<?php

namespace Rexp\Form\Application\Service;

/**
 * Сервис проверки условий runtime-правил.
 */
final class RuntimeConditionEvaluatorService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param RuntimeValueNormalizerService $normalizer
     */
    public function __construct(
        private readonly RuntimeValueNormalizerService $normalizer,
    ) {
    }

    /**
     * Нормализует поля condition.
     *
     * @param mixed $condition
     *
     * @return array
     */
    public function normalizeFieldCondition(mixed $condition): array
    {
        $condition = is_array($condition) ? $condition : [];
        $operator = $this->normalizeOperator(trim((string)($condition['operator'] ?? 'equals')) ?: 'equals');

        $values = $condition['values'] ?? [];
        if (!is_array($values)) {
            $values = ($condition['value'] ?? null) !== null && ($condition['value'] ?? '') !== ''
                ? [$condition['value']]
                : [];
        }

        $fieldCode = trim((string)($condition['fieldCode'] ?? $condition['field'] ?? $condition['sourceField'] ?? ''));

        return [
            'enabled' => array_key_exists('enabled', $condition) ? !empty($condition['enabled']) : true,
            'action' => 'show',
            'fieldCode' => $fieldCode,
            'field' => $fieldCode,
            'operator' => $operator,
            'compareBy' => trim((string)($condition['compareBy'] ?? 'value')) === 'xmlId' ? 'xmlId' : 'value',
            'value' => $condition['value'] ?? '',
            'values' => array_values($values),
            'sourceItems' => is_array($condition['sourceItems'] ?? null) ? $condition['sourceItems'] : [],
        ];
    }

    /**
     * Проверяет признак поля visible.
     *
     * @param array $field
     * @param array $values
     *
     * @return bool
     */
    public function isFieldVisible(array $field, array $values): bool
    {
        $condition = $this->normalizeFieldCondition($field['condition'] ?? []);
        if (empty($condition['enabled'])) {
            return true;
        }

        $sourceFieldCode = trim((string)($condition['fieldCode'] ?? ''));
        if ($sourceFieldCode === '') {
            return true;
        }

        return $this->evaluateCondition($condition, $values[$sourceFieldCode] ?? null);
    }

    /**
     * Проверяет условие runtime-правила.
     *
     * @param array $condition
     * @param mixed $sourceValue
     *
     * @return bool
     */
    public function evaluateCondition(array $condition, mixed $sourceValue): bool
    {
        $condition = $this->normalizeFieldCondition($condition);
        $operator = (string)($condition['operator'] ?? '=');
        $expectedValue = $condition['value'] ?? '';

        return match ($operator) {
            'filled' => !$this->isEmptySource($sourceValue),
            'not_filled' => $this->isEmptySource($sourceValue),
            '!=' => !$this->conditionValueEquals($sourceValue, $expectedValue, $condition),
            'in' => $this->conditionValueIn($sourceValue, $condition),
            'not_in' => !$this->conditionValueIn($sourceValue, $condition),
            '>' => $this->compareNumbersOrDates($sourceValue, $expectedValue, $condition, '>'),
            '>=' => $this->compareNumbersOrDates($sourceValue, $expectedValue, $condition, '>='),
            '<' => $this->compareNumbersOrDates($sourceValue, $expectedValue, $condition, '<'),
            '<=' => $this->compareNumbersOrDates($sourceValue, $expectedValue, $condition, '<='),
            'contains' => $this->conditionContains($sourceValue, $expectedValue, $condition),
            'not_contains' => !$this->conditionContains($sourceValue, $expectedValue, $condition),
            default => $this->conditionValueEquals($sourceValue, $expectedValue, $condition),
        };
    }

    /**
     * Нормализует operator.
     *
     * @param string $operator
     *
     * @return string
     */
    private function normalizeOperator(string $operator): string
    {
        $operator = strtolower(trim($operator));

        return match ($operator) {
            'equals', 'equal', 'eq', '==', '=' => '=',
            'not_equals', 'not_equal', 'neq', '<>', '!=' => '!=',
            'filled', 'not_empty' => 'filled',
            'not_filled', 'empty' => 'not_filled',
            'in' => 'in',
            'not_in' => 'not_in',
            'contain', 'contains' => 'contains',
            'not_contain', 'not_contains' => 'not_contains',
            '>', '>=', '<', '<=' => $operator,
            default => '=',
        };
    }

    /**
     * Проверяет признак empty source.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function isEmptySource(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($item !== '' && $item !== null && $item !== false) {
                    return false;
                }
            }

            return true;
        }

        if (is_bool($value)) {
            return $value === false;
        }

        return $value === null || $value === '';
    }

    /**
     * Проверяет равенство значения условию.
     *
     * @param mixed $sourceValue
     * @param mixed $expectedValue
     * @param array $condition
     *
     * @return bool
     */
    private function conditionValueEquals(mixed $sourceValue, mixed $expectedValue, array $condition = []): bool
    {
        $actualValues = $this->resolveComparableSourceValues($sourceValue, $condition);
        $expected = $this->normalizer->normalizeComparableValue($expectedValue);
        return in_array($expected, $actualValues, true);
    }

    /**
     * Проверяет вхождение значения в список условия.
     *
     * @param mixed $sourceValue
     * @param array $condition
     *
     * @return bool
     */
    private function conditionValueIn(mixed $sourceValue, array $condition): bool
    {
        $actualValues = $this->resolveComparableSourceValues($sourceValue, $condition);
        foreach ((array)($condition['values'] ?? []) as $expectedValue) {
            $expected = $this->normalizer->normalizeComparableValue($expectedValue);
            if (in_array($expected, $actualValues, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверяет наличие подстроки или элемента в значении.
     *
     * @param mixed $sourceValue
     * @param mixed $expectedValue
     * @param array $condition
     *
     * @return bool
     */
    private function conditionContains(mixed $sourceValue, mixed $expectedValue, array $condition): bool
    {
        $expected = strtolower($this->normalizer->normalizeComparableValue($expectedValue));
        if ($expected === '') {
            return false;
        }

        foreach ($this->resolveComparableSourceValues($sourceValue, $condition) as $actual) {
            if (str_contains(strtolower($actual), $expected)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Сравнивает числовые значения или даты.
     *
     * @param mixed $sourceValue
     * @param mixed $expectedValue
     * @param array $condition
     * @param string $operator
     *
     * @return bool
     */
    private function compareNumbersOrDates(mixed $sourceValue, mixed $expectedValue, array $condition, string $operator): bool
    {
        $actualValues = $this->resolveComparableSourceValues($sourceValue, $condition);
        $expected = $this->normalizeComparableNumberOrDate($expectedValue);
        if ($expected === null) {
            return false;
        }

        foreach ($actualValues as $actualValue) {
            $actual = $this->normalizeComparableNumberOrDate($actualValue);
            if ($actual === null) {
                continue;
            }

            if (match ($operator) {
                '>' => $actual > $expected,
                '>=' => $actual >= $expected,
                '<' => $actual < $expected,
                '<=' => $actual <= $expected,
                default => false,
            }) {
                return true;
            }
        }

        return false;
    }

    /**

     * Нормализует comparable number or date.

     *

     * @param mixed $value

     *

     * @return int|float|null

     */

    private function normalizeComparableNumberOrDate(mixed $value): int|float|null
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $numberValue = str_replace(',', '.', $value);
        if (is_numeric($numberValue)) {
            return str_contains($numberValue, '.') ? (float)$numberValue : (int)$numberValue;
        }

        $timestamp = strtotime($value);
        return $timestamp !== false ? $timestamp : null;
    }

    /**
     * Определяет comparable source значений.
     *
     * @param mixed $sourceValue
     * @param array $condition
     *
     * @return array
     */
    private function resolveComparableSourceValues(mixed $sourceValue, array $condition): array
    {
        if (($condition['compareBy'] ?? 'value') !== 'xmlId') {
            if (is_array($sourceValue)) {
                return array_values(array_map(fn($item) => $this->normalizer->normalizeComparableValue($item), $sourceValue));
            }
            return [$this->normalizer->normalizeComparableValue($sourceValue)];
        }

        $sourceItems = is_array($condition['sourceItems'] ?? null) ? $condition['sourceItems'] : [];
        $map = [];
        foreach ($sourceItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $valueKey = $this->normalizer->normalizeComparableValue($item['value'] ?? '');
            if ($valueKey === '') {
                continue;
            }
            $map[$valueKey] = $this->normalizer->normalizeComparableValue($item['xmlId'] ?? $item['value'] ?? '');
        }

        $sourceList = is_array($sourceValue) ? $sourceValue : [$sourceValue];
        $result = [];
        foreach ($sourceList as $item) {
            $valueKey = $this->normalizer->normalizeComparableValue($item);
            if ($valueKey === '') {
                continue;
            }
            $result[] = $map[$valueKey] ?? $valueKey;
        }

        return array_values(array_unique($result));
    }
}
