<?php

namespace Rexp\Form\Infrastructure\Persistence\Orm;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;
use Exception;
use Rexp\Form\Infrastructure\Persistence\TableNames;

/**
 * ORM-таблица отправок форм.
 */
class FormSubmissionTable extends DataManager
{
    /**
     * Возвращает имя ORM-таблицы.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return TableNames::submission();
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
            new Entity\StringField('FORM_CODE', [
                'required' => true,
                'size' => 100,
            ]),
            new Entity\IntegerField('ENTRY_ID', [
                'default_value' => 0,
            ]),
            new Entity\StringField('MODE', [
                'required' => true,
                'size' => 20,
                'default_value' => 'create',
            ]),
            new Entity\StringField('STATUS', [
                'required' => true,
                'size' => 30,
                'default_value' => 'processing',
            ]),
            new Entity\TextField('VALUES_JSON', [
                'required' => true,
            ]),
            new Entity\TextField('NORMALIZED_VALUES_JSON', [
                'default_value' => static fn() => '{}',
            ]),
            new Entity\TextField('CONTEXT_JSON', [
                'default_value' => static fn() => '{}',
            ]),
            new Entity\TextField('RESULT_JSON', [
                'default_value' => static fn() => '{}',
            ]),
            new Entity\TextField('ERRORS_JSON', [
                'default_value' => static fn() => '[]',
            ]),
            new Entity\IntegerField('USER_ID', [
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

        return parent::update($primary, $data);
    }
}
