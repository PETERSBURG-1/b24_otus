<?php

namespace Rexp\Form\Application\Service;

/**
 * Сервис применения runtime-правил к значениям формы.
 */
final class RuntimeRuleEvaluatorService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param RuntimeConditionEvaluatorService $conditionEvaluator
     * @param RuntimeValueNormalizerService $normalizer
     */
    public function __construct(
        private readonly RuntimeConditionEvaluatorService $conditionEvaluator,
        private readonly RuntimeValueNormalizerService $normalizer,
    ) {
    }

    /**
     * Нормализует правил.
     *
     * @param array $schema
     *
     * @return array
     */
    public function normalizeRules(array $schema): array
    {
        $rules = $this->extractRuntimeRules($schema);
        $fieldsMap = $this->buildFieldsMap($schema);
        $result = [];

        foreach ($rules as $index => $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $conditions = [];
            foreach ((array)($rule['conditions'] ?? []) as $conditionItem) {
                if (!is_array($conditionItem)) {
                    continue;
                }
                $item = $this->normalizeRuleCondition($conditionItem, $fieldsMap);
                if (trim((string)($item['fieldCode'] ?? '')) !== '') {
                    $conditions[] = $item;
                }
            }

            if ($conditions === [] && is_array($rule['when'] ?? null)) {
                $item = $this->normalizeRuleCondition($rule['when'], $fieldsMap);
                if (trim((string)($item['fieldCode'] ?? '')) !== '') {
                    $conditions[] = $item;
                }
            }

            $actions = [];
            foreach ((array)($rule['actions'] ?? []) as $action) {
                if (!is_array($action)) {
                    continue;
                }
                $type = $this->normalizeActionType(trim((string)($action['type'] ?? $action['action'] ?? $action['actionType'] ?? '')));
                if ($type === '') {
                    continue;
                }
                $fieldCode = trim((string)($action['fieldCode'] ?? $action['field'] ?? $action['targetFieldCode'] ?? $action['targetField'] ?? ''));
                if ($fieldCode === '') {
                    continue;
                }
                $actions[] = [
                    'type' => $type,
                    'fieldCode' => $fieldCode,
                    'field' => $fieldCode,
                    'value' => $action['value'] ?? $action['valueToSet'] ?? $action['setValue'] ?? '',
                    'clearOnHide' => !empty($action['clearOnHide']) || $type === 'clear_value',
                ];
            }

            if ($conditions === [] || $actions === []) {
                continue;
            }

            $result[] = [
                'uid' => trim((string)($rule['uid'] ?? $rule['id'] ?? '')) ?: ('rule_' . ($index + 1)),
                'enabled' => array_key_exists('enabled', $rule) ? !empty($rule['enabled']) : true,
                'event' => trim((string)($rule['event'] ?? 'change')) ?: 'change',
                'logic' => strtolower(trim((string)($rule['logic'] ?? 'and'))) === 'or' ? 'or' : 'and',
                'when' => $conditions[0],
                'conditions' => $conditions,
                'actions' => $actions,
            ];
        }

        return $result;
    }

    /**
     * Формирует поля state карты.
     *
     * @param array $schema
     * @param array $values
     *
     * @return array
     */
    public function buildFieldStateMap(array $schema, array $values): array
    {
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $rules = $this->normalizeRules($schema);
        $stateMap = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $fieldCode = trim((string)($field['code'] ?? ''));
            if ($fieldCode === '') {
                continue;
            }
            $stateMap[$fieldCode] = [
                'visible' => $this->conditionEvaluator->isFieldVisible($field, $values),
                'required' => !empty($field['required']) || !empty($field['mandatory']),
                'disabled' => !empty($field['disabled']),
                'clearOnHide' => false,
                'hasValueOverride' => false,
                'value' => null,
            ];
        }

        foreach ($rules as $rule) {
            if (empty($rule['enabled'])) {
                continue;
            }

            $matched = $this->isRuleMatched($rule, $values);

            foreach ((array)($rule['actions'] ?? []) as $action) {
                $fieldCode = trim((string)($action['fieldCode'] ?? $action['field'] ?? ''));
                if ($fieldCode === '' || !isset($stateMap[$fieldCode])) {
                    continue;
                }

                $actionType = $this->normalizeActionType((string)($action['type'] ?? $action['action'] ?? $action['actionType'] ?? ''));
                if ($actionType === 'show') {
                    $stateMap[$fieldCode]['visible'] = $matched;
                    continue;
                }

                if ($actionType === 'hide') {
                    $stateMap[$fieldCode]['visible'] = !$matched;
                    if ($matched) {
                        $stateMap[$fieldCode]['clearOnHide'] = !empty($action['clearOnHide']);
                    }
                    continue;
                }

                if (!$matched) {
                    continue;
                }

                switch ($actionType) {
                    case 'require':
                        $stateMap[$fieldCode]['required'] = true;
                        break;
                    case 'unrequire':
                        $stateMap[$fieldCode]['required'] = false;
                        break;
                    case 'set_value':
                        $stateMap[$fieldCode]['hasValueOverride'] = true;
                        $stateMap[$fieldCode]['value'] = $action['value'] ?? '';
                        break;
                    case 'clear_value':
                        $stateMap[$fieldCode]['hasValueOverride'] = true;
                        $stateMap[$fieldCode]['value'] = '';
                        $stateMap[$fieldCode]['clearOnHide'] = true;
                        break;
                    case 'disable':
                        $stateMap[$fieldCode]['disabled'] = true;
                        break;
                    case 'enable':
                        $stateMap[$fieldCode]['disabled'] = false;
                        break;
                }
            }
        }

        return $stateMap;
    }

    /**
     * Проверяет признак поля visible.
     *
     * @param array $field
     * @param array $schema
     * @param array $values
     *
     * @return bool
     */
    public function isFieldVisible(array $field, array $schema, array $values): bool
    {
        $fieldCode = trim((string)($field['code'] ?? ''));
        if ($fieldCode === '') {
            return false;
        }
        $stateMap = $this->buildFieldStateMap($schema, $values);
        return !empty($stateMap[$fieldCode]['visible']);
    }

    /**
     * Проверяет признак поля required.
     *
     * @param array $field
     * @param array $schema
     * @param array $values
     * @param bool $defaultRequired
     *
     * @return bool
     */
    public function isFieldRequired(array $field, array $schema, array $values, bool $defaultRequired = false): bool
    {
        $fieldCode = trim((string)($field['code'] ?? ''));
        if ($fieldCode === '') {
            return $defaultRequired;
        }
        $stateMap = $this->buildFieldStateMap($schema, $values);
        return array_key_exists($fieldCode, $stateMap)
            ? !empty($stateMap[$fieldCode]['required'])
            : $defaultRequired;
    }

    /**
     * Определяет поля значения override.
     *
     * @param array $field
     * @param array $schema
     * @param array $values
     *
     * @return mixed
     */
    public function resolveFieldValueOverride(array $field, array $schema, array $values): mixed
    {
        $fieldCode = trim((string)($field['code'] ?? ''));
        if ($fieldCode === '') {
            return null;
        }
        $stateMap = $this->buildFieldStateMap($schema, $values);
        if (!array_key_exists($fieldCode, $stateMap) || empty($stateMap[$fieldCode]['hasValueOverride'])) {
            return null;
        }
        return $stateMap[$fieldCode]['value'] ?? '';
    }

    /**
     * Извлекает публичной формы правил.
     *
     * @param array $schema
     *
     * @return array
     */
    private function extractRuntimeRules(array $schema): array
    {
        if (is_array($schema['compiledRules']['rules'] ?? null) && $schema['compiledRules']['rules'] !== []) {
            return $schema['compiledRules']['rules'];
        }

        if (is_array($schema['compiledRules'] ?? null) && array_is_list($schema['compiledRules']) && $schema['compiledRules'] !== []) {
            return $schema['compiledRules'];
        }

        return is_array($schema['rules'] ?? null) ? $schema['rules'] : [];
    }

    /**
     * Нормализует правила condition.
     *
     * @param array $condition
     * @param array $fieldsMap
     *
     * @return array
     */
    private function normalizeRuleCondition(array $condition, array $fieldsMap): array
    {
        $normalizedCondition = $this->conditionEvaluator->normalizeFieldCondition($condition);
        $sourceFieldCode = trim((string)($normalizedCondition['fieldCode'] ?? ''));
        if ($sourceFieldCode !== '' && ($normalizedCondition['compareBy'] ?? 'value') === 'xmlId') {
            $normalizedCondition['sourceItems'] = is_array($fieldsMap[$sourceFieldCode]['items'] ?? null)
                ? $fieldsMap[$sourceFieldCode]['items']
                : [];
        }

        return $normalizedCondition;
    }

    /**
     * Проверяет признак правила matched.
     *
     * @param array $rule
     * @param array $values
     *
     * @return bool
     */
    private function isRuleMatched(array $rule, array $values): bool
    {
        $conditions = is_array($rule['conditions'] ?? null) ? $rule['conditions'] : [];
        $logic = strtolower(trim((string)($rule['logic'] ?? 'and'))) === 'or' ? 'or' : 'and';
        if ($conditions === []) {
            return true;
        }

        $matchedCount = 0;
        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $fieldCode = trim((string)($condition['fieldCode'] ?? $condition['field'] ?? ''));
            $matched = $fieldCode === ''
                ? true
                : $this->conditionEvaluator->evaluateCondition($condition, $values[$fieldCode] ?? null);

            if ($logic === 'or' && $matched) {
                return true;
            }

            if ($logic === 'and' && !$matched) {
                return false;
            }

            if ($matched) {
                $matchedCount++;
            }
        }

        return $logic === 'and' ? true : $matchedCount > 0;
    }

    /**
     * Формирует полей карты.
     *
     * @param array $schema
     *
     * @return array
     */
    private function buildFieldsMap(array $schema): array
    {
        $result = [];
        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }
            $fieldCode = trim((string)($field['code'] ?? ''));
            if ($fieldCode === '') {
                continue;
            }
            $result[$fieldCode] = $field;
        }
        return $result;
    }

    /**

     * Нормализует действия типа.

     *

     * @param string $type

     *

     * @return string

     */

    private function normalizeActionType(string $type): string
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'show', 'hide', 'require', 'unrequire', 'set_value', 'clear_value', 'disable', 'enable' => $type,
            'required' => 'require',
            'optional' => 'unrequire',
            'clear' => 'clear_value',
            'set' => 'set_value',
            default => '',
        };
    }
}
