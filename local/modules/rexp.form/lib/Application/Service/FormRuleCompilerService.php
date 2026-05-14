<?php

namespace Rexp\Form\Application\Service;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис компиляции редакторских правил формы в runtime-правила.
 */
final class FormRuleCompilerService
{
    private const OPERATOR_ALIASES = [
        'equals' => '=',
        'equal' => '=',
        'eq' => '=',
        '==' => '=',
        'not_equals' => '!=',
        'not_equal' => '!=',
        'neq' => '!=',
        '<>' => '!=',
        'filled' => 'filled',
        'not_filled' => 'not_filled',
        'empty' => 'not_filled',
        'not_empty' => 'filled',
        'in' => 'in',
        'not_in' => 'not_in',
        'contain' => 'contains',
        'contains' => 'contains',
        'not_contain' => 'not_contains',
        'not_contains' => 'not_contains',
        '>' => '>',
        '>=' => '>=',
        '<' => '<',
        '<=' => '<=',
    ];

    private const ACTION_ALIASES = [
        'show' => 'show',
        'hide' => 'hide',
        'require' => 'require',
        'required' => 'require',
        'unrequire' => 'unrequire',
        'optional' => 'unrequire',
        'clear' => 'clear_value',
        'clear_value' => 'clear_value',
        'set' => 'set_value',
        'set_value' => 'set_value',
        'disable' => 'disable',
        'enable' => 'enable',
    ];

    /**
     * Компилирует правила схемы формы в runtime-структуру.
     *
     * @param array $schema
     *
     * @return array
     */
    public function compileSchema(array $schema): array
    {
        $fieldCodes = $this->extractFieldCodes($schema);
        $ruleGroups = is_array($schema['ruleGroups'] ?? null) ? $schema['ruleGroups'] : [];
        $rules = [];

        if ($ruleGroups !== []) {
            $rules = $this->compileRuleGroups($ruleGroups, $fieldCodes);
        } else {
            $rules = $this->normalizeRuntimeRules(
                is_array($schema['compiledRules']['rules'] ?? null)
                    ? $schema['compiledRules']['rules']
                    : (is_array($schema['rules'] ?? null) ? $schema['rules'] : []),
                $fieldCodes
            );
        }

        $schema['ruleGroups'] = $this->normalizeRuleGroups($ruleGroups, $fieldCodes);
        $schema['compiledRules'] = [
            'schemaVersion' => (int)($schema['schemaVersion'] ?? $schema['version'] ?? 2),
            'rules' => $rules,
            'dependsOn' => $this->buildDependsOn($rules),
        ];

        // Старый public runtime уже читает schema.rules, поэтому держим совместимый плоский контракт.
        $schema['rules'] = $rules;

        return $schema;
    }

    /**
     * Компилирует группы правил редактора в runtime-правила.
     *
     * @param array $groups
     * @param array $fieldCodes
     *
     * @return array
     */
    public function compileRuleGroups(array $groups, array $fieldCodes = []): array
    {
        $rules = [];
        foreach ($groups as $groupIndex => $group) {
            if (!is_array($group)) {
                continue;
            }

            if (array_key_exists('enabled', $group) && !$this->toBool($group['enabled'])) {
                continue;
            }

            $groupId = $this->normalizeId((string)($group['id'] ?? $group['uid'] ?? 'group_' . ($groupIndex + 1)));
            $groupLogic = $this->normalizeLogic((string)($group['logic'] ?? 'and'));
            $sourceField = trim((string)($group['sourceField'] ?? $group['sourceFieldCode'] ?? $group['fieldCode'] ?? $group['field'] ?? ''));
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];

            if ($rows === [] && is_array($group['conditions'] ?? null)) {
                $rows[] = [
                    'id' => $groupId . '_row_1',
                    'conditions' => $group['conditions'],
                    'actions' => $group['actions'] ?? [],
                ];
            }

            foreach ($rows as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (array_key_exists('enabled', $row) && !$this->toBool($row['enabled'])) {
                    continue;
                }

                $conditions = $this->normalizeRowConditions($row, $sourceField, $fieldCodes);
                $actions = $this->resolveRowActions($group, $row, $fieldCodes);

                if ($conditions === [] || $actions === []) {
                    continue;
                }

                $rules[] = [
                    'uid' => $this->normalizeId((string)($row['id'] ?? $row['uid'] ?? $groupId . '_rule_' . ($rowIndex + 1))),
                    'enabled' => true,
                    'event' => trim((string)($row['event'] ?? $group['event'] ?? 'change')) ?: 'change',
                    'logic' => $this->normalizeLogic((string)($row['logic'] ?? $groupLogic)),
                    'conditions' => $conditions,
                    'actions' => $actions,
                    'groupId' => $groupId,
                ];
            }
        }

        return $rules;
    }

    /**
     * Нормализует публичной формы правил.
     *
     * @param array $rules
     * @param array $fieldCodes
     *
     * @return array
     */
    public function normalizeRuntimeRules(array $rules, array $fieldCodes = []): array
    {
        $result = [];
        foreach ($rules as $index => $rule) {
            if (!is_array($rule)) {
                continue;
            }
            if (array_key_exists('enabled', $rule) && !$this->toBool($rule['enabled'])) {
                continue;
            }

            $conditions = [];
            foreach ((array)($rule['conditions'] ?? []) as $condition) {
                $normalized = $this->normalizeCondition(is_array($condition) ? $condition : [], '', $fieldCodes);
                if ($normalized !== null) {
                    $conditions[] = $normalized;
                }
            }

            if ($conditions === [] && is_array($rule['when'] ?? null)) {
                $normalized = $this->normalizeCondition($rule['when'], '', $fieldCodes);
                if ($normalized !== null) {
                    $conditions[] = $normalized;
                }
            }

            $actions = $this->normalizeActions((array)($rule['actions'] ?? []), $fieldCodes);
            if ($conditions === [] || $actions === []) {
                continue;
            }

            $result[] = [
                'uid' => $this->normalizeId((string)($rule['uid'] ?? $rule['id'] ?? 'rule_' . ($index + 1))),
                'enabled' => true,
                'event' => trim((string)($rule['event'] ?? 'change')) ?: 'change',
                'logic' => $this->normalizeLogic((string)($rule['logic'] ?? 'and')),
                'conditions' => $conditions,
                'when' => $conditions[0],
                'actions' => $actions,
                'groupId' => trim((string)($rule['groupId'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * Нормализует operator.
     *
     * @param string $operator
     *
     * @return string
     */
    public function normalizeOperator(string $operator): string
    {
        $operator = strtolower(trim($operator));
        return self::OPERATOR_ALIASES[$operator] ?? '=';
    }

    /**
     * Нормализует действия.
     *
     * @param string $action
     *
     * @return string
     */
    public function normalizeAction(string $action): string
    {
        $action = strtolower(trim($action));
        return self::ACTION_ALIASES[$action] ?? 'show';
    }

    /**
     * Возвращает operators.
     *
     * @return array
     */
    public function getOperators(): array
    {
        return [
            '=' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_001'),
            '!=' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_002'),
            'filled' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_003'),
            'not_filled' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_004'),
            'in' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_005'),
            'not_in' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_006'),
            '>' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_007'),
            '>=' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_008'),
            '<' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_009'),
            '<=' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_010'),
            'contains' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_011'),
            'not_contains' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_012'),
        ];
    }

    /**
     * Возвращает действий.
     *
     * @return array
     */
    public function getActions(): array
    {
        return [
            'show' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_013'),
            'hide' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_014'),
            'require' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_015'),
            'unrequire' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_016'),
            'clear_value' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_017'),
            'set_value' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_018'),
            'disable' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_019'),
            'enable' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_020'),
        ];
    }

    /**
     * Нормализует правила groups.
     *
     * @param array $groups
     * @param array $fieldCodes
     *
     * @return array
     */
    private function normalizeRuleGroups(array $groups, array $fieldCodes): array
    {
        $result = [];
        foreach ($groups as $groupIndex => $group) {
            if (!is_array($group)) {
                continue;
            }

            $groupId = $this->normalizeId((string)($group['id'] ?? $group['uid'] ?? 'group_' . ($groupIndex + 1)));
            $sourceField = trim((string)($group['sourceField'] ?? $group['sourceFieldCode'] ?? $group['fieldCode'] ?? $group['field'] ?? ''));
            $rows = [];
            foreach ((array)($group['rows'] ?? []) as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $targetField = trim((string)($row['targetFieldCode'] ?? $row['fieldCode'] ?? $row['targetField'] ?? ''));
                $rows[] = [
                    'id' => $this->normalizeId((string)($row['id'] ?? $row['uid'] ?? $groupId . '_row_' . ($rowIndex + 1))),
                    'uid' => (string)($row['uid'] ?? $groupId . '_row_' . ($rowIndex + 1)),
                    'enabled' => array_key_exists('enabled', $row) ? $this->toBool($row['enabled']) : true,
                    'operator' => $this->normalizeOperator((string)($row['operator'] ?? '=')),
                    'value' => $row['value'] ?? '',
                    'values' => is_array($row['values'] ?? null) ? array_values($row['values']) : [],
                    'actionType' => $this->normalizeAction((string)($row['actionType'] ?? $row['type'] ?? $group['actionType'] ?? 'show')),
                    'targetFieldCode' => $this->isKnownField($targetField, $fieldCodes) ? $targetField : '',
                    'valueToSet' => $row['valueToSet'] ?? $row['setValue'] ?? '',
                    'conditions' => is_array($row['conditions'] ?? null) ? array_values($row['conditions']) : [],
                    'actions' => $this->normalizeActions(is_array($row['actions'] ?? null) ? $row['actions'] : [], $fieldCodes),
                ];
            }

            $normalizedSourceField = $this->isKnownField($sourceField, $fieldCodes) ? $sourceField : '';
            $targetFieldCodes = [];
            foreach ((array)($group['targetFieldCodes'] ?? []) as $target) {
                $target = trim((string)$target);
                if ($this->isKnownField($target, $fieldCodes)) {
                    $targetFieldCodes[] = $target;
                }
            }

            $result[] = [
                'id' => $groupId,
                'uid' => (string)($group['uid'] ?? $groupId),
                'title' => trim((string)($group['title'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_021') . ($groupIndex + 1))),
                'enabled' => array_key_exists('enabled', $group) ? $this->toBool($group['enabled']) : true,
                'mode' => trim((string)($group['mode'] ?? 'different_values_different_fields')) ?: 'different_values_different_fields',
                'sourceField' => $normalizedSourceField,
                'sourceFieldCode' => $normalizedSourceField,
                'actionType' => $this->normalizeAction((string)($group['actionType'] ?? 'show')),
                'targetFieldCodes' => array_values(array_unique($targetFieldCodes)),
                'actions' => $this->normalizeActions(is_array($group['actions'] ?? null) ? $group['actions'] : [], $fieldCodes),
                'logic' => $this->normalizeLogic((string)($group['logic'] ?? 'and')),
                'rows' => $rows,
                'conditions' => is_array($group['conditions'] ?? null) ? array_values(array_filter(
                    $group['conditions'],
                    fn(mixed $condition): bool => is_array($condition)
                        && $this->isKnownField(trim((string)($condition['fieldCode'] ?? $condition['field'] ?? '')), $fieldCodes)
                )) : [],
            ];
        }

        return $result;
    }

    /**
     * Нормализует строки conditions.
     *
     * @param array $row
     * @param string $sourceField
     * @param array $fieldCodes
     *
     * @return array
     */
    private function normalizeRowConditions(array $row, string $sourceField, array $fieldCodes): array
    {
        $conditions = [];
        $rawConditions = is_array($row['conditions'] ?? null) ? $row['conditions'] : [];

        if ($rawConditions === []) {
            $when = is_array($row['when'] ?? null) ? $row['when'] : [];
            if ($when !== []) {
                $rawConditions[] = $when;
            }
        }

        foreach ($rawConditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }
            $normalized = $this->normalizeCondition($condition, $sourceField, $fieldCodes);
            if ($normalized !== null) {
                $conditions[] = $normalized;
            }
        }

        if ($conditions === [] && $sourceField !== '') {
            $normalized = $this->normalizeCondition([
                'fieldCode' => $sourceField,
                'operator' => $row['operator'] ?? '=',
                'value' => $row['value'] ?? '',
            ], $sourceField, $fieldCodes);
            if ($normalized !== null) {
                $conditions[] = $normalized;
            }
        }

        return $conditions;
    }

    /**
     * Нормализует condition.
     *
     * @param array $condition
     * @param string $fallbackField
     * @param array $fieldCodes
     *
     * @return ?array
     */
    private function normalizeCondition(array $condition, string $fallbackField, array $fieldCodes): ?array
    {
        $field = trim((string)($condition['field'] ?? $condition['fieldCode'] ?? $condition['sourceField'] ?? $fallbackField));
        if (!$this->isKnownField($field, $fieldCodes)) {
            return null;
        }

        $values = $condition['values'] ?? null;
        if (!is_array($values)) {
            $value = $condition['value'] ?? null;
            $values = ($value === null || $value === '') ? [] : [$value];
        }

        return [
            'fieldCode' => $field,
            'field' => $field,
            'operator' => $this->normalizeOperator((string)($condition['operator'] ?? '=')),
            'value' => $condition['value'] ?? ($values[0] ?? ''),
            'values' => array_values($values),
            'compareBy' => trim((string)($condition['compareBy'] ?? 'value')) === 'xmlId' ? 'xmlId' : 'value',
            'enabled' => true,
        ];
    }

    /**
     * Определяет строки действий.
     *
     * @param array $group
     * @param array $row
     * @param array $fieldCodes
     *
     * @return array
     */
    private function resolveRowActions(array $group, array $row, array $fieldCodes): array
    {
        if (is_array($row['actions'] ?? null) && $row['actions'] !== []) {
            return $this->normalizeActions($row['actions'], $fieldCodes);
        }

        if (is_array($group['actions'] ?? null) && $group['actions'] !== []) {
            return $this->normalizeActions($group['actions'], $fieldCodes);
        }

        $mode = trim((string)($group['mode'] ?? ''));
        $actionType = trim((string)($row['actionType'] ?? $group['actionType'] ?? 'show')) ?: 'show';
        $actions = [];

        if ($mode === 'different_values_different_fields' || trim((string)($row['targetFieldCode'] ?? '')) !== '') {
            $target = trim((string)($row['targetFieldCode'] ?? ''));
            if ($target !== '') {
                $actions[] = ['type' => $actionType, 'fieldCode' => $target, 'value' => $row['valueToSet'] ?? $row['setValue'] ?? $row['actionValue'] ?? ''];
            }
        } else {
            foreach ((array)($group['targetFieldCodes'] ?? []) as $target) {
                $target = trim((string)$target);
                if ($target !== '') {
                    $actions[] = ['type' => $actionType, 'fieldCode' => $target, 'value' => $row['valueToSet'] ?? $row['setValue'] ?? $row['actionValue'] ?? ''];
                }
            }
        }

        return $this->normalizeActions($actions, $fieldCodes);
    }

    /**
     * Нормализует действий.
     *
     * @param array $actions
     * @param array $fieldCodes
     *
     * @return array
     */
    private function normalizeActions(array $actions, array $fieldCodes): array
    {
        $result = [];
        foreach ($actions as $action) {
            if (!is_array($action)) {
                continue;
            }

            $typeRaw = strtolower(trim((string)($action['type'] ?? $action['action'] ?? '')));
            $type = self::ACTION_ALIASES[$typeRaw] ?? '';
            if ($type === '') {
                continue;
            }

            $field = trim((string)($action['field'] ?? $action['fieldCode'] ?? $action['targetField'] ?? $action['targetFieldCode'] ?? ''));
            if (!$this->isKnownField($field, $fieldCodes)) {
                continue;
            }

            $result[] = [
                'type' => $type,
                'fieldCode' => $field,
                'field' => $field,
                'value' => $action['value'] ?? $action['valueToSet'] ?? $action['setValue'] ?? '',
                'clearOnHide' => !empty($action['clearOnHide']) || $type === 'clear_value',
            ];
        }

        return $result;
    }

    /**
     * Формирует depends on.
     *
     * @param array $rules
     *
     * @return array
     */
    private function buildDependsOn(array $rules): array
    {
        $result = [];
        foreach ($rules as $rule) {
            $ruleId = trim((string)($rule['uid'] ?? ''));
            if ($ruleId === '') {
                continue;
            }
            foreach ((array)($rule['conditions'] ?? []) as $condition) {
                if (!is_array($condition)) {
                    continue;
                }
                $field = trim((string)($condition['fieldCode'] ?? $condition['field'] ?? ''));
                if ($field === '') {
                    continue;
                }
                $result[$field] ??= [];
                if (!in_array($ruleId, $result[$field], true)) {
                    $result[$field][] = $ruleId;
                }
            }
        }

        return $result;
    }

    /**
     * Извлекает поля codes.
     *
     * @param array $schema
     *
     * @return array
     */
    private function extractFieldCodes(array $schema): array
    {
        $codes = [];
        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }
            $code = trim((string)($field['code'] ?? ''));
            if ($code !== '') {
                $codes[$code] = true;
            }
        }

        return $codes;
    }

    /**
     * Проверяет признак known поля.
     *
     * @param string $fieldCode
     * @param array $fieldCodes
     *
     * @return bool
     */
    private function isKnownField(string $fieldCode, array $fieldCodes): bool
    {
        if ($fieldCode === '') {
            return false;
        }
        if ($fieldCodes === []) {
            return true;
        }

        return isset($fieldCodes[$fieldCode]);
    }

    /**

     * Нормализует logic.

     *

     * @param string $logic

     *

     * @return string

     */

    private function normalizeLogic(string $logic): string
    {
        return strtolower(trim($logic)) === 'or' ? 'or' : 'and';
    }

    /**

     * Нормализует ID.

     *

     * @param string $id

     *

     * @return string

     */

    private function normalizeId(string $id): string
    {
        $id = trim($id);
        if ($id === '') {
            return 'rule_' . md5((string)microtime(true));
        }

        return preg_replace('/[^a-zA-Z0-9_\-:.]/', '_', $id) ?: $id;
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

        return in_array(strtolower(trim((string)$value)), ['1', 'y', 'yes', 'true', 'on', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRULECOMPILERSERVICE_022')], true);
    }
}
