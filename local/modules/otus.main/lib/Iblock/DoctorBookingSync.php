<?php

namespace Otus\Main\Iblock;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;

/**
 * Синхронизирует значение пользовательского свойства с идентификатором элемента врача.
 */
class DoctorBookingSync
{
    /**
     * Код пользовательского типа свойства.
     */
    private const USER_TYPE = 'otus_doctor_booking';

    /**
     * Флаг защиты от повторного запуска синхронизации.
     *
     * @var bool
     */
    private static bool $isSyncRunning = false;

    /**
     * Запускает синхронизацию после добавления элемента.
     *
     * @param array<string, mixed> $fields
     *
     * @return void
     */
    public static function onAfterIBlockElementAdd(array &$fields): void
    {
        static::sync($fields);
    }

    /**
     * Запускает синхронизацию после обновления элемента.
     *
     * @param array<string, mixed> $fields
     *
     * @return void
     */
    public static function onAfterIBlockElementUpdate(array &$fields): void
    {
        static::sync($fields);
    }

    /**
     * Записывает ID элемента в пользовательские свойства типа otus_doctor_booking.
     *
     * @param array<string, mixed> $fields
     *
     * @return void
     */
    private static function sync(array $fields): void
    {
        if (static::$isSyncRunning || !Loader::includeModule('iblock')) {
            return;
        }

        $elementId = (int)($fields['ID'] ?? 0);
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);

        if ($elementId <= 0 || $iblockId <= 0) {
            return;
        }

        $propertyCodes = static::getBookingPropertyCodes($iblockId);
        if ($propertyCodes === []) {
            return;
        }

        $propertyValues = [];
        foreach ($propertyCodes as $propertyCode) {
            $propertyValues[$propertyCode] = (string)$elementId;
        }

        static::$isSyncRunning = true;
        \CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $propertyValues);
        static::$isSyncRunning = false;
    }

    /**
     * Возвращает коды пользовательских свойств бронирования для инфоблока.
     *
     * @param int $iblockId
     *
     * @return array<int, string>
     */
    private static function getBookingPropertyCodes(int $iblockId): array
    {
        $rows = PropertyTable::getList([
            'select' => ['CODE'],
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=USER_TYPE' => static::USER_TYPE,
            ],
        ])->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $propertyCode = (string)($row['CODE'] ?? '');
            if ($propertyCode !== '') {
                $result[] = $propertyCode;
            }
        }

        return $result;
    }
}
