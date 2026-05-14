<?php

namespace Rexp\Form\Application\Service;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис адаптации пользовательских полей Bitrix к полям конструктора.
 */
final class UserFieldTypeAdapterService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param ?FormFieldTypeRegistryService $typeRegistry
     */
    public function __construct(private readonly ?FormFieldTypeRegistryService $typeRegistry = null)
    {
    }

    /**
     * Возвращает пользователя поля types.
     *
     * @return array
     */
    public function getUserFieldTypes(): array
    {
        $manager = $this->getUserFieldManager();
        if (!$manager || !method_exists($manager, 'GetUserType')) {
            return [];
        }

        $types = $manager->GetUserType(false);
        if (!is_array($types)) {
            return [];
        }

        $result = [];
        foreach ($types as $userTypeId => $typeInfo) {
            if (!is_array($typeInfo)) {
                continue;
            }

            $id = trim((string)($typeInfo['USER_TYPE_ID'] ?? $userTypeId));
            if ($id === '') {
                continue;
            }

            $result[$id] = [
                'userTypeId' => $id,
                'title' => trim((string)($typeInfo['DESCRIPTION'] ?? $id)) ?: $id,
                'baseType' => $this->mapBaseType($id, $typeInfo),
                'className' => (string)($typeInfo['CLASS_NAME'] ?? ''),
                'supported' => $this->isSafelySupported($id),
            ];
        }

        ksort($result);
        return $result;
    }

    /**
     * Возвращает полей for сущности.
     *
     * @param string $entityId
     * @param int $valueId
     *
     * @return array
     */
    public function getFieldsForEntity(string $entityId, int $valueId = 0): array
    {
        $entityId = trim($entityId);
        if ($entityId === '') {
            return [];
        }

        $manager = $this->getUserFieldManager();
        if (!$manager || !method_exists($manager, 'GetUserFields')) {
            return [];
        }

        $fields = $manager->GetUserFields($entityId, $valueId, defined('LANGUAGE_ID') ? LANGUAGE_ID : false);
        if (!is_array($fields)) {
            return [];
        }

        $result = [];
        $index = 0;
        foreach ($fields as $fieldName => $field) {
            if (!is_array($field)) {
                continue;
            }
            $field['FIELD_NAME'] = $field['FIELD_NAME'] ?? $fieldName;
            $result[] = $this->adaptUserField($field, $index++);
        }

        return $result;
    }

    /**
     * Адаптирует пользовательское поле Bitrix к формату конструктора.
     *
     * @param array $userField
     * @param int $index
     *
     * @return array
     */
    public function adaptUserField(array $userField, int $index = 0): array
    {
        $userTypeId = trim((string)($userField['USER_TYPE_ID'] ?? 'string')) ?: 'string';
        $code = trim((string)($userField['FIELD_NAME'] ?? $userField['fieldName'] ?? ''));
        $title = $this->resolveUserFieldTitle($userField, $code);
        $type = $this->mapBaseType($userTypeId, is_array($userField['USER_TYPE'] ?? null) ? $userField['USER_TYPE'] : []);
        $multiple = ((string)($userField['MULTIPLE'] ?? 'N')) === 'Y';
        $view = $this->resolveView($userTypeId, $type, $multiple);

        $field = [
            'uid' => $code !== '' ? $code : 'uf_' . ($index + 1),
            'id' => $code !== '' ? $code : 'uf_' . ($index + 1),
            'code' => $code,
            'title' => $title,
            'label' => $title,
            'type' => $type,
            'userTypeId' => $userTypeId,
            'mandatory' => ((string)($userField['MANDATORY'] ?? 'N')) === 'Y',
            'required' => ((string)($userField['MANDATORY'] ?? 'N')) === 'Y',
            'multiple' => $multiple,
            'sort' => (int)($userField['SORT'] ?? (($index + 1) * 100)),
            'ui' => [
                'view' => $view,
                'placeholder' => $this->placeholder($type, $view, $userTypeId),
                'hint' => trim((string)($userField['HELP_MESSAGE'] ?? '')),
                'width' => 'full',
            ],
            'items' => $this->resolveItems($userField),
            'settings' => is_array($userField['SETTINGS'] ?? null) ? $userField['SETTINGS'] : [],
            'source' => null,
            'defaultValue' => $userField['VALUE'] ?? ($multiple ? [] : null),
            'origin' => [
                'provider' => 'main.userfield',
                'entityId' => (string)($userField['ENTITY_ID'] ?? ''),
                'fieldId' => (int)($userField['ID'] ?? 0),
                'fieldName' => $code,
                'userTypeId' => $userTypeId,
            ],
        ];

        if ($type === 'entity_selector') {
            $field['source'] = $this->buildEntitySelectorSource($userTypeId, $userField);
            $field['selectorEntities'] = $field['source']['entities'];
            $field['relationType'] = $this->resolveRelationType($userTypeId);
        }

        return $this->registry()->normalizeField($field, $index);
    }

    /**

     * Возвращает пользователя поля manager.

     *

     * @return mixed

     */

    private function getUserFieldManager(): mixed
    {
        return $GLOBALS['USER_FIELD_MANAGER'] ?? null;
    }

    /**
     * Сопоставляет базовый тип пользовательского поля с типом конструктора.
     *
     * @param string $userTypeId
     * @param array $typeInfo
     *
     * @return string
     */
    private function mapBaseType(string $userTypeId, array $typeInfo = []): string
    {
        $userTypeId = strtolower(trim($userTypeId));
        $baseType = strtolower(trim((string)($typeInfo['BASE_TYPE'] ?? '')));

        if ($baseType !== '') {
            return match ($baseType) {
                'int' => 'integer',
                'double' => 'double',
                'enum' => 'enumeration',
                'datetime' => 'datetime',
                'file' => 'file',
                'string' => in_array($userTypeId, ['url', 'webdav_element'], true) ? $userTypeId : 'string',
                default => $baseType,
            };
        }

        return match ($userTypeId) {
            'integer', 'int' => 'integer',
            'double', 'float' => 'double',
            'boolean' => 'boolean',
            'enumeration', 'enum' => 'enumeration',
            'date' => 'date',
            'datetime' => 'datetime',
            'file' => 'file',
            'url' => 'url',
            'employee', 'crm', 'iblock_element', 'iblock_section' => 'entity_selector',
            'money' => 'money',
            default => $this->isSafelySupported($userTypeId) ? 'string' : 'userfield',
        };
    }

    /**

     * Проверяет признак safely supported.

     *

     * @param string $userTypeId

     *

     * @return bool

     */

    private function isSafelySupported(string $userTypeId): bool
    {
        return in_array(strtolower(trim($userTypeId)), [
            'string',
            'integer',
            'double',
            'boolean',
            'enumeration',
            'enum',
            'date',
            'datetime',
            'file',
            'url',
            'employee',
            'crm',
            'iblock_element',
            'iblock_section',
            'money',
        ], true);
    }

    /**
     * Определяет пользователя поля title.
     *
     * @param array $userField
     * @param string $fallback
     *
     * @return string
     */
    private function resolveUserFieldTitle(array $userField, string $fallback): string
    {
        foreach (['EDIT_FORM_LABEL', 'LIST_COLUMN_LABEL', 'LIST_FILTER_LABEL', 'FIELD_NAME'] as $key) {
            $value = trim((string)($userField[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return $fallback !== '' ? $fallback : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_USERFIELDTYPEADAPTERSERVICE_001');
    }

    /**

     * Определяет view.

     *

     * @param string $userTypeId

     * @param string $type

     * @param bool $multiple

     *

     * @return string

     */

    private function resolveView(string $userTypeId, string $type, bool $multiple): string
    {
        if ($type === 'enumeration') {
            return $multiple ? 'checkbox' : 'select';
        }
        if ($type === 'boolean') {
            return 'checkbox';
        }
        if ($type === 'entity_selector') {
            return 'entity_selector';
        }

        return match ($type) {
            'text' => 'textarea',
            'date' => 'date',
            'datetime' => 'datetime',
            'file' => 'file',
            default => 'input',
        };
    }

    /**

     * Возвращает плейсхолдер для пользовательского поля.

     *

     * @param string $type

     * @param string $view

     * @param string $userTypeId

     *

     * @return string

     */

    private function placeholder(string $type, string $view, string $userTypeId): string
    {
        $entity = $this->resolveRelationType($userTypeId);
        return $this->registry()->getDefaultPlaceholder($type, $view, $entity);
    }

    /**
     * Определяет элементов.
     *
     * @param array $userField
     *
     * @return array
     */
    private function resolveItems(array $userField): array
    {
        if (is_array($userField['ENUM'] ?? null)) {
            return $this->registry()->normalizeItems($userField['ENUM']);
        }

        $userTypeId = trim((string)($userField['USER_TYPE_ID'] ?? ''));
        if (!in_array($userTypeId, ['enumeration', 'enum'], true)) {
            return [];
        }

        $fieldId = (int)($userField['ID'] ?? 0);
        if ($fieldId <= 0 || !class_exists('CUserFieldEnum')) {
            return [];
        }

        $items = [];
        $iterator = \CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $fieldId]);
        while ($row = $iterator->Fetch()) {
            $items[] = [
                'id' => (string)($row['ID'] ?? ''),
                'value' => (string)($row['VALUE'] ?? ''),
                'xmlId' => (string)($row['XML_ID'] ?? $row['ID'] ?? ''),
                'sort' => (int)($row['SORT'] ?? 100),
            ];
        }

        return $this->registry()->normalizeItems($items);
    }

    /**
     * Формирует сущности selector source.
     *
     * @param string $userTypeId
     * @param array $userField
     *
     * @return array
     */
    private function buildEntitySelectorSource(string $userTypeId, array $userField): array
    {
        $relation = $this->resolveRelationType($userTypeId);
        $entities = match ($relation) {
            'user' => ['user', 'department', 'meta-user'],
            'department' => ['department'],
            'crm' => ['lead', 'deal', 'contact', 'company'],
            'iblock_element' => ['iblock-element'],
            'iblock_section' => ['iblock-section'],
            default => [$relation],
        };

        return [
            'provider' => $relation === 'crm' ? 'crm.userfield' : 'ui.entity-selector',
            'context' => 'REXP_FORM',
            'entities' => $entities,
            'options' => [
                'userTypeId' => $userTypeId,
                'settings' => is_array($userField['SETTINGS'] ?? null) ? $userField['SETTINGS'] : [],
            ],
        ];
    }

    /**

     * Определяет relation типа.

     *

     * @param string $userTypeId

     *

     * @return string

     */

    private function resolveRelationType(string $userTypeId): string
    {
        return match (strtolower(trim($userTypeId))) {
            'employee' => 'user',
            'iblock_section' => 'iblock_section',
            'iblock_element' => 'iblock_element',
            'crm' => 'crm',
            default => strtolower(trim($userTypeId)) ?: 'entity',
        };
    }

    /**

     * Возвращает реестр типов полей.

     *

     * @return FormFieldTypeRegistryService

     */

    private function registry(): FormFieldTypeRegistryService
    {
        return $this->typeRegistry ?? new FormFieldTypeRegistryService();
    }
}
