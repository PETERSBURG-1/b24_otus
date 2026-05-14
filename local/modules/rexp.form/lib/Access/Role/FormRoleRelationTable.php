<?php

namespace Rexp\Form\Access\Role;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Rexp\Form\Infrastructure\Persistence\TableNames;

/**
 * ORM-таблица связей ролей с access-кодами пользователей и групп.
 */
final class FormRoleRelationTable extends DataManager
{
    /**
     * Возвращает имя ORM-таблицы.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return TableNames::accessRoleRelation();
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
            new Entity\StringField('ACCESS_CODE', ['required' => true, 'size' => 100]),
        ];
    }
}
