<?php

namespace Rexp\Form\Access\Permission;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Rexp\Form\Infrastructure\Persistence\TableNames;

/**
 * ORM-таблица прав ролей конструктора форм.
 */
final class FormPermissionTable extends DataManager
{
    /**
     * Возвращает имя ORM-таблицы.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return TableNames::accessPermission();
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
            new Entity\IntegerField('ROLE_ID', ['required' => true]),
            new Entity\StringField('PERMISSION_ID', ['required' => true, 'size' => 100]),
            new Entity\BooleanField('VALUE', ['values' => ['N', 'Y'], 'default_value' => 'Y']),
        ];
    }
}
