<?php

namespace Otus\Main\Ui;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

/**
 * Подключает JS для модального окна начала рабочего дня.
 */
class BeginDateButton
{
    /**
     * Подключает расширение и языковые фразы на страницах портала.
     *
     * @return void
     */
    public static function onProlog(): void
    {
        global $USER;

        if (!is_object($USER) || !$USER->IsAuthorized())
        {
            return;
        }

        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
        {
            return;
        }

        if (!Loader::includeModule('timeman'))
        {
            return;
        }

        Extension::load('otus.main.begin_date_button');

        Asset::getInstance()->addString(
            '<script>BX.message(' . \CUtil::PhpToJSObject([
                'OTUS_MAIN_BEGIN_DATE_BUTTON_TITLE' => Loc::getMessage('OTUS_MAIN_BEGIN_DATE_BUTTON_TITLE'),
                'OTUS_MAIN_BEGIN_DATE_BUTTON_TEXT' => Loc::getMessage('OTUS_MAIN_BEGIN_DATE_BUTTON_TEXT'),
                'OTUS_MAIN_BEGIN_DATE_BUTTON_CONFIRM' => Loc::getMessage('OTUS_MAIN_BEGIN_DATE_BUTTON_CONFIRM'),
                'OTUS_MAIN_BEGIN_DATE_BUTTON_CANCEL' => Loc::getMessage('OTUS_MAIN_BEGIN_DATE_BUTTON_CANCEL'),
                'OTUS_MAIN_BEGIN_DATE_BUTTON_ERROR' => Loc::getMessage('OTUS_MAIN_BEGIN_DATE_BUTTON_ERROR'),
            ]) . ');</script>'
        );
    }
}
