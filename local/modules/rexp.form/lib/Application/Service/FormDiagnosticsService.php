<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Infrastructure\Persistence\FormRepository;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис диагностики схемы формы.
 */
final class FormDiagnosticsService
{
    private const SUPPORTED_FIELD_TYPES = [
        'string',
        'text',
        'email',
        'phone',
        'url',
        'integer',
        'double',
        'number',
        'money',
        'date',
        'datetime',
        'boolean',
        'checkbox',
        'switch',
        'enumeration',
        'select',
        'radio',
        'checkbox_list',
        'file',
        'entity_selector',
        'user',
        'employee',
        'department',
        'userfield',
    ];

    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormRepository $formRepository
     */
    public function __construct(private readonly FormRepository $formRepository)
    {
    }

    /**
     * Запускает диагностику указанной формы.
     *
     * @param int $formId
     *
     * @return array
     */
    public function runForForm(int $formId): array
    {
        $form = $this->formRepository->getById($formId);
        if (!$form) {
            return [
                'ok' => false,
                'errors' => [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_001')],
                'warnings' => [],
                'fieldTypes' => [],
                'rules' => [],
            ];
        }

        $errors = [];
        $warnings = [];
        $schema = $form->getSchema();
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $codes = [];
        $usedTypes = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                $warnings[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_002');
                continue;
            }

            $code = trim((string)($field['code'] ?? ''));
            $label = trim((string)($field['label'] ?? $field['title'] ?? $code));
            $type = trim((string)($field['type'] ?? 'string')) ?: 'string';
            $usedTypes[$type] = ($usedTypes[$type] ?? 0) + 1;

            if ($code === '') {
                $warnings[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_003') . ($label !== '' ? ': ' . $label : '') . '.';
                continue;
            }

            if (isset($codes[$code])) {
                $errors[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_004') . $code;
            }
            $codes[$code] = true;

            if (!in_array($type, self::SUPPORTED_FIELD_TYPES, true)) {
                $errors[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_005') . ($label ?: $code) . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_006') . $type;
            }

            if ($type === 'enumeration' || in_array($type, ['select', 'radio', 'checkbox_list'], true)) {
                $items = is_array($field['items'] ?? null) ? $field['items'] : [];
                if ($items === [] && empty($field['dynamicOptions'])) {
                    $warnings[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_007') . ($label ?: $code) . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_008');
                }
            }

            if ($type === 'entity_selector') {
                $source = is_array($field['source'] ?? null) ? $field['source'] : [];
                if (empty($source['entities']) && empty($field['selectorEntities'])) {
                    $warnings[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_009') . ($label ?: $code) . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_010');
                }
            }
        }

        if ($fields === []) {
            $warnings[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_011');
        }

        $compiledRules = is_array($schema['compiledRules']['rules'] ?? null) ? $schema['compiledRules']['rules'] : [];
        foreach ($compiledRules as $rule) {
            if (!is_array($rule)) {
                $warnings[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_012');
                continue;
            }

            foreach ((array)($rule['conditions'] ?? []) as $condition) {
                if (!is_array($condition)) {
                    continue;
                }
                $fieldCode = trim((string)($condition['fieldCode'] ?? $condition['field'] ?? ''));
                if ($fieldCode !== '' && !isset($codes[$fieldCode])) {
                    $errors[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_013') . (string)($rule['uid'] ?? '') . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_014') . $fieldCode;
                }
            }

            foreach ((array)($rule['actions'] ?? []) as $action) {
                if (!is_array($action)) {
                    continue;
                }
                $fieldCode = trim((string)($action['fieldCode'] ?? $action['field'] ?? ''));
                if ($fieldCode !== '' && !isset($codes[$fieldCode])) {
                    $errors[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_015') . (string)($rule['uid'] ?? '') . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMDIAGNOSTICSSERVICE_016') . $fieldCode;
                }
            }
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'fieldTypes' => [
                'supported' => self::SUPPORTED_FIELD_TYPES,
                'used' => $usedTypes,
            ],
            'rules' => [
                'groups' => is_array($schema['ruleGroups'] ?? null) ? count($schema['ruleGroups']) : 0,
                'compiled' => count($compiledRules),
                'dependsOn' => is_array($schema['compiledRules']['dependsOn'] ?? null) ? $schema['compiledRules']['dependsOn'] : [],
            ],
        ];
    }
}
