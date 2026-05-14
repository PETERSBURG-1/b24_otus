<?php

namespace Rexp\Form\Infrastructure\Persistence\Orm;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;
use Exception;
use Rexp\Form\Infrastructure\Persistence\TableNames;

/**
 * ORM-таблица форм.
 */
class FormTable extends DataManager
{
    /**
     * Возвращает имя ORM-таблицы.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return TableNames::form();
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
            new Entity\StringField('CODE', [
                'required' => true,
                'size' => 100,
            ]),
            new Entity\StringField('NAME', [
                'required' => true,
                'size' => 255,
            ]),
            new Entity\BooleanField('ACTIVE', [
                'values' => ['N', 'Y'],
                'default_value' => 'Y',
            ]),
            new Entity\BooleanField('ARCHIVED', [
                'values' => ['N', 'Y'],
                'default_value' => 'N',
            ]),
            new Entity\TextField('SCHEMA', [
                'required' => true,
                'column_name' => 'FORM_SCHEMA',
            ]),
            new Entity\IntegerField('CREATED_BY', [
                'default_value' => static fn() => (int)(CurrentUser::get()?->getId() ?: 0),
            ]),
            new Entity\IntegerField('UPDATED_BY', [
                'default_value' => static fn() => (int)(CurrentUser::get()?->getId() ?: 0),
            ]),
            new Entity\DatetimeField('CREATED_AT', [
                'required' => true,
                'default_value' => static fn() => new DateTime(),
            ]),
            new Entity\DatetimeField('UPDATED_AT', [
                'required' => true,
                'default_value' => static fn() => new DateTime(),
            ]),
        ];
    }

    /**
     * Обновляет запись ORM-таблицы.
     *
     * @param mixed $primary
     * @param array $data
     *
     * @return \Bitrix\Main\ORM\Data\UpdateResult
     */
    public static function update(mixed $primary, array $data): \Bitrix\Main\ORM\Data\UpdateResult
    {
        $data['UPDATED_AT'] = new DateTime();
        $data['UPDATED_BY'] = (int)(CurrentUser::get()?->getId() ?: 0);

        return parent::update($primary, $data);
    }
}
