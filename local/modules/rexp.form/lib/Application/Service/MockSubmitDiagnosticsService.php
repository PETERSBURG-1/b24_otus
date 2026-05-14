<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Type\DateTime;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис диагностической имитации отправки формы.
 */
final class MockSubmitDiagnosticsService
{
    /**
     * Формирует mock значений.
     *
     * @param array $schema
     *
     * @return array
     */
    public function buildMockValues(array $schema): array
    {
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $values = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $code = (string)($field['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $values[$code] = $this->mockValueForField($field);
        }

        return $values;
    }

    /**
     * Запускает основную операцию сервиса.
     *
     * @param array $schema
     *
     * @return array
     */
    public function run(array $schema): array
    {
        $values = $this->buildMockValues($schema);
        $payload = [];
        $warnings = [];

        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }

            $code = trim((string)($field['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            if (($field['type'] ?? '') === 'file') {
                $warnings[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_001') . $code;
                continue;
            }

            $payload[$code] = $values[$code] ?? null;
        }

        return [
            'success' => true,
            'messages' => [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_002')],
            'warnings' => array_values(array_unique($warnings)),
            'errors' => [],
            'payload' => $payload,
            'mockValues' => $values,
            'checkedAt' => (new DateTime())->toString(),
        ];
    }

    /**
     * Выполняет пробную обработку отправки формы.
     *
     * @param array $item
     * @param array $values
     * @param array $hlFields
     *
     * @return array
     */
    public function probe(array $item, array $values, array $hlFields = []): array
    {
        $schema = is_array($item['schema'] ?? null) ? $item['schema'] : [];
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $errors = [];
        $warnings = [];
        $payload = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $code = trim((string)($field['code'] ?? ''));
            $label = trim((string)($field['label'] ?? $code ?: \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_003')));
            if ($code === '') {
                continue;
            }

            $value = $values[$code] ?? null;
            $required = !empty($field['required']);
            $empty = $value === null || $value === '' || (is_array($value) && count(array_filter($value, static fn($v) => $v !== null && $v !== '')) === 0);
            if ($required && $empty) {
                $errors[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_004') . $label . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_005');
            }

            if (($field['type'] ?? '') === 'file') {
                $warnings[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_006') . $label . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_007');
                continue;
            }

            $payload[$code] = $value;
        }

        return [
            'isValid' => empty($errors),
            'messages' => empty($errors) ? [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_008')] : [],
            'warnings' => array_values(array_unique($warnings)),
            'errors' => $errors,
            'payloadPreview' => $payload,
            'checkedAt' => (new DateTime())->toString(),
        ];
    }

    /**
     * Формирует тестовое значение для поля формы.
     *
     * @param array $field
     *
     * @return mixed
     */
    private function mockValueForField(array $field): mixed
    {
        $type = (string)($field['type'] ?? 'string');
        $multiple = (bool)($field['multiple'] ?? false);
        $items = is_array($field['items'] ?? null) ? $field['items'] : [];

        $single = match ($type) {
            'number' => 1,
            'boolean', 'checkbox' => 1,
            'date' => date('Y-m-d'),
            'datetime' => date('Y-m-d H:i:s'),
            'select', 'radio', 'checkbox_list', 'enumeration' => (string)($items[0]['value'] ?? $items[0]['id'] ?? '1'),
            'user', 'employee' => 1,
            'department' => 1,
            'file' => null,
            default => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_MOCKSUBMITDIAGNOSTICSSERVICE_009'),
        };

        return $multiple ? array_values(array_filter([$single], static fn($value) => $value !== null && $value !== '')) : $single;
    }
}
