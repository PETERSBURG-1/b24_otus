<?php

namespace Otus\Main\Service;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * Создает элементы бронирования по выбранной процедуре врача.
 */
class BookingService
{
    /**
     * Код инфоблока бронирований.
     */
    private const BOOKING_IBLOCK_CODE = 'booking';

    /**
     * Код свойства пациента.
     */
    private const BOOKING_PATIENT_PROPERTY_CODE = 'PATIENT_NAME';

    /**
     * Код свойства времени записи.
     */
    private const BOOKING_TIME_PROPERTY_CODE = 'APPOINTMENT_TIME';

    /**
     * Код свойства процедуры.
     */
    private const BOOKING_PROCEDURE_PROPERTY_CODE = 'PROCEDURE';

    /**
     * Код свойства врача.
     */
    private const BOOKING_DOCTOR_PROPERTY_CODE = 'DOCTOR';

    /**
     * Создает бронирование пациента на процедуру врача.
     *
     * @param int $doctorId
     * @param int $procedureId
     * @param string $patientName
     * @param string $appointmentTime
     *
     * @return int
     */
    public function createBooking(int $doctorId, int $procedureId, string $patientName, string $appointmentTime): int
    {
        if (!Loader::includeModule('iblock')) {
            throw new SystemException(Loc::getMessage('OTUS_BOOKING_SERVICE_IBLOCK_MODULE_REQUIRED'));
        }

        $doctorId = (int)$doctorId;
        $procedureId = (int)$procedureId;
        $patientName = trim($patientName);
        $appointmentTime = $this->normalizeAppointmentTime($appointmentTime);
        $bookingIblockId = $this->getBookingIblockId();

        if ($doctorId <= 0 || !$this->isActiveElement($doctorId)) {
            throw new SystemException(Loc::getMessage('OTUS_BOOKING_SERVICE_DOCTOR_NOT_FOUND'));
        }

        if ($procedureId <= 0 || !$this->isActiveElement($procedureId)) {
            throw new SystemException(Loc::getMessage('OTUS_BOOKING_SERVICE_PROCEDURE_NOT_FOUND'));
        }

        if ($patientName === '') {
            throw new SystemException(Loc::getMessage('OTUS_BOOKING_SERVICE_EMPTY_PATIENT'));
        }

        if ($appointmentTime === '') {
            throw new SystemException(Loc::getMessage('OTUS_BOOKING_SERVICE_EMPTY_TIME'));
        }

        if ($bookingIblockId <= 0) {
            throw new SystemException(Loc::getMessage('OTUS_BOOKING_SERVICE_BOOKING_IBLOCK_NOT_FOUND'));
        }

        $doctorProcedureService = new DoctorProcedureService();
        if (!$doctorProcedureService->doctorHasProcedure($doctorId, $procedureId)) {
            throw new SystemException(Loc::getMessage('OTUS_BOOKING_SERVICE_PROCEDURE_NOT_ALLOWED'));
        }

        if ($this->isTimeBusy($bookingIblockId, $doctorId, $appointmentTime)) {
            throw new SystemException(Loc::getMessage('OTUS_BOOKING_SERVICE_BUSY_TIME'));
        }

        $element = new \CIBlockElement();
        $bookingId = $element->Add([
            'IBLOCK_ID' => $bookingIblockId,
            'ACTIVE' => 'Y',
            'NAME' => $patientName . ' - ' . $appointmentTime,
            'PROPERTY_VALUES' => [
                self::BOOKING_PATIENT_PROPERTY_CODE => $patientName,
                self::BOOKING_TIME_PROPERTY_CODE => $appointmentTime,
                self::BOOKING_PROCEDURE_PROPERTY_CODE => $procedureId,
                self::BOOKING_DOCTOR_PROPERTY_CODE => $doctorId,
            ],
        ]);

        if (!$bookingId) {
            throw new SystemException((string)$element->LAST_ERROR);
        }

        return (int)$bookingId;
    }

    /**
     * Возвращает идентификатор инфоблока бронирований.
     *
     * @return int
     */
    public function getBookingIblockId(): int
    {
        $row = IblockTable::getRow([
            'select' => ['ID'],
            'filter' => [
                '=CODE' => static::BOOKING_IBLOCK_CODE,
            ],
        ]);

        return (int)($row['ID'] ?? 0);
    }

    /**
     * Нормализует значение даты и времени из popup-формы.
     *
     * @param string $appointmentTime
     *
     * @return string
     */
    private function normalizeAppointmentTime(string $appointmentTime): string
    {
        $appointmentTime = trim($appointmentTime);
        if ($appointmentTime === '') {
            return '';
        }

        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
            'd.m.Y H:i',
            'd.m.Y H:i:s',
            'd.m.Y G:i',
            'd.m.Y G:i:s',
        ];

        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $appointmentTime);

            if ($dateTime instanceof \DateTime) {
                return $dateTime->format('d.m.Y H:i');
            }
        }

        return '';
    }

    /**
     * Проверяет, существует ли активный элемент.
     *
     * @param int $elementId
     *
     * @return bool
     */
    private function isActiveElement(int $elementId): bool
    {
        $row = ElementTable::getRow([
            'select' => ['ID'],
            'filter' => [
                '=ID' => $elementId,
                '=ACTIVE' => 'Y',
            ],
        ]);

        return $row !== null;
    }

    /**
     * Проверяет, занято ли время у врача.
     *
     * @param int $bookingIblockId
     * @param int $doctorId
     * @param string $appointmentTime
     *
     * @return bool
     */
    private function isTimeBusy(int $bookingIblockId, int $doctorId, string $appointmentTime): bool
    {
        $result = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $bookingIblockId,
                'ACTIVE' => 'Y',
                'PROPERTY_' . static::BOOKING_DOCTOR_PROPERTY_CODE => $doctorId,
                'PROPERTY_' . static::BOOKING_TIME_PROPERTY_CODE => $appointmentTime,
            ],
            false,
            ['nTopCount' => 1],
            ['ID']
        );

        return (bool)$result->Fetch();
    }
}
