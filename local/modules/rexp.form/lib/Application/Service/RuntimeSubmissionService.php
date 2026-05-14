<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Infrastructure\Persistence\Orm\FormSubmissionTable;

/**
 * Сервис сохранения отправки формы и запуска связанных обработчиков.
 */
final class RuntimeSubmissionService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormModuleOptionsService $moduleOptions
     */
    public function __construct(
        private readonly FormModuleOptionsService $moduleOptions,
    ) {
    }

    /**
     * Создаёт начальную запись процесса отправки формы.
     *
     * @param array $formRow
     * @param string $mode
     * @param int $entryId
     * @param array $values
     * @param array $context
     * @param array $normalizedValues
     *
     * @return int
     */
    public function start(array $formRow, string $mode, int $entryId, array $values, array $context = [], array $normalizedValues = []): int
    {
        if (!$this->moduleOptions->isSubmissionLogEnabled() || !$this->isEnabled($formRow)) {
            return 0;
        }

        $result = FormSubmissionTable::add([
            'FORM_ID' => (int)($formRow['ID'] ?? 0),
            'FORM_CODE' => trim((string)($formRow['CODE'] ?? '')),
            'ENTRY_ID' => $entryId,
            'MODE' => trim($mode) ?: 'create',
            'STATUS' => 'processing',
            'VALUES_JSON' => $this->encodeJson($values, '{}'),
            'NORMALIZED_VALUES_JSON' => $this->encodeJson($normalizedValues, '{}'),
            'CONTEXT_JSON' => $this->encodeJson($context, '{}'),
            'RESULT_JSON' => '{}',
            'ERRORS_JSON' => '[]',
        ]);

        return $result->isSuccess() ? (int)$result->getId() : 0;
    }

    /**
     * Отмечает отправку формы как успешную.
     *
     * @param int $submissionId
     * @param array $values
     * @param array $resultPayload
     *
     * @return void
     */
    public function markSuccess(int $submissionId, array $values, array $resultPayload = []): void
    {
        if ($submissionId <= 0) {
            return;
        }

        FormSubmissionTable::update($submissionId, [
            'STATUS' => 'success',
            'ENTRY_ID' => (int)($resultPayload['entryId'] ?? 0),
            'NORMALIZED_VALUES_JSON' => $this->encodeJson($values, '{}'),
            'RESULT_JSON' => $this->encodeJson($resultPayload, '{}'),
            'ERRORS_JSON' => '[]',
        ]);
    }


    /**
     * Отмечает отправку формы как частично успешную.
     *
     * @param int $submissionId
     * @param array $values
     * @param array $resultPayload
     * @param array $errors
     *
     * @return void
     */
    public function markPartialSuccess(int $submissionId, array $values, array $resultPayload = [], array $errors = []): void
    {
        if ($submissionId <= 0) {
            return;
        }

        FormSubmissionTable::update($submissionId, [
            'STATUS' => 'partial_success',
            'ENTRY_ID' => (int)($resultPayload['entryId'] ?? 0),
            'NORMALIZED_VALUES_JSON' => $this->encodeJson($values, '{}'),
            'RESULT_JSON' => $this->encodeJson($resultPayload, '{}'),
            'ERRORS_JSON' => $this->encodeJson(array_values($errors), '[]'),
        ]);
    }

    /**
     * Отмечает отправку формы как ошибочную.
     *
     * @param int $submissionId
     * @param array $values
     * @param array $context
     * @param array $errors
     *
     * @return void
     */
    public function markError(int $submissionId, array $values, array $context = [], array $errors = []): void
    {
        if ($submissionId <= 0) {
            return;
        }

        FormSubmissionTable::update($submissionId, [
            'STATUS' => 'error',
            'NORMALIZED_VALUES_JSON' => $this->encodeJson($values, '{}'),
            'CONTEXT_JSON' => $this->encodeJson($context, '{}'),
            'ERRORS_JSON' => $this->encodeJson(array_values($errors), '[]'),
        ]);
    }

    /**
     * Проверяет признак enabled.
     *
     * @param array $formRow
     *
     * @return bool
     */
    private function isEnabled(array $formRow): bool
    {
        $schema = [];
        $rawSchema = $formRow['FORM_SCHEMA'] ?? $formRow['SCHEMA'] ?? '{}';
        if (is_string($rawSchema) && $rawSchema !== '') {
            $decoded = json_decode($rawSchema, true);
            if (is_array($decoded)) {
                $schema = $decoded;
            }
        } elseif (is_array($rawSchema)) {
            $schema = $rawSchema;
        }

        $settings = is_array($schema['settings'] ?? null) ? $schema['settings'] : [];
        $submitProcessing = is_array($settings['submitProcessing'] ?? null) ? $settings['submitProcessing'] : [];

        if (array_key_exists('logSubmissions', $submitProcessing)) {
            return $this->toBoolean($submitProcessing['logSubmissions']);
        }

        return true;
    }

    /**
     * Приводит значение настройки к булевому виду.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(mb_strtolower(trim((string)$value)), ['y', 'yes', '1', 'true'], true);
    }

    /**
     * Кодирует json.
     *
     * @param mixed $value
     * @param string $fallback
     *
     * @return string
     */
    private function encodeJson(mixed $value, string $fallback): string
    {
        try {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable) {
            $encoded = false;
        }

        return is_string($encoded) && $encoded !== '' ? $encoded : $fallback;
    }
}
