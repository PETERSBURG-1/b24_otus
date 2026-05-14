<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

if (!defined('PUBLIC_AJAX_MODE')) {
    define('PUBLIC_AJAX_MODE', true);
}

$APPLICATION->SetTitle("Форма");
?>

<?$APPLICATION->IncludeComponent(
    "rexp.form:runtime",
    "",
    Array(
        "FORM_CODE" => (string)($_REQUEST['CODE'] ?? $_REQUEST['code'] ?? ''),
    )
);?>

<?require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';?>
