<?php


use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Компонент страницы прав конструктора форм.
 */
class RexpFormDesignerSettingsComponent extends CBitrixComponent
{
    /**
     * Нормализует входные параметры компонента.
     *
     * @param mixed $arParams
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['CONTROLLER'] = (string)($arParams['CONTROLLER'] ?? 'rexp:form.Designer');

        return $arParams;
    }

    /**
     * Подготавливает данные компонента и подключает шаблон.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule('rexp.form')) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SETTINGS_CLASS_001'));
            return;
        }

        $permissions = $this->permissionService()->getPermissions();
        if (empty($permissions['canManageSettings'])) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SETTINGS_CLASS_002'));
            return;
        }

        $settings = $this->settingsService()->getAccessRightsSettings();
        $this->arResult = [
            'CONTROLLER' => $this->arParams['CONTROLLER'],
            'USER_GROUPS' => $settings['USER_GROUPS'],
            'ACCESS_RIGHTS' => $settings['ACCESS_RIGHTS'],
        ];

        $this->includeComponentTemplate();
    }

    /**
     * Возвращает сервис проверки прав.
     *
     * @return \Rexp\Form\Application\Service\FormPermissionService
     */
    private function permissionService(): \Rexp\Form\Application\Service\FormPermissionService
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.permission');
    }

    /**
     * Возвращает сервис настроек прав конструктора.
     *
     * @return \Rexp\Form\Application\Service\FormSettingsService
     */
    private function settingsService(): \Rexp\Form\Application\Service\FormSettingsService
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.settings');
    }
}
