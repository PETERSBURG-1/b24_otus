<?php

namespace Rexp\Form\Access\Role;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;
use Rexp\Form\Infrastructure\Persistence\TableNames;

/**
 * ORM-таблица ролей доступа конструктора форм.
 */
final class FormRoleTable extends DataManager
{
    /**
     * Возвращает имя ORM-таблицы.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return TableNames::accessRole();
    }

    /**
     * Возвращает карту полей ORM-таблицы.
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
            new Entity\StringField('CODE', ['required' => true, 'size' => 64]),
            new Entity\StringField('NAME', ['required' => true, 'size' => 255]),
            new Entity\BooleanField('IS_SYSTEM', ['values' => ['N', 'Y'], 'default_value' => 'N']),
            new Entity\IntegerField('SORT', ['default_value' => 100]),
            new Entity\DatetimeField('CREATED_AT', ['required' => true, 'default_value' => static fn() => new DateTime()]),
            new Entity\DatetimeField('UPDATED_AT', ['required' => true, 'default_value' => static fn() => new DateTime()]),
        ];
    }
}
