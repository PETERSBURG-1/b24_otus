<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Config\Option;

/**
 * Сервис чтения и сохранения настроек модуля.
 */
final class FormModuleOptionsService
{
    public const MODULE_ID = 'rexp.form';

    public const OPTION_SUBMISSION_LOG_ENABLED = 'submission_log_enabled';
    public const OPTION_DRAFTS_ENABLED = 'drafts_enabled';
    public const OPTION_VERSIONS_ENABLED = 'versions_enabled';
    public const OPTION_EDITOR_COUNTERS_ENABLED = 'editor_counters_enabled';

    /**
     * Возвращает текущие настройки модуля.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return [
            self::OPTION_SUBMISSION_LOG_ENABLED => $this->isSubmissionLogEnabled(),
            self::OPTION_DRAFTS_ENABLED => $this->isDraftsEnabled(),
            self::OPTION_VERSIONS_ENABLED => $this->isVersionsEnabled(),
            self::OPTION_EDITOR_COUNTERS_ENABLED => $this->isEditorCountersEnabled(),
        ];
    }

    /**
     * Сохраняет настройки модуля.
     *
     * @param array $options
     *
     * @return array
     */
    public function saveOptions(array $options): array
    {
        foreach ($this->getDefaultOptions() as $name => $defaultValue) {
            if (!array_key_exists($name, $options)) {
                continue;
            }

            Option::set(self::MODULE_ID, $name, $this->toBoolean($options[$name]) ? 'Y' : 'N');
        }

        return $this->getOptions();
    }

    /**
     * Проверяет, включена ли фиксация журнала отправок.
     *
     * @return bool
     */
    public function isSubmissionLogEnabled(): bool
    {
        return $this->getBoolean(self::OPTION_SUBMISSION_LOG_ENABLED, true);
    }

    /**
     * Проверяет, включено ли сохранение черновиков.
     *
     * @return bool
     */
    public function isDraftsEnabled(): bool
    {
        return $this->getBoolean(self::OPTION_DRAFTS_ENABLED, true);
    }

    /**
     * Проверяет, включено ли создание версий.
     *
     * @return bool
     */
    public function isVersionsEnabled(): bool
    {
        return $this->getBoolean(self::OPTION_VERSIONS_ENABLED, true);
    }

    /**
     * Проверяет, включён ли вывод счётчиков редактора.
     *
     * @return bool
     */
    public function isEditorCountersEnabled(): bool
    {
        return $this->getBoolean(self::OPTION_EDITOR_COUNTERS_ENABLED, true);
    }

    /**
     * Возвращает значения настроек модуля по умолчанию.
     *
     * @return array
     */
    private function getDefaultOptions(): array
    {
        return [
            self::OPTION_SUBMISSION_LOG_ENABLED => true,
            self::OPTION_DRAFTS_ENABLED => true,
            self::OPTION_VERSIONS_ENABLED => true,
            self::OPTION_EDITOR_COUNTERS_ENABLED => true,
        ];
    }

    /**
     * Возвращает булевое значение настройки модуля.
     *
     * @param string $name
     * @param bool $defaultValue
     *
     * @return bool
     */
    private function getBoolean(string $name, bool $defaultValue): bool
    {
        return Option::get(self::MODULE_ID, $name, $defaultValue ? 'Y' : 'N') === 'Y';
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

        if (is_int($value)) {
            return $value > 0;
        }

        return in_array(mb_strtolower(trim((string)$value)), ['y', 'yes', '1', 'true', 'on'], true);
    }
}
