<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => Loc::getMessage('REXP_FORM_COMPAT_COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('REXP_FORM_COMPAT_COMPONENT_DESCRIPTION'),
    'PATH' => [
        'ID' => 'rexp_form',
        'NAME' => Loc::getMessage('REXP_FORM_COMPAT_COMPONENT_SECTION'),
    ],
];
