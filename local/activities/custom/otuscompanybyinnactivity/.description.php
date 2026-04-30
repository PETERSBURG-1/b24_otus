<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arActivityDescription = [
    'NAME' => Loc::getMessage('OTUS_COMPANY_BY_INN_DESCR_NAME'),
    'DESCRIPTION' => Loc::getMessage('OTUS_COMPANY_BY_INN_DESCR_DESCRIPTION'),
    'TYPE' => 'activity',
    'CLASS' => 'OtusCompanyByInnActivity',
    'JSCLASS' => 'BizProcActivity',
    'CATEGORY' => [
        'ID' => 'other',
        'OWN_ID' => 'otus_main',
        'OWN_NAME' => Loc::getMessage('OTUS_COMPANY_BY_INN_DESCR_CATEGORY'),
    ],
    'RETURN' => [
        'CompanyId' => [
            'NAME' => Loc::getMessage('OTUS_COMPANY_BY_INN_DESCR_RETURN_COMPANY_ID'),
            'TYPE' => FieldType::INT,
        ],
        'CompanyTitle' => [
            'NAME' => Loc::getMessage('OTUS_COMPANY_BY_INN_DESCR_RETURN_COMPANY_TITLE'),
            'TYPE' => FieldType::STRING,
        ],
        'ErrorText' => [
            'NAME' => Loc::getMessage('OTUS_COMPANY_BY_INN_DESCR_RETURN_ERROR'),
            'TYPE' => FieldType::STRING,
        ],
    ],
];
