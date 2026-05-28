<?php

namespace Otus\Main\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * ORM-таблица сущности "Книга".
 */
class BookTable extends DataManager
{
    /**
     * Возвращает имя таблицы.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'otus_rest_book';
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
                ->configureTitle(Loc::getMessage('OTUS_MAIN_BOOK_ID')),
            (new StringField('TITLE'))
                ->configureRequired()
                ->configureSize(255)
                ->configureTitle(Loc::getMessage('OTUS_MAIN_BOOK_TITLE')),
            (new StringField('AUTHOR'))
                ->configureSize(255)
                ->configureTitle(Loc::getMessage('OTUS_MAIN_BOOK_AUTHOR')),
            (new TextField('DESCRIPTION'))
                ->configureTitle(Loc::getMessage('OTUS_MAIN_BOOK_DESCRIPTION')),
            (new DatetimeField('CREATED_AT'))
                ->configureDefaultValue(static function () {
                    return new DateTime();
                })
                ->configureTitle(Loc::getMessage('OTUS_MAIN_BOOK_CREATED_AT')),
            (new DatetimeField('UPDATED_AT'))
                ->configureDefaultValue(static function () {
                    return new DateTime();
                })
                ->configureTitle(Loc::getMessage('OTUS_MAIN_BOOK_UPDATED_AT')),
        ];
    }
}
