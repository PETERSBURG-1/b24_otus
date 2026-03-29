<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'GROUPS' => [
        'DATA_SOURCE' => ['NAME' => Loc::getMessage('OTUS_DOCTORS_PARAMS_GROUP_DATA_SOURCE')],
        'SEF' => ['NAME' => Loc::getMessage('OTUS_DOCTORS_PARAMS_GROUP_SEF')],
    ],
    'PARAMETERS' => [
        'IBLOCK_DOCTORS_ID' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => Loc::getMessage('OTUS_DOCTORS_PARAM_IBLOCK_DOCTORS_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '16',
        ],
        'IBLOCK_PROCEDURES_ID' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => Loc::getMessage('OTUS_DOCTORS_PARAM_IBLOCK_PROCEDURES_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '17',
        ],
        'PROP_PROCEDURES_CODE' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => Loc::getMessage('OTUS_DOCTORS_PARAM_PROP_PROCEDURES_CODE'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'PROCEDURES',
        ],
        'SEF_MODE' => [
            'PARENT' => 'SEF',
            'NAME' => Loc::getMessage('OTUS_DOCTORS_PARAM_SEF_MODE'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
            'REFRESH' => 'Y',
        ],
        'SEF_FOLDER' => [
            'PARENT' => 'SEF',
            'NAME' => Loc::getMessage('OTUS_DOCTORS_PARAM_SEF_FOLDER'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/doctors/',
        ],
        'SEF_URL_TEMPLATES' => [
            'PARENT' => 'SEF',
            'NAME' => Loc::getMessage('OTUS_DOCTORS_PARAM_SEF_URL_TEMPLATES'),
            'TYPE' => 'CUSTOM',
            'DEFAULT' => [
                'list' => '',
                'detail' => '#ID#/',
            ],
        ],
    ],
];
