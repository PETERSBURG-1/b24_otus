<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$currencyList = [];

if (Loader::includeModule('currency')) {
    $currencyRows = CurrencyTable::getList([
        'select' => [
            'CURRENCY',
            'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME',
        ],
        'order' => [
            'SORT' => 'ASC',
            'CURRENCY' => 'ASC',
        ],
    ]);

    while ($currency = $currencyRows->fetch()) {
        $currencyCode = (string)$currency['CURRENCY'];
        $currencyName = (string)($currency['FULL_NAME'] ?: $currencyCode);

        $currencyList[$currencyCode] = '[' . $currencyCode . '] ' . $currencyName;
    }
}

$arComponentParameters = [
    'PARAMETERS' => [
        'CURRENCY' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('OTUS_CURRENCY_RATE_PARAM_CURRENCY'),
            'TYPE' => 'LIST',
            'VALUES' => $currencyList,
            'ADDITIONAL_VALUES' => 'N',
            'DEFAULT' => 'USD',
            'REFRESH' => 'N',
        ],
    ],
];
