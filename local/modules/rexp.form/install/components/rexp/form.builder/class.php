<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Компонент совместимости для старого имени form.builder.
 */
final class RexpCompatFormBuilderComponent extends CBitrixComponent
{
    private const TARGET_COMPONENT = 'rexp.form:runtime';

    /**
     * Подключает актуальный компонент модуля.
     *
     * @return mixed
     */
    public function executeComponent()
    {
        global $APPLICATION;

        if (!Loader::includeModule('rexp.form')) {
            ShowError(Loc::getMessage('REXP_FORM_COMPAT_MODULE_NOT_INSTALLED'));
            return null;
        }

        return $APPLICATION->IncludeComponent(
            self::TARGET_COMPONENT,
            $this->getTemplateName() ?: '.default',
            $this->arParams,
            $this
        );
    }
}
