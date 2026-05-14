<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle("Конструктор форм");
?>

<?$APPLICATION->IncludeComponent(
    "rexp.form:designer",
    "",
    Array()
);?>

<?require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';?>
