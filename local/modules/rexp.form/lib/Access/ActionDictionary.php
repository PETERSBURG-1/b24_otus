<?php

namespace Rexp\Form\Access;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Справочник действий прав доступа конструктора форм.
 */
final class ActionDictionary
{
    public const ACTION_SECTION_VIEW = 'section_view';
    public const ACTION_FORM_READ = 'form_read';
    public const ACTION_FORM_EDIT = 'form_edit';
    public const ACTION_FORM_PUBLISH = 'form_publish';
    public const ACTION_FORM_DELETE = 'form_delete';
    public const ACTION_SUBMISSION_READ = 'submission_read';
    public const ACTION_BIZPROC_MANAGE = 'bizproc_manage';
    public const ACTION_PUBLIC_USE = 'public_use';
    public const ACTION_SETTINGS_MANAGE = 'settings_manage';

    /**
     * Возвращает подписи элементов справочника.
     *
     * @return array
     */
    public static function getLabels(): array
    {
        return [
            self::ACTION_SECTION_VIEW => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_001'),
            self::ACTION_FORM_READ => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_002'),
            self::ACTION_FORM_EDIT => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_003'),
            self::ACTION_FORM_PUBLISH => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_004'),
            self::ACTION_FORM_DELETE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_005'),
            self::ACTION_SUBMISSION_READ => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_006'),
            self::ACTION_BIZPROC_MANAGE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_007'),
            self::ACTION_PUBLIC_USE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_008'),
            self::ACTION_SETTINGS_MANAGE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ACTIONDICTIONARY_009'),
        ];
    }

    /**
     * Возвращает полный список элементов справочника.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return array_keys(self::getLabels());
    }
}
