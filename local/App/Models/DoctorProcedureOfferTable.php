<?php

namespace App\Models;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\ORM\Query\Join;

Loc::LoadMessages(__FILE__);

/**
 * ORM-модель таблицы связей врача и процедуры.
 *
 * Таблица хранит собственные поля и привязки к двум инфоблокам:
 * - Врачи
 * - Процедуры
 */
class DoctorProcedureOfferTable extends DataManager
{
    /**
     * Возвращает имя таблицы в базе данных.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'otus_doctor_procedure_offer';
    }

    /**
     * Возвращает карту ORM-полей сущности.
     *
     * @return array<int, object>
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete()
                ->configureTitle(Loc::getMessage('OFFER_FIELD_ID')),

            (new IntegerField('DOCTOR_ID'))
                ->configureRequired()
                ->addValidator(new RangeValidator(1, null))
                ->configureTitle(Loc::getMessage('OFFER_FIELD_DOCTOR_ID')),

            (new IntegerField('PROCEDURE_ID'))
                ->configureRequired()
                ->addValidator(new RangeValidator(1, null))
                ->configureTitle(Loc::getMessage('OFFER_FIELD_PROCEDURE_ID')),

            (new IntegerField('PRICE'))
                ->configureRequired()
                ->configureDefaultValue(0)
                ->addValidator(new RangeValidator(0, 1000000))
                ->configureTitle(Loc::getMessage('OFFER_FIELD_PRICE')),

            (new StringField('CABINET'))
                ->configureRequired()
                ->configureSize(50)
                ->addValidator(new LengthValidator(1, 50))
                ->configureTitle(Loc::getMessage('OFFER_FIELD_CABINET')),

            (new IntegerField('SORT'))
                ->configureDefaultValue(500)
                ->addValidator(new RangeValidator(0, 100000))
                ->configureTitle(Loc::getMessage('OFFER_FIELD_SORT')),

            (new Reference(
                'DOCTOR',
                DoctorsPropertyValuesTable::class,
                Join::on('this.DOCTOR_ID', 'ref.IBLOCK_ELEMENT_ID')
            ))->configureJoinType('inner'),

            (new Reference(
                'PROCEDURE',
                ProceduresPropertyValuesTable::class,
                Join::on('this.PROCEDURE_ID', 'ref.IBLOCK_ELEMENT_ID')
            ))->configureJoinType('inner'),
        ];
    }
}