<?php

namespace Otus\Main\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * ORM-класс для работы с внешней таблицей данных CRM.
 */
class CrmEntityDataTable extends DataManager
{
    /**
     * Возвращает имя таблицы.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'otus_crm_entity_data';
    }

    /**
     * Возвращает описание полей таблицы.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete()
                ->configureTitle(Loc::getMessage('OTUS_MAIN_ID')),
            (new IntegerField('ENTITY_TYPE_ID'))
                ->configureRequired()
                ->configureTitle(Loc::getMessage('OTUS_MAIN_ENTITY_TYPE_ID')),
            (new IntegerField('ENTITY_ID'))
                ->configureRequired()
                ->configureTitle(Loc::getMessage('OTUS_MAIN_ENTITY_ID')),
            (new StringField('TITLE'))
                ->configureRequired()
                ->configureSize(255)
                ->configureTitle(Loc::getMessage('OTUS_MAIN_ENTITY_TITLE')),
            (new StringField('VALUE'))
                ->configureSize(255)
                ->configureTitle(Loc::getMessage('OTUS_MAIN_ENTITY_VALUE')),
            (new DatetimeField('CREATED_AT'))
                ->configureDefaultValue(static function () {
                    return new DateTime();
                })
                ->configureTitle(Loc::getMessage('OTUS_MAIN_ENTITY_CREATED_AT')),
        ];
    }
}
