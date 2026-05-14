<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис нормализации значений отправленной формы.
 */
final class RuntimeValueNormalizerService
{
    /**
     * Проверяет, поддерживает ли поле множественные значения.
     *
     * @param string $fieldType
     *
     * @return bool
     */
    public function fieldSupportsMultiple(string $fieldType): bool
    {
        return in_array(trim($fieldType) ?: 'string', [
            'enumeration',
            'checkbox_list',
            'file',
            'entity_selector',
            'user',
            'employee',
            'department',
            'hl_relation',
            'userfield',
        ], true);
    }

    /**
     * Определяет поля multiple.
     *
     * @param string $fieldType
     * @param mixed[] $flags
     *
     * @return bool
     */
    public function resolveFieldMultiple(string $fieldType, mixed ...$flags): bool
    {
        if (!$this->fieldSupportsMultiple($fieldType)) {
            return false;
        }

        foreach ($flags as $flag) {
            if ($this->toBooleanFlag($flag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Преобразует значение в булевый флаг.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function toBooleanFlag(mixed $value): bool
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

        $normalized = strtolower(trim((string)$value));

        return in_array($normalized, ['1', 'y', 'yes', 'true', 'on', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEVALUENORMALIZERSERVICE_001')], true);
    }

    /**
     * Определяет boolean flag.
     *
     * @param mixed $fieldValue
     * @param mixed $bindingValue
     *
     * @return bool
     */
    public function resolveBooleanFlag(mixed $fieldValue, mixed $bindingValue = null): bool
    {
        if ($this->toBooleanFlag($bindingValue)) {
            return true;
        }

        return $this->toBooleanFlag($fieldValue);
    }

    /**
     * Нормализует scalar значения.
     *
     * @param mixed $rawValue
     * @param bool $stripEntityPrefix
     *
     * @return string
     */
    public function normalizeScalarValue(mixed $rawValue, bool $stripEntityPrefix = true): string
    {
        if (is_array($rawValue)) {
            if (array_key_exists('id', $rawValue) || array_key_exists('value', $rawValue)) {
                $id = $rawValue['id'] ?? $rawValue['value'] ?? '';
                return is_scalar($id) ? trim((string)$id) : '';
            }

            foreach ($rawValue as $item) {
                $normalized = $this->normalizeScalarValue($item, $stripEntityPrefix);
                if ($normalized !== '') {
                    return $normalized;
                }
            }

            return '';
        }

        if ($rawValue === null) {
            return '';
        }

        if (is_bool($rawValue)) {
            return $rawValue ? '1' : '';
        }

        if (!is_scalar($rawValue)) {
            return '';
        }

        $stringValue = trim((string)$rawValue);
        if ($stripEntityPrefix && str_contains($stringValue, ':')) {
            $parts = explode(':', $stringValue, 2);
            return trim((string)($parts[1] ?? ''));
        }

        return $stringValue;
    }

    /**
     * Нормализует list значения.
     *
     * @param mixed $rawValue
     * @param bool $stripEntityPrefix
     *
     * @return array
     */
    public function normalizeListValue(mixed $rawValue, bool $stripEntityPrefix = true): array
    {
        $sourceList = is_array($rawValue) ? $rawValue : [$rawValue];
        $result = [];

        foreach ($sourceList as $item) {
            $normalized = $this->normalizeScalarValue($item, $stripEntityPrefix);
            if ($normalized === '') {
                continue;
            }
            $result[] = $normalized;
        }

        return array_values(array_unique($result));
    }

    /**
     * Нормализует comparable значения.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function normalizeComparableValue(mixed $value): string
    {
        if (is_array($value)) {
            return $this->normalizeScalarValue($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return '';
        }

        return trim((string)$value);
    }

    /**
     * Преобразует скалярное значение поля.
     *
     * @param mixed $rawValue
     * @param string $userTypeId
     * @param string $fieldType
     *
     * @return mixed
     */
    public function convertScalarValue(mixed $rawValue, string $userTypeId, string $fieldType): mixed
    {
        $value = is_scalar($rawValue) ? trim((string)$rawValue) : $this->normalizeScalarValue($rawValue);
        if ($value === '') {
            return null;
        }

        if (in_array($userTypeId, ['boolean', 'char'], true) || in_array($fieldType, ['boolean', 'checkbox', 'switch'], true)) {
            return $this->toBooleanFlag($rawValue) ? 'Y' : 'N';
        }

        if (in_array($userTypeId, ['integer', 'int', 'employee', 'user', 'crm_employee'], true)
            || in_array($fieldType, ['integer', 'user', 'employee', 'department', 'entity_selector'], true)) {
            $normalized = $this->normalizeScalarValue($rawValue);
            if (!ctype_digit($normalized) && preg_match('/(\d+)$/', $normalized, $matches)) {
                $normalized = (string)$matches[1];
            }
            return (int)$normalized;
        }

        if (in_array($userTypeId, ['double', 'float', 'money'], true) || in_array($fieldType, ['number', 'double', 'money'], true)) {
            $value = str_replace(',', '.', $value);
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        if ($fieldType === 'date' || $userTypeId === 'date') {
            return new Date($value);
        }

        if ($fieldType === 'datetime' || in_array($userTypeId, ['datetime', 'date_time'], true)) {
            return new DateTime($value);
        }

        return $value;
    }
}
