<?php


use Bitrix\Main\Loader;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Компонент редактора формы.
 */
class RexpFormDesignerEditorComponent extends CBitrixComponent
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
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_EDITOR_CLASS_001'));
            return;
        }

        $permissions = $this->permissionService()->getPermissions();
        if (empty($permissions['canManage'])) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_EDITOR_CLASS_002'));
            return;
        }

        $this->arResult = [
            'CONTROLLER' => $this->arParams['CONTROLLER'],
            'FORM_ID' => (int)(($_REQUEST['FORM_ID'] ?? 0) ?: ($_REQUEST['id'] ?? 0)),
            'VERSIONS_URL' => '/forms/designer/versions.php',
            'DRAFTS_URL' => '/forms/designer/drafts.php',
            'BIZPROC_URL' => '/forms/designer/bizproc.php',
            'RUNTIME_URL' => '/forms/runtime/index.php',
            'MODULE_OPTIONS' => $this->moduleOptionsService()->getOptions(),
        ];

        $this->includeComponentTemplate();
    }


    /**
     * Возвращает сервис проверки прав.
     *
     * @return mixed
     */
    private function permissionService()
    {
        return \Bitrix\Main\DI\ServiceLocator::getInstance()->get('rexp.form.service.permission');
    }

    /**
     * Возвращает сервис настроек модуля.
     *
     * @return mixed
     */
    private function moduleOptionsService()
    {
        return \Bitrix\Main\DI\ServiceLocator::getInstance()->get('rexp.form.service.module_options');
    }
}

