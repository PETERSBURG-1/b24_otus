<?php

namespace Rexp\Form\Application\Service;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис реестра и нормализации типов полей формы.
 */
final class FormFieldTypeRegistryService
{
    private const TYPE_STRING = 'string';
    private const TYPE_TEXT = 'text';
    private const TYPE_INTEGER = 'integer';
    private const TYPE_DOUBLE = 'double';
    private const TYPE_DATE = 'date';
    private const TYPE_DATETIME = 'datetime';
    private const TYPE_FILE = 'file';
    private const TYPE_BOOLEAN = 'boolean';
    private const TYPE_ENUMERATION = 'enumeration';
    private const TYPE_USER = 'user';
    private const TYPE_DEPARTMENT = 'department';
    private const TYPE_ENTITY_SELECTOR = 'entity_selector';

    /**
     * Возвращает типа definitions.
     *
     * @return array
     */
    public function getTypeDefinitions(): array
    {
        return [
            self::TYPE_STRING => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_001'),
                'baseType' => self::TYPE_STRING,
                'supportedViews' => ['input'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder(self::TYPE_STRING),
            ],
            'email' => [
                'title' => 'E-mail',
                'baseType' => self::TYPE_STRING,
                'supportedViews' => ['input'],
                'validation' => ['format' => 'email'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder('email'),
            ],
            'phone' => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_002'),
                'baseType' => self::TYPE_STRING,
                'supportedViews' => ['input'],
                'validation' => ['format' => 'phone'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder('phone'),
            ],
            'url' => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_003'),
                'baseType' => self::TYPE_STRING,
                'supportedViews' => ['input'],
                'validation' => ['format' => 'url'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder('url'),
            ],
            self::TYPE_TEXT => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_004'),
                'baseType' => self::TYPE_TEXT,
                'supportedViews' => ['textarea'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder(self::TYPE_TEXT),
            ],
            self::TYPE_INTEGER => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_005'),
                'baseType' => self::TYPE_INTEGER,
                'supportedViews' => ['input'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder(self::TYPE_INTEGER),
            ],
            self::TYPE_DOUBLE => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_006'),
                'baseType' => self::TYPE_DOUBLE,
                'supportedViews' => ['input'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder(self::TYPE_DOUBLE),
            ],
            'money' => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_007'),
                'baseType' => self::TYPE_DOUBLE,
                'supportedViews' => ['money'],
                'multiple' => false,
                'placeholder' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_008'),
            ],
            self::TYPE_DATE => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_009'),
                'baseType' => self::TYPE_DATE,
                'supportedViews' => ['date'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder(self::TYPE_DATE),
            ],
            self::TYPE_DATETIME => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_010'),
                'baseType' => self::TYPE_DATETIME,
                'supportedViews' => ['datetime'],
                'multiple' => false,
                'placeholder' => $this->getDefaultPlaceholder(self::TYPE_DATETIME),
            ],
            self::TYPE_FILE => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_011'),
                'baseType' => self::TYPE_FILE,
                'supportedViews' => ['file'],
                'multiple' => true,
                'placeholder' => $this->getDefaultPlaceholder(self::TYPE_FILE),
            ],
            self::TYPE_BOOLEAN => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_012'),
                'baseType' => self::TYPE_BOOLEAN,
                'supportedViews' => ['checkbox', 'switch'],
                'multiple' => false,
                'placeholder' => '',
            ],
            self::TYPE_ENUMERATION => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_013'),
                'baseType' => self::TYPE_ENUMERATION,
                'supportedViews' => ['select', 'radio', 'checkbox'],
                'multiple' => true,
                'placeholder' => $this->getDefaultPlaceholder('select'),
            ],
            self::TYPE_USER => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_014'),
                'baseType' => self::TYPE_ENTITY_SELECTOR,
                'supportedViews' => ['entity_selector'],
                'multiple' => true,
                'placeholder' => $this->getDefaultPlaceholder(self::TYPE_USER),
            ],
            self::TYPE_DEPARTMENT => [
                'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_015'),
                'baseType' => self::TYPE_ENTITY_SELECTOR,
                'supportedViews' => ['department_select'],
                'multiple' => true,
                'placeholder' => '',
            ],
        ];
    }

    /**
     * Нормализует поля.
     *
     * @param array $field
     * @param int $index
     *
     * @return array
     */
    public function normalizeField(array $field, int $index = 0): array
    {
        $legacyType = trim((string)($field['type'] ?? self::TYPE_STRING)) ?: self::TYPE_STRING;
        $type = $this->normalizeTypeCode($legacyType);
        $ui = is_array($field['ui'] ?? null) ? $field['ui'] : [];
        $settings = is_array($field['settings'] ?? null) ? $field['settings'] : [];
        $validation = is_array($field['validation'] ?? null) ? $field['validation'] : [];

        $field['type'] = $type;
        if ($legacyType !== $type) {
            $field['legacyType'] = $legacyType;
        }

        $field['uid'] = trim((string)($field['uid'] ?? $field['id'] ?? '')) ?: ('field_' . ($index + 1));
        $field['id'] = $field['uid'];
        $field['code'] = trim((string)($field['code'] ?? $field['name'] ?? $field['uid']));
        $field['title'] = trim((string)($field['title'] ?? $field['label'] ?? $field['code']));
        $field['label'] = trim((string)($field['label'] ?? $field['title']));
        $field['sort'] = (int)($field['sort'] ?? (($index + 1) * 100));

        $field['mandatory'] = $this->toBool($field['mandatory'] ?? $field['required'] ?? false);
        $field['required'] = $field['mandatory'];

        $ui['view'] = trim((string)($ui['view'] ?? $this->resolveDefaultView($legacyType, $type)));
        if ($ui['view'] === '') {
            $ui['view'] = $this->resolveDefaultView($legacyType, $type);
        }

        $ui['placeholder'] = trim((string)($ui['placeholder'] ?? $field['placeholder'] ?? ''));
        if (!$this->usesPlaceholder($type, $ui['view'])) {
            $ui['placeholder'] = '';
        } elseif ($ui['placeholder'] === '') {
            $ui['placeholder'] = $this->getDefaultPlaceholder($type, $ui['view'], $this->resolveEntitySelectorKind($field, $legacyType));
        }
        $ui['hint'] = trim((string)($ui['hint'] ?? $field['hint'] ?? ''));
        $ui['width'] = trim((string)($ui['width'] ?? $field['widthMode'] ?? 'full')) ?: 'full';
        $field['ui'] = $ui;
        $field['placeholder'] = $ui['placeholder'];

        $field['multiple'] = $this->resolveMultiple($field, $legacyType, $type, $ui['view']);
        $field['items'] = $this->normalizeItems(is_array($field['items'] ?? null) ? $field['items'] : []);
        $field['settings'] = $settings;
        $field['validation'] = $validation;

        if ($type === self::TYPE_ENUMERATION) {
            $field['items'] = $this->normalizeItems($field['items']);
        }

        if (in_array($type, [self::TYPE_USER, self::TYPE_DEPARTMENT, self::TYPE_ENTITY_SELECTOR], true)) {
            $field = $this->normalizeEntitySelectorField($field, $legacyType);
        }

        if ($type === self::TYPE_ENUMERATION && ($field['ui']['view'] ?? '') === 'entity_selector') {
            $field = $this->normalizeEntitySelectorField($field, $legacyType);
        }

        if (!array_key_exists('defaultValue', $field)) {
            $field['defaultValue'] = $field['multiple'] ? [] : null;
        }

        unset(
            $field['fieldName'],
            $field['binding'],
            $field['entityBinding']
        );

        return $field;
    }

    /**
     * Проверяет поддержку множественного значения для типа поля.
     *
     * @param string $type
     *
     * @return bool
     */
    public function supportsMultiple(string $type): bool
    {
        return in_array($this->normalizeTypeCode($type), [self::TYPE_ENUMERATION, self::TYPE_USER, self::TYPE_DEPARTMENT, self::TYPE_ENTITY_SELECTOR, self::TYPE_FILE, 'userfield'], true);
    }

    /**
     * Нормализует типа кода.
     *
     * @param string $type
     *
     * @return string
     */
    public function normalizeTypeCode(string $type): string
    {
        $type = strtolower(trim($type));

        return match ($type) {
            '', 'str', 'string_formatted', 'char' => self::TYPE_STRING,
            'html', 'textarea' => self::TYPE_TEXT,
            'number', 'float', 'decimal' => self::TYPE_DOUBLE,
            'int' => self::TYPE_INTEGER,
            'bool', 'checkbox', 'switch' => self::TYPE_BOOLEAN,
            'list', 'enum', 'select', 'radio', 'checkbox_list' => self::TYPE_ENUMERATION,
            'employee', 'crm_employee' => self::TYPE_USER,
            'user' => self::TYPE_USER,
            'department' => self::TYPE_DEPARTMENT,
            'crm', 'iblock_element', 'iblock_section' => self::TYPE_ENTITY_SELECTOR,
            default => $type,
        };
    }

    /**
     * Проверяет, использует ли тип поля плейсхолдер.
     *
     * @param string $type
     * @param string $view
     *
     * @return bool
     */
    private function usesPlaceholder(string $type, string $view = ''): bool
    {
        $type = $this->normalizeTypeCode($type);
        $view = trim($view);

        if (in_array($type, [self::TYPE_BOOLEAN, self::TYPE_FILE, self::TYPE_DATE, self::TYPE_DATETIME], true)) {
            return false;
        }

        if ($type === self::TYPE_ENUMERATION && in_array($view, ['radio', 'checkbox'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Возвращает default placeholder.
     *
     * @param string $type
     * @param string $view
     * @param string $entity
     *
     * @return string
     */
    public function getDefaultPlaceholder(string $type, string $view = '', string $entity = ''): string
    {
        $type = $this->normalizeTypeCode($type);
        $view = trim($view);
        $entity = trim($entity);

        if (!$this->usesPlaceholder($type, $view)) {
            return '';
        }

        if (in_array($type, [self::TYPE_USER, self::TYPE_DEPARTMENT, self::TYPE_ENTITY_SELECTOR], true)) {
            return match ($entity) {
                'department' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_016'),
                'user', 'employee' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_017'),
                default => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_018'),
            };
        }

        if ($type === self::TYPE_ENUMERATION) {
            return $view === 'tag_input' ? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_019') : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_020');
        }

        return match ($type) {
            self::TYPE_TEXT => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_021'),
            'email' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_022'),
            'phone' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_023'),
            'url' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_024'),
            self::TYPE_INTEGER, self::TYPE_DOUBLE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_025'),
            self::TYPE_DATE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_026'),
            self::TYPE_DATETIME => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_027'),
            self::TYPE_FILE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_028'),
            self::TYPE_BOOLEAN => '',
            default => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_029'),
        };
    }

    /**
     * Нормализует список элементов для вывода.
     *
     * @param array $items
     *
     * @return array
     */
    public function normalizeItems(array $items): array
    {
        $result = [];
        foreach ($items as $index => $item) {
            if (is_array($item)) {
                $id = trim((string)($item['id'] ?? $item['value'] ?? $item['xmlId'] ?? ''));
                $value = trim((string)($item['value'] ?? $item['label'] ?? $item['title'] ?? $item['text'] ?? $id));
                if ($id === '' && $value !== '') {
                    $id = $value;
                }
                if ($value === '') {
                    continue;
                }

                $normalized = $item;
                $normalized['id'] = $id;
                $normalized['value'] = $value;
                $normalized['label'] = trim((string)($item['label'] ?? $value));
                $normalized['title'] = trim((string)($item['title'] ?? $normalized['label']));
                $normalized['xmlId'] = trim((string)($item['xmlId'] ?? $item['XML_ID'] ?? $id));
                $normalized['sort'] = (int)($item['sort'] ?? (($index + 1) * 100));
                $result[] = $normalized;
                continue;
            }

            $value = trim((string)$item);
            if ($value === '') {
                continue;
            }

            $result[] = [
                'id' => $value,
                'value' => $value,
                'label' => $value,
                'title' => $value,
                'xmlId' => $value,
                'sort' => ($index + 1) * 100,
            ];
        }

        usort($result, static fn(array $a, array $b): int => ((int)($a['sort'] ?? 0)) <=> ((int)($b['sort'] ?? 0)));
        return array_values($result);
    }

    /**
     * Определяет default view.
     *
     * @param string $legacyType
     * @param string $type
     *
     * @return string
     */
    private function resolveDefaultView(string $legacyType, string $type): string
    {
        return match ($legacyType) {
            'switch' => 'switch',
            'checkbox' => 'checkbox',
            'radio' => 'radio',
            'checkbox_list' => 'checkbox',
            'select' => 'select',
            'user', 'employee' => 'entity_selector',
            'department' => 'department_select',
            default => match ($type) {
                self::TYPE_TEXT => 'textarea',
                self::TYPE_DATE => 'date',
                self::TYPE_DATETIME => 'datetime',
                self::TYPE_FILE => 'file',
                self::TYPE_BOOLEAN => 'checkbox',
                self::TYPE_ENUMERATION => 'select',
                self::TYPE_USER, self::TYPE_ENTITY_SELECTOR => 'entity_selector',
                self::TYPE_DEPARTMENT => 'department_select',
                default => 'input',
            },
        };
    }

    /**
     * Определяет multiple.
     *
     * @param array $field
     * @param string $legacyType
     * @param string $type
     * @param string $view
     *
     * @return bool
     */
    private function resolveMultiple(array $field, string $legacyType, string $type, string $view): bool
    {
        if ($legacyType === 'checkbox_list' || ($type === self::TYPE_ENUMERATION && $view === 'checkbox')) {
            return true;
        }

        if (!$this->supportsMultiple($type)) {
            return false;
        }

        return $this->toBool($field['multiple'] ?? false);
    }

    /**
     * Нормализует сущности selector поля.
     *
     * @param array $field
     * @param string $legacyType
     *
     * @return array
     */
    private function normalizeEntitySelectorField(array $field, string $legacyType): array
    {
        $entity = $this->resolveEntitySelectorKind($field, $legacyType);
        $source = is_array($field['source'] ?? null) ? $field['source'] : [];
        $source['provider'] = trim((string)($source['provider'] ?? 'ui.entity-selector')) ?: 'ui.entity-selector';
        $source['context'] = trim((string)($source['context'] ?? 'REXP_FORM')) ?: 'REXP_FORM';

        if ($entity === 'department') {
            $source['provider'] = 'iblock.department';
            $source['entities'] = ['department'];
            $field['ui'] = is_array($field['ui'] ?? null) ? $field['ui'] : [];
            $field['ui']['view'] = 'department_select';
        } elseif ($entity === 'user' || $entity === 'employee') {
            $source['entities'] = ['user'];
        } elseif (!is_array($source['entities'] ?? null) || empty($source['entities'])) {
            $source['entities'] = [$entity];
        }

        $field['source'] = $source;
        $field['relationType'] = $entity === 'department' ? 'department' : ($field['relationType'] ?? $entity);
        $field['selectorEntities'] = $source['entities'];

        return $field;
    }

    /**
     * Определяет сущности selector kind.
     *
     * @param array $field
     * @param string $legacyType
     *
     * @return string
     */
    private function resolveEntitySelectorKind(array $field, string $legacyType): string
    {
        $type = trim((string)($field['type'] ?? ''));
        if ($type === self::TYPE_DEPARTMENT) {
            return 'department';
        }
        if ($type === self::TYPE_USER) {
            return 'user';
        }

        if ($legacyType === 'department') {
            return 'department';
        }
        if (in_array($legacyType, ['user', 'employee'], true)) {
            return 'user';
        }

        $relationType = trim((string)($field['relationType'] ?? ''));
        if ($relationType !== '') {
            return $relationType;
        }

        $source = is_array($field['source'] ?? null) ? $field['source'] : [];
        $entities = is_array($source['entities'] ?? null) ? $source['entities'] : [];
        $first = trim((string)($entities[0] ?? ''));

        return $first !== '' ? $first : 'entity';
    }

    /**
     * Приводит значение к булевому виду.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int)$value > 0;
        }
        if (!is_scalar($value)) {
            return false;
        }

        return in_array(strtolower(trim((string)$value)), ['1', 'y', 'yes', 'true', 'on', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMFIELDTYPEREGISTRYSERVICE_030')], true);
    }
}
