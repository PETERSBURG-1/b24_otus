<?php

namespace Rexp\Form\Infrastructure\Persistence\Orm;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;
use Rexp\Form\Infrastructure\Persistence\TableNames;

/**
 * ORM-таблица версий форм.
 */
class FormVersionTable extends DataManager
{
    /**
     * Возвращает имя ORM-таблицы.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return TableNames::version();
    }

    /**
     * Возвращает карту полей ORM-таблицы.
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new Entity\IntegerField('FORM_ID', [
                'required' => true,
            ]),
            new Entity\StringField('VERSION_TYPE', [
                'required' => true,
                'size' => 50,
                'default_value' => 'save',
            ]),
            new Entity\StringField('NAME', [
                'required' => true,
                'size' => 255,
                'default_value' => '',
            ]),
            new Entity\StringField('CODE', [
                'required' => true,
                'size' => 255,
                'default_value' => '',
            ]),
            new Entity\StringField('STATUS', [
                'required' => true,
                'size' => 50,
                'default_value' => 'draft',
            ]),
            new Entity\TextField('SCHEMA', [
                'required' => true,
                'column_name' => 'VERSION_SCHEMA',
            ]),
            new Entity\IntegerField('CREATED_BY', [
                'default_value' => static fn() => (int)(CurrentUser::get()?->getId() ?: 0),
            ]),
            new Entity\DatetimeField('CREATED_AT', [
                'required' => true,
                'default_value' => static fn() => new DateTime(),
            ]),
        ];
    }
}
