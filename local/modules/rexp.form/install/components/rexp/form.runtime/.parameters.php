<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'GROUPS' => [
        'BASE' => ['NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_GROUP_BASE')],
    ],
    'PARAMETERS' => [
        'FORM_CODE' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_FORM_CODE'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'FORM_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_FORM_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'CONTROLLER' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_RUNTIME_CONTROLLER'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'rexp:form.Runtime',
        ],
        'TITLE' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_TITLE'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'MODE' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_MODE'),
            'TYPE' => 'LIST',
            'VALUES' => [
                'auto' => Loc::getMessage('REXP_FORM_CMP_PARAMS_MODE_AUTO'),
                'view' => Loc::getMessage('REXP_FORM_CMP_PARAMS_MODE_VIEW'),
            ],
            'DEFAULT' => 'auto',
        ],
        'ENTRY_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_ENTRY_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
    ],
];
