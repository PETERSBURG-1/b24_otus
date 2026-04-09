<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->SetTitle('Курсы валют');
?>
<h1><? $APPLICATION->ShowTitle(); ?></h1>
<?
$APPLICATION->IncludeComponent(
    'otus:currency.rate',
    '.default',
    [
        'CURRENCY' => 'USD',
    ]
);
?>
<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>
