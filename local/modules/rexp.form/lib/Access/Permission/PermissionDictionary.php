<?php

namespace Rexp\Form\Access\Permission;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Справочник кодов разрешений конструктора форм.
 */
final class PermissionDictionary
{
    public const SECTION_VIEW = 'section_view';
    public const FORM_READ = 'form_read';
    public const FORM_EDIT = 'form_edit';
    public const FORM_PUBLISH = 'form_publish';
    public const FORM_DELETE = 'form_delete';
    public const SUBMISSION_READ = 'submission_read';
    public const BIZPROC_MANAGE = 'bizproc_manage';
    public const PUBLIC_USE = 'public_use';
    public const SETTINGS_MANAGE = 'settings_manage';

    /**
     * Возвращает подписи элементов справочника.
     *
     * @return array
     */
    public static function getLabels(): array
    {
        return [
            self::SECTION_VIEW => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_001'),
            self::FORM_READ => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_002'),
            self::FORM_EDIT => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_003'),
            self::FORM_PUBLISH => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_004'),
            self::FORM_DELETE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_005'),
            self::SUBMISSION_READ => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_006'),
            self::BIZPROC_MANAGE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_007'),
            self::PUBLIC_USE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_008'),
            self::SETTINGS_MANAGE => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_PERMISSION_PERMISSIONDICTIONARY_009'),
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
