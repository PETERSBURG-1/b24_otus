<?php

namespace Rexp\Form\Application\Service;

use RuntimeException;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис валидации значений публичной формы.
 */
final class RuntimeFieldValidationService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param ?RuntimeRuleEvaluatorService $ruleEvaluator
     */
    public function __construct(private readonly ?RuntimeRuleEvaluatorService $ruleEvaluator = null)
    {
    }

    /**
     * Проверяет корректность and normalize.
     *
     * @param array $schema
     * @param array $values
     *
     * @return array
     */
    public function validateAndNormalize(array $schema, array $values): array
    {
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $normalized = [];
        $errors = [];
        $fieldStateMap = $this->ruleEvaluator instanceof RuntimeRuleEvaluatorService
            ? $this->ruleEvaluator->buildFieldStateMap($schema, $values)
            : [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $code = trim((string)($field['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $state = is_array($fieldStateMap[$code] ?? null) ? $fieldStateMap[$code] : [];
            $label = trim((string)($field['label'] ?? $field['title'] ?? $code)) ?: $code;
            $type = $this->resolveEffectiveType($field);
            $multiple = $this->isMultiple($field, $type);

            if (array_key_exists('visible', $state) && empty($state['visible'])) {
                if (!empty($state['clearOnHide']) || !empty($state['hasValueOverride'])) {
                    $normalized[$code] = $this->emptyValue($multiple);
                }
                continue;
            }

            $value = $this->resolveSubmittedValue($values, $field);
            if (!empty($state['hasValueOverride'])) {
                $value = $state['value'] ?? null;
            } elseif (!empty($state['disabled'])) {
                // Значения disabled-полей пользователь не должен менять вручную.
                // Оставляем только дефолтное значение из схемы, если оно задано.
                $value = array_key_exists('defaultValue', $field) ? $field['defaultValue'] : null;
            }

            $isRequired = array_key_exists('required', $state)
                ? !empty($state['required'])
                : (!empty($field['required']) || !empty($field['mandatory']));
            $isEmpty = $this->isEmptyValue($value);

            if ($isRequired && $isEmpty) {
                $errors[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_001') . $label . '».';
                continue;
            }

            if ($isEmpty) {
                $normalized[$code] = $this->emptyValue($multiple);
                continue;
            }

            try {
                $normalized[$code] = $this->normalizeValue($value, $type, $multiple, $field);
            } catch (RuntimeException $exception) {
                $errors[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_002') . $label . '»: ' . $exception->getMessage();
            }
        }

        if ($errors !== []) {
            throw new RuntimeException(implode("\n", $errors));
        }

        return $normalized;
    }

    /**
     * Фильтрует отправленные значения по схеме формы.
     *
     * @param array $schema
     * @param array $values
     *
     * @return array
     */
    public function filterSubmittedValues(array $schema, array $values): array
    {
        $result = [];
        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }
            $code = trim((string)($field['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $value = $this->resolveSubmittedValue($values, $field, false);
            if ($value === null && !array_key_exists($code, $values)) {
                continue;
            }
            $result[$code] = $value;
        }

        return $result;
    }

    /**
     * Проверяет признак empty значения.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (!$this->isEmptyValue($item)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**

     * Проверяет, является ли значение пустым.

     *

     * @param bool $multiple

     *

     * @return mixed

     */

    private function emptyValue(bool $multiple): mixed
    {
        return $multiple ? [] : null;
    }

    /**
     * Определяет submitted значения.
     *
     * @param array $values
     * @param array $field
     * @param bool $withDefault
     *
     * @return mixed
     */
    private function resolveSubmittedValue(array $values, array $field, bool $withDefault = true): mixed
    {
        $code = trim((string)($field['code'] ?? ''));
        if ($code !== '' && array_key_exists($code, $values)) {
            return $values[$code];
        }

        $origin = is_array($field['origin'] ?? null) ? $field['origin'] : [];
        foreach (['fieldName', 'FIELD_NAME'] as $originKey) {
            $originCode = trim((string)($origin[$originKey] ?? ''));
            if ($originCode !== '' && array_key_exists($originCode, $values)) {
                return $values[$originCode];
            }
        }

        if ($withDefault && array_key_exists('defaultValue', $field)) {
            return $field['defaultValue'];
        }

        return null;
    }

    /**
     * Проверяет признак multiple.
     *
     * @param array $field
     * @param ?string $resolvedType
     *
     * @return bool
     */
    private function isMultiple(array $field, ?string $resolvedType = null): bool
    {
        $type = $resolvedType ?: $this->resolveEffectiveType($field);
        $view = is_array($field['ui'] ?? null) ? trim((string)($field['ui']['view'] ?? '')) : '';
        $userFieldMultiple = strtoupper(trim((string)($field['MULTIPLE'] ?? $field['settings']['MULTIPLE'] ?? 'N'))) === 'Y';

        return !empty($field['multiple'])
            || $userFieldMultiple
            || $type === 'file'
            || ($type === 'enumeration' && in_array($view, ['checkbox', 'checkboxes', 'tag_input'], true))
            || ($type === 'entity_selector' && !empty($field['multiple']))
            || trim((string)($field['legacyType'] ?? '')) === 'checkbox_list';
    }

    /**
     * Нормализует значения.
     *
     * @param mixed $value
     * @param string $type
     * @param bool $multiple
     * @param array $field
     *
     * @return mixed
     */
    private function normalizeValue(mixed $value, string $type, bool $multiple, array $field): mixed
    {
        if ($multiple) {
            $items = $this->normalizeInputList($value);
            $result = [];
            foreach ($items as $item) {
                if ($this->isEmptyValue($item)) {
                    continue;
                }
                $result[] = $this->normalizeSingleValue($item, $type, $field);
            }

            return array_values(array_unique($result, SORT_REGULAR));
        }

        return $this->normalizeSingleValue($value, $type, $field);
    }

    /**
     * Нормализует single значения.
     *
     * @param mixed $value
     * @param string $type
     * @param array $field
     *
     * @return mixed
     */
    private function normalizeSingleValue(mixed $value, string $type, array $field): mixed
    {
        if (is_array($value) && (array_key_exists('id', $value) || array_key_exists('value', $value))) {
            $value = $value['id'] ?? $value['value'] ?? '';
        }

        return match ($type) {
            'integer' => $this->normalizeInteger($value),
            'number', 'double', 'money' => $this->normalizeNumber($value),
            'checkbox', 'switch', 'boolean' => $this->toBoolean($value),
            'date', 'datetime' => $this->normalizeDateValue($value),
            'user', 'employee', 'department', 'entity_selector' => $this->normalizeEntityValue($value, $field),
            'file' => $this->normalizePositiveInt($value),
            'email' => $this->normalizeEmail($value),
            'phone' => $this->normalizePhone($value),
            'url' => $this->normalizeUrl($value),
            'enumeration' => $this->normalizeEnumerationValue($value, $field),
            default => $this->normalizeFallbackValue($value),
        };
    }

    /**
     * Нормализует input list.
     *
     * @param mixed $value
     *
     * @return array
     */
    private function normalizeInputList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            return [$value];
        }

        if (array_key_exists('id', $value) || array_key_exists('value', $value)) {
            return [$value];
        }

        return array_values($value);
    }

    /**
     * Нормализует number.
     *
     * @param mixed $value
     *
     * @return int|float
     */
    private function normalizeNumber(mixed $value): int|float
    {
        $value = str_replace([' ', ','], ['', '.'], trim((string)$value));
        if (!is_numeric($value)) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_003'));
        }

        return str_contains($value, '.') ? (float)$value : (int)$value;
    }

    /**
     * Нормализует integer.
     *
     * @param mixed $value
     *
     * @return int
     */
    private function normalizeInteger(mixed $value): int
    {
        $value = trim((string)$value);
        if (!preg_match('/^-?\d+$/', $value)) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_004'));
        }

        return (int)$value;
    }

    /**
     * Нормализует date значения.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function normalizeDateValue(mixed $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        return $value;
    }

    /**
     * Нормализует positive int.
     *
     * @param mixed $value
     *
     * @return int
     */
    private function normalizePositiveInt(mixed $value): int
    {
        if (is_array($value) && (array_key_exists('id', $value) || array_key_exists('value', $value))) {
            $value = $value['id'] ?? $value['value'] ?? 0;
        }
        $id = (int)$value;
        if ($id <= 0) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_005'));
        }

        return $id;
    }

    /**
     * Нормализует сущности значения.
     *
     * @param mixed $value
     * @param array $field
     *
     * @return int|string
     */
    private function normalizeEntityValue(mixed $value, array $field): int|string
    {
        if (is_array($value) && (array_key_exists('id', $value) || array_key_exists('value', $value))) {
            $value = $value['id'] ?? $value['value'] ?? '';
        }

        $stringValue = trim((string)$value);
        if ($stringValue === '') {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_006'));
        }

        $relationType = trim((string)($field['relationType'] ?? ''));
        $origin = is_array($field['origin'] ?? null) ? $field['origin'] : [];
        $userTypeId = strtolower(trim((string)($field['userTypeId'] ?? $origin['userTypeId'] ?? '')));
        $selectorEntities = $this->extractSelectorEntities($field);
        $mustBeNumeric = in_array($relationType, ['user', 'employee', 'department'], true)
            || in_array($userTypeId, ['employee'], true)
            || ($selectorEntities !== [] && count(array_diff($selectorEntities, ['user', 'department', 'structure-node', 'meta-user'])) === 0);

        if ($mustBeNumeric) {
            $numericId = $this->extractLastNumericId($stringValue);
            if ($numericId <= 0) {
                throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_007'));
            }
            return $numericId;
        }

        if (ctype_digit($stringValue)) {
            return (int)$stringValue;
        }

        return $stringValue;
    }

    /**
     * Приводит значение настройки к булевому виду.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function toBoolean(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Y' : 'N';
        }

        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 'y', 'yes', 'true', 'on', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_008')], true) ? 'Y' : 'N';
    }

    /**

     * Нормализует email.

     *

     * @param mixed $value

     *

     * @return string

     */

    private function normalizeEmail(mixed $value): string
    {
        $value = trim((string)$value);
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_009'));
        }

        return $value;
    }

    /**

     * Нормализует URL.

     *

     * @param mixed $value

     *

     * @return string

     */

    private function normalizeUrl(mixed $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (!preg_match('/^https?:\/\//i', $value)) {
            $value = 'https://' . $value;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_010'));
        }

        return $value;
    }

    /**

     * Нормализует phone.

     *

     * @param mixed $value

     *

     * @return string

     */

    private function normalizePhone(mixed $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if (mb_strlen($digits) < 5) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_011'));
        }

        return $value;
    }

    /**
     * Нормализует enumeration значения.
     *
     * @param mixed $value
     * @param array $field
     *
     * @return string|int
     */
    private function normalizeEnumerationValue(mixed $value, array $field): string|int
    {
        $view = is_array($field['ui'] ?? null) ? trim((string)($field['ui']['view'] ?? '')) : '';
        if ($view === 'entity_selector') {
            return $this->normalizeEntityValue($value, $field);
        }

        if (is_array($value) && (array_key_exists('id', $value) || array_key_exists('value', $value))) {
            $value = $value['value'] ?? $value['id'] ?? '';
        }

        $value = is_scalar($value) ? trim((string)$value) : '';
        if ($value === '') {
            return '';
        }

        $items = is_array($field['items'] ?? null) ? $field['items'] : [];
        $allowCustomValues = !empty($field['settings']['allowCustomValues']) || $view === 'tag_input';
        if ($items === [] || $allowCustomValues) {
            return $value;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $available = array_filter([
                trim((string)($item['id'] ?? '')),
                trim((string)($item['value'] ?? '')),
                trim((string)($item['xmlId'] ?? $item['XML_ID'] ?? '')),
            ], static fn(string $itemValue): bool => $itemValue !== '');
            if (in_array($value, $available, true)) {
                return $value;
            }
        }

        throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFIELDVALIDATIONSERVICE_012'));
    }

    /**

     * Нормализует fallback значения.

     *

     * @param mixed $value

     *

     * @return mixed

     */

    private function normalizeFallbackValue(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return is_string($value) ? trim($value) : $value;
        }

        if (is_array($value) && (array_key_exists('id', $value) || array_key_exists('value', $value))) {
            return trim((string)($value['value'] ?? $value['id'] ?? ''));
        }

        return $value;
    }

    /**
     * Определяет effective типа.
     *
     * @param array $field
     *
     * @return string
     */
    private function resolveEffectiveType(array $field): string
    {
        $type = $this->normalizeType(trim((string)($field['type'] ?? 'string')) ?: 'string');
        $origin = is_array($field['origin'] ?? null) ? $field['origin'] : [];
        $userTypeId = strtolower(trim((string)($field['userTypeId'] ?? $origin['userTypeId'] ?? '')));

        if ($type !== 'userfield') {
            return $type;
        }

        return match ($userTypeId) {
            'integer', 'int' => 'integer',
            'double', 'float' => 'double',
            'boolean' => 'boolean',
            'enumeration', 'enum' => 'enumeration',
            'date' => 'date',
            'datetime', 'date_time' => 'datetime',
            'file' => 'file',
            'url' => 'url',
            'employee', 'crm', 'iblock_element', 'iblock_section' => 'entity_selector',
            'money' => 'money',
            default => 'string',
        };
    }

    /**

     * Нормализует типа.

     *

     * @param string $type

     *

     * @return string

     */

    private function normalizeType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'number', 'float', 'decimal' => 'double',
            'int' => 'integer',
            'checkbox', 'switch', 'bool' => 'boolean',
            'select', 'radio', 'checkbox_list', 'list', 'enum' => 'enumeration',
            'user', 'employee', 'department', 'crm', 'iblock_element', 'iblock_section' => 'entity_selector',
            default => strtolower(trim($type)) ?: 'string',
        };
    }

    /**
     * Извлекает selector entities.
     *
     * @param array $field
     *
     * @return array
     */
    private function extractSelectorEntities(array $field): array
    {
        $source = is_array($field['source'] ?? null) ? $field['source'] : [];
        $entities = is_array($field['selectorEntities'] ?? null) ? $field['selectorEntities'] : (is_array($source['entities'] ?? null) ? $source['entities'] : []);

        return array_values(array_filter(array_map(static fn($entity): string => trim((string)$entity), $entities)));
    }

    /**

     * Извлекает last numeric ID.

     *

     * @param string $value

     *

     * @return int

     */

    private function extractLastNumericId(string $value): int
    {
        if (ctype_digit($value)) {
            return (int)$value;
        }

        if (preg_match('/(\d+)$/', $value, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }
}
