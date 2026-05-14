<?php

namespace Rexp\Form\Infrastructure\Persistence;

/**
 * Справочник имён таблиц модуля.
 */
final class TableNames
{
    public const FORM = 'rexp_form_form';
    public const VERSION = 'rexp_form_version';
    public const DRAFT = 'rexp_form_draft';
    public const SUBMISSION = 'rexp_form_submission';
    public const ACCESS_ROLE = 'rexp_form_access_role';
    public const ACCESS_ROLE_RELATION = 'rexp_form_access_role_relation';
    public const ACCESS_PERMISSION = 'rexp_form_access_permission';

    /**
     * Возвращает имя таблицы форм.
     *
     * @return string
     */
    public static function form(): string
    {
        return self::FORM;
    }

    /**
     * Возвращает имя таблицы версий.
     *
     * @return string
     */
    public static function version(): string
    {
        return self::VERSION;
    }

    /**
     * Возвращает имя таблицы черновиков.
     *
     * @return string
     */
    public static function draft(): string
    {
        return self::DRAFT;
    }

    /**
     * Возвращает имя таблицы отправок.
     *
     * @return string
     */
    public static function submission(): string
    {
        return self::SUBMISSION;
    }

    /**
     * Возвращает имя таблицы ролей доступа.
     *
     * @return string
     */
    public static function accessRole(): string
    {
        return self::ACCESS_ROLE;
    }

    /**
     * Возвращает имя таблицы связей ролей доступа.
     *
     * @return string
     */
    public static function accessRoleRelation(): string
    {
        return self::ACCESS_ROLE_RELATION;
    }

    /**
     * Возвращает имя таблицы разрешений доступа.
     *
     * @return string
     */
    public static function accessPermission(): string
    {
        return self::ACCESS_PERMISSION;
    }
}
