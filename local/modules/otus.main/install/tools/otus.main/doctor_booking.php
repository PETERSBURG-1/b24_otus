<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Otus\Main\Service\BookingService;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loc::loadMessages(__FILE__);
header('Content-Type: application/json; charset=' . LANG_CHARSET);

$result = [
    'success' => false,
    'message' => '',
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \RuntimeException(Loc::getMessage('OTUS_DOCTOR_BOOKING_TOOL_INVALID_METHOD'));
    }

    if (!check_bitrix_sessid()) {
        throw new \RuntimeException(Loc::getMessage('OTUS_DOCTOR_BOOKING_TOOL_SESSION_EXPIRED'));
    }

    if (!Loader::includeModule('otus.main')) {
        throw new \RuntimeException(Loc::getMessage('OTUS_DOCTOR_BOOKING_TOOL_MODULE_REQUIRED'));
    }

    $service = new BookingService();
    $bookingId = $service->createBooking(
        (int)($_POST['doctor_id'] ?? 0),
        (int)($_POST['procedure_id'] ?? 0),
        (string)($_POST['patient_name'] ?? ''),
        (string)($_POST['appointment_time'] ?? '')
    );

    $result['success'] = true;
    $result['message'] = Loc::getMessage('OTUS_DOCTOR_BOOKING_TOOL_SUCCESS');
    $result['bookingId'] = $bookingId;
} catch (\Throwable $exception) {
    $result['message'] = $exception->getMessage();
}

echo Json::encode($result);
