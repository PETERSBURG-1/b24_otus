<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'GROUPS' => [
        'BASE' => ['NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_GROUP_BASE')],
        'URLS' => ['NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_GROUP_URLS')],
        'GRID' => ['NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_GROUP_GRID')],
    ],
    'PARAMETERS' => [
        'FORM_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_FORM_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'GRID_ID' => [
            'PARENT' => 'GRID',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_GRID_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'rexp_form_submissions_grid',
        ],
        'FILTER_ID' => [
            'PARENT' => 'GRID',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_FILTER_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'rexp_form_submissions_filter',
        ],
        'EDITOR_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_EDITOR_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/designer/editor.php',
        ],
    ],
];
