<?php

namespace Rexp\Form\Bizproc;

use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Rexp\Form\Infrastructure\Persistence\Orm\FormSubmissionTable;
use Rexp\Form\Infrastructure\Persistence\Orm\FormTable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Документ бизнес-процессов для отправок форм.
 */
final class FormSubmissionDocument implements \IBPWorkflowDocument
{
    public const MODULE_ID = 'rexp.form';
    public const BIZPROC_MODULE_ID = 'crm';
    public const DOCUMENT_TYPE_PREFIX = 'form_';

    /**
     * Возвращает тип документа бизнес-процесса.
     *
     * @param mixed $documentId
     *
     * @return mixed
     */
    public static function GetDocumentType($documentId)
    {
        $row = self::getSubmissionRow((int)$documentId);

        return $row ? self::getDocumentTypeCode((int)($row['FORM_ID'] ?? 0)) : '';
    }

    /**
     * Возвращает название типа документа бизнес-процесса.
     *
     * @param mixed $documentType
     *
     * @return string
     */
    public static function GetDocumentTypeName($documentType): string
    {
        $formId = self::extractFormId($documentType);
        $form = $formId > 0 ? FormTable::getById($formId)->fetch() : null;

        return trim((string)($form['NAME'] ?? '')) ?: (\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_001') . $formId);
    }

    /**
     * Возвращает название документа бизнес-процесса.
     *
     * @param mixed $documentId
     *
     * @return string
     */
    public static function GetDocumentName($documentId): string
    {
        $row = self::getSubmissionRow((int)$documentId);
        $formName = '';
        if ($row) {
            $form = FormTable::getById((int)($row['FORM_ID'] ?? 0))->fetch();
            $formName = trim((string)($form['NAME'] ?? ''));
        }

        return \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_002') . (int)$documentId . ($formName !== '' ? ' · ' . $formName : '');
    }

    /**
     * Возвращает типы полей документа бизнес-процесса.
     *
     * @param mixed $documentType
     *
     * @return mixed
     */
    public static function GetDocumentFieldTypes($documentType)
    {
        $result = \CBPHelper::GetDocumentFieldTypes();
        if (isset($result['select']) && is_array($result['select'])) {
            $result['select']['Complex'] = true;
        }

        return $result;
    }

    /**
     * Возвращает поля документа бизнес-процесса.
     *
     * @param mixed $documentType
     *
     * @return mixed
     */
    public static function GetDocumentFields($documentType)
    {
        $formId = self::extractFormId($documentType);
        $form = $formId > 0 ? FormTable::getById($formId)->fetch() : null;
        $schema = self::extractSchema($form);

        $fields = [
            'ID' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_003'), 'Type' => FieldType::INT, 'Filterable' => true, 'Editable' => false, 'Required' => true],
            'FORM_ID' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_004'), 'Type' => FieldType::INT, 'Filterable' => true, 'Editable' => false, 'Required' => true],
            'FORM_CODE' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_005'), 'Type' => FieldType::STRING, 'Filterable' => true, 'Editable' => false, 'Required' => false],
            'FORM_NAME' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_006'), 'Type' => FieldType::STRING, 'Filterable' => true, 'Editable' => false, 'Required' => false],
            'ENTRY_ID' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_007'), 'Type' => FieldType::INT, 'Filterable' => true, 'Editable' => false, 'Required' => false],
            'MODE' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_008'), 'Type' => FieldType::STRING, 'Filterable' => true, 'Editable' => false, 'Required' => false],
            'STATUS' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_009'), 'Type' => FieldType::SELECT, 'Filterable' => true, 'Editable' => true, 'Required' => false, 'Options' => [
                'processing' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_010'),
                'success' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_011'),
                'partial_success' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_012'),
                'error' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_013'),
            ]],
            'USER_ID' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_014'), 'Type' => FieldType::USER, 'Filterable' => true, 'Editable' => false, 'Required' => false],
            'CREATED_AT' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_015'), 'Type' => FieldType::DATETIME, 'Filterable' => true, 'Editable' => false, 'Required' => false],
            'UPDATED_AT' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_016'), 'Type' => FieldType::DATETIME, 'Filterable' => true, 'Editable' => false, 'Required' => false],
            'VALUES_JSON' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_017'), 'Type' => FieldType::TEXT, 'Filterable' => false, 'Editable' => false, 'Required' => false],
            'NORMALIZED_VALUES_JSON' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_018'), 'Type' => FieldType::TEXT, 'Filterable' => false, 'Editable' => false, 'Required' => false],
            'RESULT_JSON' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_019'), 'Type' => FieldType::TEXT, 'Filterable' => false, 'Editable' => false, 'Required' => false],
            'ERRORS_JSON' => ['Name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_020'), 'Type' => FieldType::TEXT, 'Filterable' => false, 'Editable' => false, 'Required' => false],
        ];

        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }

            $code = trim((string)($field['code'] ?? ''));
            if ($code === '' || isset($fields[$code])) {
                continue;
            }

            $definition = self::buildDocumentFieldDefinition($field);
            if ($definition !== null) {
                $fields[$code] = $definition;
            }
        }

        return $fields;
    }

    /**
     * Возвращает данные документа бизнес-процесса.
     *
     * @param mixed $documentId
     *
     * @return mixed
     */
    public static function GetDocument($documentId)
    {
        $row = self::getSubmissionRow((int)$documentId);
        if (!$row) {
            return [];
        }

        return self::buildDocumentData($row);
    }

    /**
     * Создаёт документ отправки формы из бизнес-процесса.
     *
     * @param mixed $parentDocumentId
     * @param mixed $arFields
     *
     * @return mixed
     */
    public static function CreateDocument($parentDocumentId, $arFields)
    {
        $formId = self::resolveFormIdForCreate($parentDocumentId, (array)$arFields);
        if ($formId <= 0) {
            throw new \CBPArgumentOutOfRangeException('parentDocumentId', $parentDocumentId);
        }

        $form = FormTable::getById($formId)->fetch();
        if (!$form) {
            throw new \Exception(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_021'));
        }

        $values = self::extractSubmissionValuesFromBp($formId, (array)$arFields);
        $result = FormSubmissionTable::add([
            'FORM_ID' => $formId,
            'FORM_CODE' => trim((string)($form['CODE'] ?? '')),
            'ENTRY_ID' => (int)($arFields['ENTRY_ID'] ?? 0),
            'MODE' => trim((string)($arFields['MODE'] ?? 'create')) ?: 'create',
            'STATUS' => trim((string)($arFields['STATUS'] ?? 'success')) ?: 'success',
            'VALUES_JSON' => self::encodeJson($values, '{}'),
            'NORMALIZED_VALUES_JSON' => self::encodeJson($values, '{}'),
            'CONTEXT_JSON' => '{}',
            'RESULT_JSON' => self::encodeJson((array)($arFields['RESULT'] ?? []), '{}'),
            'ERRORS_JSON' => self::encodeJson((array)($arFields['ERRORS'] ?? []), '[]'),
            'USER_ID' => self::extractAuthorId((array)$arFields),
        ]);

        if (!$result->isSuccess()) {
            throw new \Exception(implode('; ', $result->getErrorMessages()));
        }

        return (int)$result->getId();
    }

    /**
     * Обновляет документ отправки формы из бизнес-процесса.
     *
     * @param mixed $documentId
     * @param mixed $arFields
     *
     * @return mixed
     */
    public static function UpdateDocument($documentId, $arFields)
    {
        $documentId = (int)$documentId;
        if ($documentId <= 0) {
            throw new \CBPArgumentNullException('documentId');
        }

        $row = self::getSubmissionRow($documentId);
        if (!$row) {
            throw new \Exception(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_022'));
        }

        $formId = (int)($row['FORM_ID'] ?? 0);
        $values = self::extractSubmissionValuesFromBp($formId, (array)$arFields);
        $update = [
            'NORMALIZED_VALUES_JSON' => self::encodeJson($values, '{}'),
        ];

        if (isset($arFields['STATUS'])) {
            $update['STATUS'] = trim((string)$arFields['STATUS']) ?: 'success';
        }
        if (isset($arFields['MODE'])) {
            $update['MODE'] = trim((string)$arFields['MODE']) ?: 'create';
        }
        if (array_key_exists('ENTRY_ID', $arFields)) {
            $update['ENTRY_ID'] = (int)$arFields['ENTRY_ID'];
        }
        if (array_key_exists('RESULT_JSON', $arFields) || array_key_exists('RESULT', $arFields)) {
            $update['RESULT_JSON'] = self::encodeJson($arFields['RESULT_JSON'] ?? $arFields['RESULT'], '{}');
        }
        if (array_key_exists('ERRORS_JSON', $arFields) || array_key_exists('ERRORS', $arFields)) {
            $update['ERRORS_JSON'] = self::encodeJson($arFields['ERRORS_JSON'] ?? $arFields['ERRORS'], '[]');
        }
        if (array_key_exists('USER_ID', $arFields) || array_key_exists('AUTHOR_ID', $arFields)) {
            $update['USER_ID'] = self::extractAuthorId((array)$arFields);
        }

        FormSubmissionTable::update($documentId, $update);
    }

    /**
     * Удаляет документ отправки формы.
     *
     * @param mixed $documentId
     *
     * @return mixed
     */
    public static function DeleteDocument($documentId)
    {
        $documentId = (int)$documentId;
        if ($documentId > 0) {
            FormSubmissionTable::delete($documentId);
        }
    }

    /**
     * Возвращает административную ссылку документа.
     *
     * @param mixed $documentId
     *
     * @return mixed
     */
    public static function GetDocumentAdminPage($documentId)
    {
        return '/forms/designer/submissions.php?FIND=' . (int)$documentId;
    }

    /**
     * Возвращает доступные группы пользователей для документа.
     *
     * @param mixed $documentType
     *
     * @return mixed
     */
    public static function GetAllowableUserGroups($documentType)
    {
        return [
            'Author' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_023'),
            'group_1' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_BIZPROC_FORMSUBMISSIONDOCUMENT_024'),
        ];
    }

    /**
     * Возвращает пользователей из группы документа.
     *
     * @param mixed $group
     * @param mixed $documentId
     *
     * @return mixed
     */
    public static function GetUsersFromUserGroup($group, $documentId)
    {
        $group = mb_strtolower(trim((string)$group));
        if ($group === 'author') {
            $row = self::getSubmissionRow((int)$documentId);
            $userId = (int)($row['USER_ID'] ?? 0);
            return $userId > 0 ? [$userId] : [];
        }

        $groupId = (int)str_replace('group_', '', $group);
        if ($groupId > 0) {
            return \CGroup::GetGroupUser($groupId);
        }

        return [];
    }

    /**
     * Проверяет право пользователя на операцию с документом.
     *
     * @param mixed $operation
     * @param mixed $userId
     * @param mixed $documentId
     * @param mixed $arParameters
     *
     * @return mixed
     */
    public static function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = [])
    {
        return true;
    }

    /**
     * Проверяет право пользователя на операцию с типом документа.
     *
     * @param mixed $operation
     * @param mixed $userId
     * @param mixed $documentType
     * @param mixed $arParameters
     *
     * @return mixed
     */
    public static function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = [])
    {
        return true;
    }

    /**
     * Возвращает доступные операции документа.
     *
     * @param mixed $documentType
     *
     * @return mixed
     */
    public static function GetAllowableOperations($documentType)
    {
        return [];
    }

    /**
     * Возвращает данные документа для истории БП.
     *
     * @param mixed $documentId
     * @param mixed $historyIndex
     *
     * @return mixed
     */
    public static function GetDocumentForHistory($documentId, $historyIndex = null)
    {
        $row = self::getSubmissionRow((int)$documentId);
        return $row ?: [];
    }

    /**
     * Восстанавливает документ из истории БП.
     *
     * @param mixed $documentId
     * @param mixed $arHistory
     *
     * @return mixed
     */
    public static function RecoverDocumentFromHistory($documentId, $arHistory)
    {
        $documentId = (int)$documentId;
        $data = is_array($arHistory) ? $arHistory : [];
        unset($data['ID']);

        if ($documentId > 0 && self::getSubmissionRow($documentId)) {
            FormSubmissionTable::update($documentId, $data);
            return true;
        }

        $result = FormSubmissionTable::add($data);
        return $result->isSuccess();
    }

    /**
     * Публикует документ бизнес-процесса.
     *
     * @param mixed $documentId
     *
     * @return mixed
     */
    public static function PublishDocument($documentId)
    {
        return true;
    }

    /**
     * Снимает публикацию документа бизнес-процесса.
     *
     * @param mixed $documentId
     *
     * @return mixed
     */
    public static function UnpublishDocument($documentId)
    {
        return true;
    }

    /**
     * Блокирует документ бизнес-процесса.
     *
     * @param mixed $documentId
     * @param mixed $workflowId
     *
     * @return mixed
     */
    public static function LockDocument($documentId, $workflowId)
    {
        return true;
    }

    /**
     * Снимает блокировку документа бизнес-процесса.
     *
     * @param mixed $documentId
     * @param mixed $workflowId
     *
     * @return mixed
     */
    public static function UnlockDocument($documentId, $workflowId)
    {
        return true;
    }

    /**
     * Проверяет блокировку документа бизнес-процесса.
     *
     * @param mixed $documentId
     * @param mixed $workflowId
     *
     * @return mixed
     */
    public static function IsDocumentLocked($documentId, $workflowId)
    {
        return false;
    }

    /**
     * Возвращает complex document типа.
     *
     * @param int $formId
     *
     * @return array
     */
    public static function getComplexDocumentType(int $formId): array
    {
        return [self::BIZPROC_MODULE_ID, self::class, self::getDocumentTypeCode($formId)];
    }

    /**
     * Возвращает complex document ID.
     *
     * @param int $submissionId
     *
     * @return array
     */
    public static function getComplexDocumentId(int $submissionId): array
    {
        return [self::BIZPROC_MODULE_ID, self::class, $submissionId];
    }

    /**
     * Возвращает document типа кода.
     *
     * @param int $formId
     *
     * @return string
     */
    public static function getDocumentTypeCode(int $formId): string
    {
        if ($formId <= 0) {
            return '';
        }

        $form = FormTable::getById($formId)->fetch();
        $code = trim((string)($form['CODE'] ?? ''));

        return $code !== '' ? $code : (self::DOCUMENT_TYPE_PREFIX . $formId);
    }

    /**
     * Определяет формы ID for create.
     *
     * @param mixed $parentDocumentId
     * @param array $arFields
     *
     * @return int
     */
    private static function resolveFormIdForCreate($parentDocumentId, array $arFields): int
    {
        if (is_array($parentDocumentId) && isset($parentDocumentId[2])) {
            $formId = self::extractFormId($parentDocumentId[2]);
            if ($formId > 0) {
                return $formId;
            }
        }

        $formId = (int)($arFields['FORM_ID'] ?? 0);
        if ($formId > 0) {
            return $formId;
        }

        $documentType = (string)($arFields['DOCUMENT_TYPE'] ?? '');
        return self::extractFormId($documentType);
    }

    /**
     * Формирует document поля definition.
     *
     * @param array $field
     *
     * @return ?array
     */
    private static function buildDocumentFieldDefinition(array $field): ?array
    {
        $code = trim((string)($field['code'] ?? ''));
        if ($code === '') {
            return null;
        }

        $type = trim((string)($field['type'] ?? 'text'));
        $definition = [
            'Name' => trim((string)($field['label'] ?? $code)) ?: $code,
            'Type' => self::resolveFieldType($type),
            'Filterable' => true,
            'Editable' => true,
            'Required' => self::toBool($field['required'] ?? false),
            'Multiple' => self::supportsMultiple($type, $field),
        ];

        $options = self::extractOptions($field);
        if ($options !== []) {
            $definition['Options'] = $options;
        }

        return $definition;
    }

    /**
     * Формирует document data.
     *
     * @param array $row
     *
     * @return array
     */
    private static function buildDocumentData(array $row): array
    {
        $form = FormTable::getById((int)($row['FORM_ID'] ?? 0))->fetch() ?: [];
        $schema = self::extractSchema($form);
        $values = self::decodeJson((string)($row['NORMALIZED_VALUES_JSON'] ?? ''), []);
        if ($values === []) {
            $values = self::decodeJson((string)($row['VALUES_JSON'] ?? ''), []);
        }

        $result = [
            'ID' => (int)($row['ID'] ?? 0),
            'FORM_ID' => (int)($row['FORM_ID'] ?? 0),
            'FORM_CODE' => (string)($row['FORM_CODE'] ?? ''),
            'FORM_NAME' => (string)($form['NAME'] ?? ''),
            'ENTRY_ID' => (int)($row['ENTRY_ID'] ?? 0),
            'MODE' => (string)($row['MODE'] ?? ''),
            'STATUS' => (string)($row['STATUS'] ?? ''),
            'USER_ID' => (int)($row['USER_ID'] ?? 0) > 0 ? 'user_' . (int)$row['USER_ID'] : '',
            'CREATED_AT' => self::dateToString($row['CREATED_AT'] ?? null),
            'UPDATED_AT' => self::dateToString($row['UPDATED_AT'] ?? null),
            'VALUES_JSON' => (string)($row['VALUES_JSON'] ?? '{}'),
            'NORMALIZED_VALUES_JSON' => (string)($row['NORMALIZED_VALUES_JSON'] ?? '{}'),
            'RESULT_JSON' => (string)($row['RESULT_JSON'] ?? '{}'),
            'ERRORS_JSON' => (string)($row['ERRORS_JSON'] ?? '[]'),
        ];

        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }
            $code = trim((string)($field['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $result[$code] = self::normalizeValueForBp($values[$code] ?? null, (string)($field['type'] ?? 'text'), self::supportsMultiple((string)($field['type'] ?? 'text'), $field), $field);
        }

        return $result;
    }

    /**
     * Нормализует значения for bp.
     *
     * @param mixed $value
     * @param string $type
     * @param bool $multiple
     * @param array $field
     *
     * @return mixed
     */
    private static function normalizeValueForBp(mixed $value, string $type, bool $multiple, array $field = []): mixed
    {
        if ($multiple) {
            $items = is_array($value) ? array_values($value) : ($value === null || $value === '' ? [] : [$value]);
            return array_values(array_filter(array_map(static fn($item) => self::normalizeSingleValueForBp($item, $type, $field), $items), static fn($item) => $item !== null && $item !== ''));
        }

        return self::normalizeSingleValueForBp($value, $type, $field);
    }

    /**
     * Нормализует single значения for bp.
     *
     * @param mixed $value
     * @param string $type
     * @param array $field
     *
     * @return mixed
     */
    private static function normalizeSingleValueForBp(mixed $value, string $type, array $field = []): mixed
    {
        if ($value === null) {
            return '';
        }

        if (self::isUserLikeSelectorField($type, $field)) {
            if (is_array($value)) {
                $value = reset($value);
            }
            $id = (int)$value;
            return $id > 0 ? 'user_' . $id : '';
        }

        if ($type === 'checkbox' || $type === 'boolean') {
            return self::toBool($value) ? 'Y' : 'N';
        }

        if (is_array($value)) {
            return implode(', ', array_map(static fn($item) => is_scalar($item) ? (string)$item : '', $value));
        }

        return is_scalar($value) ? (string)$value : '';
    }

    /**
     * Извлекает отправки значений from bp.
     *
     * @param int $formId
     * @param array $arFields
     *
     * @return array
     */
    private static function extractSubmissionValuesFromBp(int $formId, array $arFields): array
    {
        $form = FormTable::getById($formId)->fetch() ?: [];
        $schema = self::extractSchema($form);
        $result = [];
        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }
            $code = trim((string)($field['code'] ?? ''));
            if ($code === '' || !array_key_exists($code, $arFields)) {
                continue;
            }
            $result[$code] = self::extractValueFromBp($arFields[$code], (string)($field['type'] ?? 'text'), self::supportsMultiple((string)($field['type'] ?? 'text'), $field), $field);
        }
        return $result;
    }

    /**
     * Извлекает значения from bp.
     *
     * @param mixed $value
     * @param string $type
     * @param bool $multiple
     * @param array $field
     *
     * @return mixed
     */
    private static function extractValueFromBp(mixed $value, string $type, bool $multiple, array $field = []): mixed
    {
        if ($multiple) {
            $items = is_array($value) ? array_values($value) : ($value === null || $value === '' ? [] : [$value]);
            return array_values(array_filter(array_map(static fn($item) => self::extractSingleValueFromBp($item, $type, $field), $items), static fn($item) => $item !== null && $item !== ''));
        }

        return self::extractSingleValueFromBp($value, $type, $field);
    }

    /**
     * Извлекает single значения from bp.
     *
     * @param mixed $value
     * @param string $type
     * @param array $field
     *
     * @return mixed
     */
    private static function extractSingleValueFromBp(mixed $value, string $type, array $field = []): mixed
    {
        if (self::isUserLikeSelectorField($type, $field)) {
            if (is_string($value) && str_starts_with($value, 'user_')) {
                return (int)substr($value, 5);
            }
            return (int)$value;
        }

        if ($type === 'checkbox' || $type === 'boolean') {
            return self::toBool($value);
        }

        return $value;
    }

    /**
     * Проверяет поддержку множественного значения для типа поля.
     *
     * @param string $type
     * @param array $field
     *
     * @return bool
     */
    private static function supportsMultiple(string $type, array $field): bool
    {
        if (self::toBool($field['multiple'] ?? false)) {
            return true;
        }

        $view = is_array($field['ui'] ?? null) ? trim((string)($field['ui']['view'] ?? '')) : '';

        return in_array(trim($type), ['checkbox_group', 'multiselect', 'employee_multiple', 'department_multiple'], true)
            || trim($type) === 'file'
            || (trim($type) === 'enumeration' && in_array($view, ['checkbox', 'checkboxes', 'tag_input'], true));
    }

    /**
     * Определяет поля типа.
     *
     * @param string $type
     *
     * @return string
     */
    private static function resolveFieldType(string $type): string
    {
        return match (trim($type)) {
            'number', 'double', 'money' => FieldType::DOUBLE,
            'checkbox', 'boolean' => FieldType::BOOL,
            'date' => FieldType::DATE,
            'datetime' => FieldType::DATETIME,
            'textarea', 'html', 'richtext' => FieldType::TEXT,
            'select', 'radio', 'multiselect', 'checkbox_group', 'enumeration' => FieldType::SELECT,
            'employee', 'user', 'employee_multiple' => FieldType::USER,
            'entity_selector' => FieldType::STRING,
            'file' => FieldType::FILE,
            default => FieldType::STRING,
        };
    }

    /**
     * Проверяет признак пользователя like selector поля.
     *
     * @param string $type
     * @param array $field
     *
     * @return bool
     */
    private static function isUserLikeSelectorField(string $type, array $field): bool
    {
        if (in_array(trim($type), ['employee', 'user', 'employee_multiple'], true)) {
            return true;
        }

        if (trim($type) !== 'entity_selector') {
            return false;
        }

        $origin = is_array($field['origin'] ?? null) ? $field['origin'] : [];
        $userTypeId = strtolower(trim((string)($field['userTypeId'] ?? $origin['userTypeId'] ?? '')));
        $source = is_array($field['source'] ?? null) ? $field['source'] : [];
        $selectorEntities = is_array($field['selectorEntities'] ?? null) ? $field['selectorEntities'] : (is_array($source['entities'] ?? null) ? $source['entities'] : []);

        return in_array($userTypeId, ['employee'], true)
            || in_array(trim((string)($field['relationType'] ?? '')), ['user', 'employee'], true)
            || in_array('user', $selectorEntities, true);
    }

    /**
     * Извлекает настроек.
     *
     * @param array $field
     *
     * @return array
     */
    private static function extractOptions(array $field): array
    {
        $items = $field['items'] ?? null;
        if (!is_array($items)) {
            return [];
        }

        $options = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $value = isset($item['value']) ? (string)$item['value'] : (isset($item['id']) ? (string)$item['id'] : '');
                $label = isset($item['label']) ? (string)$item['label'] : (isset($item['title']) ? (string)$item['title'] : (isset($item['text']) ? (string)$item['text'] : $value));
            } else {
                $value = is_scalar($item) ? (string)$item : '';
                $label = $value;
            }
            if ($value === '') {
                continue;
            }
            $options[$value] = $label;
        }

        return $options;
    }

    /**
     * Извлекает схемы.
     *
     * @param array $form
     *
     * @return array
     */
    private static function extractSchema(array $form): array
    {
        $raw = $form['SCHEMA'] ?? null;
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || trim($raw) === '') {
            return ['fields' => [], 'settings' => []];
        }

        try {
            $decoded = Json::decode($raw);
        } catch (\Throwable) {
            $decoded = null;
        }

        return is_array($decoded) ? $decoded : ['fields' => [], 'settings' => []];
    }

    /**
     * Возвращает отправки строки.
     *
     * @param int $submissionId
     *
     * @return ?array
     */
    private static function getSubmissionRow(int $submissionId): ?array
    {
        if ($submissionId <= 0) {
            return null;
        }

        $row = FormSubmissionTable::getById($submissionId)->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * Извлекает формы ID.
     *
     * @param mixed $documentType
     *
     * @return int
     */
    private static function extractFormId(mixed $documentType): int
    {
        if (is_array($documentType)) {
            $documentType = $documentType[2] ?? '';
        }

        $code = trim((string)$documentType);
        if ($code === '') {
            return 0;
        }

        $form = FormTable::getList([
            'select' => ['ID'],
            'filter' => ['=CODE' => $code],
            'limit' => 1,
        ])->fetch();

        if ($form) {
            return (int)($form['ID'] ?? 0);
        }

        if (str_starts_with($code, self::DOCUMENT_TYPE_PREFIX)) {
            return (int)substr($code, strlen(self::DOCUMENT_TYPE_PREFIX));
        }

        return 0;
    }

    /**
     * Извлекает author ID.
     *
     * @param array $arFields
     *
     * @return int
     */
    private static function extractAuthorId(array $arFields): int
    {
        $value = $arFields['USER_ID'] ?? $arFields['AUTHOR_ID'] ?? 0;
        if (is_string($value) && str_starts_with($value, 'user_')) {
            return (int)substr($value, 5);
        }
        return (int)$value;
    }

    /**
     * Декодирует json.
     *
     * @param string $json
     * @param array $fallback
     *
     * @return array
     */
    private static function decodeJson(string $json, array $fallback): array
    {
        if ($json === '') {
            return $fallback;
        }
        try {
            $decoded = Json::decode($json);
        } catch (\Throwable) {
            $decoded = null;
        }
        return is_array($decoded) ? $decoded : $fallback;
    }

    /**
     * Кодирует json.
     *
     * @param mixed $value
     * @param string $fallback
     *
     * @return string
     */
    private static function encodeJson(mixed $value, string $fallback): string
    {
        try {
            $encoded = Json::encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable) {
            $encoded = false;
        }
        return is_string($encoded) && $encoded !== '' ? $encoded : $fallback;
    }

    /**
     * Преобразует дату в строку для бизнес-процесса.
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function dateToString(mixed $value): string
    {
        if ($value instanceof DateTime) {
            return $value->toString();
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        return '';
    }

    /**
     * Приводит значение к булевому виду.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int)$value > 0;
        }
        return in_array(mb_strtolower(trim((string)$value)), ['1', 'y', 'yes', 'true'], true);
    }
}
