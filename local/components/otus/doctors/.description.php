<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('OTUS_DOCTORS_COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('OTUS_DOCTORS_COMPONENT_DESC'),
    'CACHE_PATH' => 'Y',
    'SORT' => 100,
    'PATH' => [
        'ID' => 'otus',
        'NAME' => Loc::getMessage('OTUS_DOCTORS_COMPONENT_SECTION'),
    ],
];
