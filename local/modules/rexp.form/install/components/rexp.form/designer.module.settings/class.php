<?php


use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Rexp\Form\Application\Service\FormModuleOptionsService;
use Rexp\Form\Application\Service\FormPermissionService;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Компонент настроек модуля конструктора форм.
 */
class RexpFormDesignerModuleSettingsComponent extends CBitrixComponent
{
    /**
     * Подготавливает данные компонента и подключает шаблон.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule('rexp.form')) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_MODULE_SETTINGS_CLASS_001'));
            return;
        }

        try {
            $this->permissionService()->assertCanManageSettings();
        } catch (\Throwable $exception) {
            ShowError($exception->getMessage() ?: \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_MODULE_SETTINGS_CLASS_002'));
            return;
        }

        $saved = false;
        if ($this->isSaveRequest()) {
            $this->optionsService()->saveOptions($this->getOptionsFromRequest());
            $saved = true;
        }

        $this->arResult = [
            'OPTIONS' => $this->optionsService()->getOptions(),
            'SAVED' => $saved,
        ];

        $this->includeComponentTemplate();
    }

    /**
     * Проверяет, является ли текущий запрос сохранением настроек.
     *
     * @return bool
     */
    private function isSaveRequest(): bool
    {
        return (string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && (string)($_POST['save_module_settings'] ?? '') === 'Y'
            && check_bitrix_sessid();
    }

    /**
     * Получает настройки модуля из HTTP-запроса.
     *
     * @return array
     */
    private function getOptionsFromRequest(): array
    {
        return [
            FormModuleOptionsService::OPTION_SUBMISSION_LOG_ENABLED => (string)($_POST[FormModuleOptionsService::OPTION_SUBMISSION_LOG_ENABLED] ?? 'N'),
            FormModuleOptionsService::OPTION_DRAFTS_ENABLED => (string)($_POST[FormModuleOptionsService::OPTION_DRAFTS_ENABLED] ?? 'N'),
            FormModuleOptionsService::OPTION_VERSIONS_ENABLED => (string)($_POST[FormModuleOptionsService::OPTION_VERSIONS_ENABLED] ?? 'N'),
            FormModuleOptionsService::OPTION_EDITOR_COUNTERS_ENABLED => (string)($_POST[FormModuleOptionsService::OPTION_EDITOR_COUNTERS_ENABLED] ?? 'N'),
        ];
    }

    /**
     * Возвращает сервис настроек модуля.
     *
     * @return FormModuleOptionsService
     */
    private function optionsService(): FormModuleOptionsService
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.module_options');
    }

    /**
     * Возвращает сервис проверки прав.
     *
     * @return FormPermissionService
     */
    private function permissionService(): FormPermissionService
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.permission');
    }
}
