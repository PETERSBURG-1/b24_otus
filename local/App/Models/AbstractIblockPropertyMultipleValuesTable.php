<?php

namespace App\Models;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;

abstract class AbstractIblockPropertyMultipleValuesTable extends DataManager
{
    public const IBLOCK_ID = null;

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_iblock_element_prop_m' . static::IBLOCK_ID;
    }

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new IntegerField('IBLOCK_ELEMENT_ID'),
            new IntegerField('IBLOCK_PROPERTY_ID'),
            new IntegerField('VALUE'),
            new StringField('DESCRIPTION'),
            new ReferenceField(
                'ELEMENT',
                ElementTable::class,
                ['=this.VALUE' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),
        ];
    }
}
