<?php

namespace Rexp\Form\Infrastructure\Persistence\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;
use Rexp\Form\Infrastructure\Persistence\TableNames;

/**
 * ORM-таблица черновиков форм.
 */
class FormDraftTable extends DataManager
{
    /**
     * Возвращает имя ORM-таблицы.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return TableNames::draft();
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
            new Entity\IntegerField('USER_ID', [
                'required' => true,
            ]),
            new Entity\TextField('FORM_JSON', [
                'required' => true,
            ]),
            new Entity\DatetimeField('UPDATED_AT', [
                'required' => true,
                'default_value' => static fn() => new DateTime(),
            ]),
        ];
    }
}
