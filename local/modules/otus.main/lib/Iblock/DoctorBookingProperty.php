<?php

namespace Otus\Main\Iblock;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Otus\Main\Service\DoctorProcedureService;

Loc::loadMessages(__FILE__);

/**
 * Пользовательский тип свойства для быстрого создания бронирования из списка врачей.
 */
class DoctorBookingProperty
{
    /**
     * Возвращает описание пользовательского типа свойства.
     *
     * @return array<string, mixed>
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'otus_doctor_booking',
            'DESCRIPTION' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_DESCRIPTION'),
            'GetPropertyFieldHtml' => [static::class, 'getPropertyFieldHtml'],
            'GetPublicEditHTML' => [static::class, 'getPublicEditHtml'],
            'GetPublicViewHTML' => [static::class, 'getPublicViewHtml'],
            'GetAdminListViewHTML' => [static::class, 'getAdminListViewHtml'],
            'ConvertToDB' => [static::class, 'convertToDb'],
            'ConvertFromDB' => [static::class, 'convertFromDb'],
        ];
    }

    /**
     * Возвращает HTML свойства в административной форме редактирования элемента.
     *
     * @param array<string, mixed> $property Свойство инфоблока.
     * @param array<string, mixed> $value Значение свойства.
     * @param array<string, string> $controlName Имена HTML-контролов.
     *
     * @return string
     */
    public static function getPropertyFieldHtml(array $property, array $value, array $controlName): string
    {
        return static::renderField($value, $controlName);
    }

    /**
     * Возвращает HTML свойства в публичной форме редактирования элемента.
     *
     * @param array<string, mixed> $property Свойство инфоблока.
     * @param array<string, mixed> $value Значение свойства.
     * @param array<string, string> $controlName Имена HTML-контролов.
     *
     * @return string
     */
    public static function getPublicEditHtml(array $property, array $value, array $controlName): string
    {
        return static::renderField($value, $controlName);
    }

    /**
     * Возвращает HTML свойства в публичном списке.
     *
     * @param array<string, mixed> $property Свойство инфоблока.
     * @param array<string, mixed> $value Значение свойства.
     * @param array<string, string> $controlName Имена HTML-контролов.
     *
     * @return string
     */
    public static function getPublicViewHtml(array $property, array $value, array $controlName): string
    {
        return static::renderField($value, $controlName);
    }

    /**
     * Возвращает HTML свойства в административном списке элементов.
     *
     * @param array<string, mixed> $property Свойство инфоблока.
     * @param array<string, mixed> $value Значение свойства.
     * @param array<string, string> $controlName Имена HTML-контролов.
     *
     * @return string
     */
    public static function getAdminListViewHtml(array $property, array $value, array $controlName): string
    {
        return static::renderField($value, $controlName);
    }

    /**
     * Подготавливает значение свойства к сохранению в базе.
     *
     * @param array<string, mixed> $property Свойство инфоблока.
     * @param array<string, mixed> $value Значение свойства.
     *
     * @return array<string, mixed>
     */
    public static function convertToDb(array $property, array $value): array
    {
        return [
            'VALUE' => (string)(int)($value['VALUE'] ?? 0),
            'DESCRIPTION' => '',
        ];
    }

    /**
     * Подготавливает значение свойства после чтения из базы.
     *
     * @param array<string, mixed> $property Свойство инфоблока.
     * @param array<string, mixed> $value Значение свойства.
     *
     * @return array<string, mixed>
     */
    public static function convertFromDb(array $property, array $value): array
    {
        return [
            'VALUE' => (string)(int)($value['VALUE'] ?? 0),
            'DESCRIPTION' => '',
        ];
    }

    /**
     * Рендерит кнопку открытия окна бронирования.
     *
     * @param array<string, mixed> $value Значение свойства.
     * @param array<string, string> $controlName Имена HTML-контролов.
     *
     * @return string
     */
    private static function renderField(array $value, array $controlName): string
    {
        $doctorId = static::resolveDoctorId($value);
        $inputName = (string)($controlName['VALUE'] ?? '');
        $input = $inputName !== ''
            ? '<input type="hidden" name="' . htmlspecialcharsbx($inputName) . '" value="' . $doctorId . '">' 
            : '';

        if ($doctorId <= 0) {
            return $input . '<span>' . htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_SAVE_FIRST')) . '</span>';
        }

        $service = new DoctorProcedureService();
        $procedures = $service->getDoctorProcedures($doctorId);

        if ($procedures === []) {
            return $input . '<span>' . htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_EMPTY')) . '</span>';
        }

        Extension::load('otus.main.doctorbooking');
        \CJSCore::Init(['popup', 'date']);

        $messages = \CUtil::PhpToJSObject([
            'OTUS_DOCTOR_BOOKING_TITLE' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_POPUP_TITLE'),
            'OTUS_DOCTOR_BOOKING_PATIENT' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_PATIENT'),
            'OTUS_DOCTOR_BOOKING_TIME' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_TIME'),
            'OTUS_DOCTOR_BOOKING_PROCEDURE' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_PROCEDURE'),
            'OTUS_DOCTOR_BOOKING_SAVE' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_SAVE_BUTTON'),
            'OTUS_DOCTOR_BOOKING_CANCEL' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_CANCEL_BUTTON'),
            'OTUS_DOCTOR_BOOKING_REQUIRED' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_REQUIRED'),
            'OTUS_DOCTOR_BOOKING_SUCCESS' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_SUCCESS'),
            'OTUS_DOCTOR_BOOKING_REQUEST_ERROR' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_REQUEST_ERROR'),
            'OTUS_DOCTOR_BOOKING_OPEN' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_OPEN_BUTTON'),
            'OTUS_DOCTOR_BOOKING_SAVING' => Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_SAVING'),
        ]);

        return $input
            . '<script>BX.message(' . $messages . ');</script>'
            . '<div class="otus-doctor-booking">'
            . '<button'
            . ' type="button"'
            . ' class="ui-btn ui-btn-xs ui-btn-primary otus-doctor-booking-open"'
            . ' data-url="/bitrix/tools/otus.main/doctor_booking.php"'
            . ' data-doctor-id="' . $doctorId . '"'
            . ' data-procedures="' . htmlspecialcharsbx(Json::encode($procedures)) . '"'
            . ' onmousedown="event.preventDefault(); event.stopPropagation(); return false;"'
            . ' onclick="return window.OtusDoctorBooking && window.OtusDoctorBooking.open(this, event);"'
            . '>'
            . htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTOR_BOOKING_PROPERTY_OPEN_BUTTON'))
            . '</button>'
            . '</div>';
    }

    /**
     * Определяет идентификатор врача из значения свойства или параметров запроса.
     *
     * @param array<string, mixed> $value Значение свойства.
     *
     * @return int
     */
    private static function resolveDoctorId(array $value): int
    {
        $doctorId = (int)($value['VALUE'] ?? 0);
        if ($doctorId > 0) {
            return $doctorId;
        }

        foreach (['element_id', 'ELEMENT_ID', 'ID'] as $requestKey) {
            $doctorId = (int)($_REQUEST[$requestKey] ?? 0);
            if ($doctorId > 0) {
                return $doctorId;
            }
        }

        return 0;
    }
}
