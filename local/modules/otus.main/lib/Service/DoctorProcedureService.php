<?php

namespace Otus\Main\Service;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

/**
 * Сервис получения процедур врача.
 */
class DoctorProcedureService
{
    /**
     * Код свойства процедур у врача.
     */
    private const PROCEDURES_PROPERTY_CODE = 'PROCEDURES';

    /**
     * Возвращает процедуры врача.
     *
     * @param int $doctorId Идентификатор врача.
     *
     * @return array<int, array{ID:int, NAME:string}>
     */
    public function getDoctorProcedures(int $doctorId): array
    {
        $doctorId = (int)$doctorId;
        if ($doctorId <= 0) {
            return [];
        }

        $doctorIblockId = $this->getDoctorIblockId($doctorId);
        if ($doctorIblockId <= 0) {
            return [];
        }

        $procedureIds = $this->getDoctorProcedureIds($doctorIblockId, $doctorId);
        if ($procedureIds === []) {
            return [];
        }

        $rows = ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => [
                '@ID' => $procedureIds,
                '=ACTIVE' => 'Y',
            ],
            'order' => ['NAME' => 'ASC'],
        ])->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $procedureId = (int)($row['ID'] ?? 0);
            $procedureName = (string)($row['NAME'] ?? '');

            if ($procedureId > 0 && $procedureName !== '') {
                $result[] = [
                    'ID' => $procedureId,
                    'NAME' => $procedureName,
                ];
            }
        }

        return $result;
    }

    /**
     * Проверяет, привязана ли процедура к врачу.
     *
     * @param int $doctorId Идентификатор врача.
     * @param int $procedureId Идентификатор процедуры.
     *
     * @return bool
     */
    public function doctorHasProcedure(int $doctorId, int $procedureId): bool
    {
        $doctorId = (int)$doctorId;
        $procedureId = (int)$procedureId;

        if ($doctorId <= 0 || $procedureId <= 0) {
            return false;
        }

        $doctorIblockId = $this->getDoctorIblockId($doctorId);
        if ($doctorIblockId <= 0) {
            return false;
        }

        return in_array($procedureId, $this->getDoctorProcedureIds($doctorIblockId, $doctorId), true);
    }

    /**
     * Возвращает идентификатор инфоблока врача.
     *
     * @param int $doctorId Идентификатор врача.
     *
     * @return int
     */
    public function getDoctorIblockId(int $doctorId): int
    {
        if (!Loader::includeModule('iblock')) {
            throw new SystemException('iblock module is required');
        }

        $row = ElementTable::getRow([
            'select' => ['IBLOCK_ID'],
            'filter' => [
                '=ID' => $doctorId,
                '=ACTIVE' => 'Y',
            ],
        ]);

        return (int)($row['IBLOCK_ID'] ?? 0);
    }

    /**
     * Возвращает идентификаторы процедур врача.
     *
     * @param int $iblockId Идентификатор инфоблока врачей.
     * @param int $doctorId Идентификатор врача.
     *
     * @return array<int, int>
     */
    private function getDoctorProcedureIds(int $iblockId, int $doctorId): array
    {
        $result = [];

        $propertyResult = \CIBlockElement::GetProperty(
            $iblockId,
            $doctorId,
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['CODE' => self::PROCEDURES_PROPERTY_CODE]
        );

        while ($row = $propertyResult->Fetch()) {
            $procedureId = (int)($row['VALUE'] ?? 0);
            if ($procedureId > 0) {
                $result[$procedureId] = $procedureId;
            }
        }

        return array_values($result);
    }
}
