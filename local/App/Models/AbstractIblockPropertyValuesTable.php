<?php

namespace App\Models;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\SystemException;
use CIBlockElement;

/**
 * Абстрактная ORM-модель таблицы свойств инфоблока.
 *
 * Работает с таблицами вида b_iblock_element_prop_sXX и b_iblock_element_prop_mXX.
 */
abstract class AbstractIblockPropertyValuesTable extends DataManager
{
    public const IBLOCK_ID = null;

    protected static ?array $properties = null;
    protected static ?CIBlockElement $iblockElement = null;

    /**
     * Возвращает имя single-таблицы свойств инфоблока.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_iblock_element_prop_s' . static::IBLOCK_ID;
    }

    /**
     * Возвращает имя multiple-таблицы свойств инфоблока.
     *
     * @return string
     */
    public static function getTableNameMulti(): string
    {
        return 'b_iblock_element_prop_m' . static::IBLOCK_ID;
    }

    /**
     * Возвращает ORM-карту полей таблицы свойств инфоблока.
     *
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        $cache = Cache::createInstance();
        $cacheDir = 'iblock_property_map/' . static::IBLOCK_ID;
        $multipleValuesTableClass = static::getMultipleValuesTableClass();
        static::initMultipleValuesTableClass();

        if ($cache->initCache(3600, md5($cacheDir), $cacheDir)) {
            $map = $cache->getVars();
        } else {
            $cache->startDataCache();

            $map['IBLOCK_ELEMENT_ID'] = new IntegerField('IBLOCK_ELEMENT_ID', ['primary' => true]);
            $map['ELEMENT'] = new ReferenceField(
                'ELEMENT',
                ElementTable::class,
                ['=this.IBLOCK_ELEMENT_ID' => 'ref.ID']
            );

            foreach (static::getProperties() as $property) {
                if ($property['MULTIPLE'] === 'Y') {
                    $map[$property['CODE']] = new ExpressionField(
                        $property['CODE'],
                        sprintf(
                            '(select group_concat(`VALUE` SEPARATOR "\0") as VALUE from %s as m where m.IBLOCK_ELEMENT_ID = %s and m.IBLOCK_PROPERTY_ID = %d)',
                            static::getTableNameMulti(),
                            '%s',
                            $property['ID']
                        ),
                        ['IBLOCK_ELEMENT_ID'],
                        ['fetch_data_modification' => [static::class, 'getMultipleFieldValueModifier']]
                    );

                    if ($property['USER_TYPE'] === 'EList') {
                        $map[$property['CODE'] . '_ELEMENT_NAME'] = new ExpressionField(
                            $property['CODE'] . '_ELEMENT_NAME',
                            sprintf(
                                '(select group_concat(e.NAME SEPARATOR "\0") as VALUE from %s as m join b_iblock_element as e on m.VALUE = e.ID where m.IBLOCK_ELEMENT_ID = %s and m.IBLOCK_PROPERTY_ID = %d)',
                                static::getTableNameMulti(),
                                '%s',
                                $property['ID']
                            ),
                            ['IBLOCK_ELEMENT_ID'],
                            ['fetch_data_modification' => [static::class, 'getMultipleFieldValueModifier']]
                        );
                    }

                    $map[$property['CODE'] . '|SINGLE'] = new ReferenceField(
                        $property['CODE'] . '|SINGLE',
                        $multipleValuesTableClass,
                        [
                            '=this.IBLOCK_ELEMENT_ID' => 'ref.IBLOCK_ELEMENT_ID',
                            '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?i', $property['ID']),
                        ]
                    );

                    continue;
                }

                if ($property['PROPERTY_TYPE'] == PropertyTable::TYPE_NUMBER) {
                    $map[$property['CODE']] = new IntegerField('PROPERTY_' . $property['ID']);
                } elseif ($property['USER_TYPE'] === 'Date') {
                    $map[$property['CODE']] = new DatetimeField('PROPERTY_' . $property['ID']);
                } else {
                    $map[$property['CODE']] = new StringField('PROPERTY_' . $property['ID']);
                }

                if ($property['PROPERTY_TYPE'] === 'E' && ($property['USER_TYPE'] === 'EList' || $property['USER_TYPE'] === null)) {
                    $map[$property['CODE'] . '_ELEMENT'] = new ReferenceField(
                        $property['CODE'] . '_ELEMENT',
                        ElementTable::class,
                        ['=this.' . $property['CODE'] => 'ref.ID']
                    );
                }
            }

            if (empty($map)) {
                $cache->abortDataCache();
            } else {
                $cache->endDataCache($map);
            }
        }

        return $map;
    }

    /**
     * Возвращает активные элементы инфоблока.
     *
     * @return array<int, array{ID:int, NAME:string}>
     */
    public static function getActiveElementsList(): array
    {
        $rows = static::getList([
            'select' => [
                'ID' => 'IBLOCK_ELEMENT_ID',
                'NAME' => 'ELEMENT.NAME',
            ],
            'filter' => [
                '=ELEMENT.ACTIVE' => 'Y',
            ],
            'order' => [
                'ELEMENT.NAME' => 'ASC',
            ],
        ])->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'ID' => (int)$row['ID'],
                'NAME' => (string)$row['NAME'],
            ];
        }

        return $result;
    }

    /**
     * Возвращает элемент инфоблока по идентификатору.
     *
     * @param int $id
     *
     * @return array{ID:int, NAME:string}|null
     */
    public static function getElementById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = static::getList([
            'select' => [
                'ID' => 'IBLOCK_ELEMENT_ID',
                'NAME' => 'ELEMENT.NAME',
            ],
            'filter' => [
                '=IBLOCK_ELEMENT_ID' => $id,
                '=ELEMENT.ACTIVE' => 'Y',
            ],
            'limit' => 1,
        ])->fetch();

        if (!$row) {
            return null;
        }

        return [
            'ID' => (int)$row['ID'],
            'NAME' => (string)$row['NAME'],
        ];
    }

    /**
     * Добавляет элемент инфоблока.
     *
     * @param array $data
     *
     * @return int ID созданного элемента или 0 при ошибке
     */
    public static function add(array $data): int
    {
        static::$iblockElement ??= new CIBlockElement();

        $fields = [
            'NAME' => (string)($data['NAME'] ?? ''),
            'IBLOCK_ID' => static::IBLOCK_ID,
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => $data,
        ];

        $id = static::$iblockElement->Add($fields);

        return $id ? (int)$id : 0;
    }

    /**
     * Обновляет имя элемента инфоблока.
     *
     * @param int $id
     * @param string $name
     *
     * @return bool
     */
    public static function updateElementName(int $id, string $name): bool
    {
        if ($id <= 0 || $name === '') {
            return false;
        }

        static::$iblockElement ??= new CIBlockElement();

        return (bool)static::$iblockElement->Update($id, ['NAME' => $name]);
    }

    /**
     * Удаляет элемент.
     *
     * @param mixed $primary
     *
     * @return DeleteResult
     * @throws NotImplementedException
     */
    public static function delete($primary): DeleteResult
    {
        throw new NotImplementedException();
    }

    /**
     * Возвращает свойства инфоблока.
     *
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     */
    public static function getProperties(): array
    {
        if (isset(static::$properties[static::IBLOCK_ID])) {
            return static::$properties[static::IBLOCK_ID];
        }

        $dbResult = PropertyTable::query()
            ->setSelect(['ID', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'NAME', 'USER_TYPE'])
            ->where('IBLOCK_ID', static::IBLOCK_ID)
            ->exec();

        while ($row = $dbResult->fetch()) {
            static::$properties[static::IBLOCK_ID][$row['CODE']] = $row;
        }

        return static::$properties[static::IBLOCK_ID] ?? [];
    }

    /**
     * Возвращает ID свойства по коду.
     *
     * @param string $code
     *
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getPropertyId(string $code): int
    {
        return (int)static::getProperties()[$code]['ID'];
    }

    /**
     * Возвращает модификатор для множественных значений свойства.
     *
     * @return array
     */
    public static function getMultipleFieldValueModifier(): array
    {
        return [static fn($value) => array_filter(explode("\0", (string)$value))];
    }

    /**
     * Очищает кеш карты свойств инфоблока.
     *
     * @param int|null $iblockId
     *
     * @return void
     */
    public static function clearPropertyMapCache(?int $iblockId = null): void
    {
        $iblockId = $iblockId ?: static::IBLOCK_ID;
        if (empty($iblockId)) {
            return;
        }

        Cache::clearCache(true, 'iblock_property_map/' . $iblockId);
    }

    /**
     * Возвращает варианты списочного свойства.
     *
     * @param string $propertyCode
     * @param string $byKey
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getEnumPropertyOptions(string $propertyCode, string $byKey = 'ID'): array
    {
        $dbResult = PropertyEnumerationTable::getList([
            'select' => ['ID', 'VALUE', 'XML_ID', 'SORT'],
            'filter' => ['=PROPERTY.CODE' => $propertyCode, 'PROPERTY.IBLOCK_ID' => static::IBLOCK_ID],
        ]);

        while ($row = $dbResult->fetch()) {
            $enumPropertyOptions[$row[$byKey]] = $row;
        }

        return $enumPropertyOptions ?? [];
    }

    /**
     * Возвращает имя ORM-класса для множественных свойств.
     *
     * @return string
     */
    private static function getMultipleValuesTableClass(): string
    {
        $parts = explode('\\', static::class);
        $className = end($parts);
        $namespace = str_replace('\\' . $className, '', static::class);
        $className = str_replace('Table', 'MultipleTable', $className);

        return $namespace . '\\' . $className;
    }

    /**
     * Инициализирует ORM-класс таблицы множественных свойств.
     *
     * @return void
     */
    private static function initMultipleValuesTableClass(): void
    {
        class_exists(static::getMultipleValuesTableClass());
    }
}
