<?php

namespace Rexp\Form\Access\Role;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Справочник базовых ролей конструктора форм.
 */
final class RoleDictionary
{
    public const ADMIN = 'admin';
    public const EDITOR = 'editor';
    public const USER = 'user';

    /**
     * Возвращает подписи элементов справочника.
     *
     * @return array
     */
    public static function getLabels(): array
    {
        return [
            self::ADMIN => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ROLE_ROLEDICTIONARY_001'),
            self::EDITOR => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ROLE_ROLEDICTIONARY_002'),
            self::USER => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_ACCESS_ROLE_ROLEDICTIONARY_003'),
        ];
    }
}
