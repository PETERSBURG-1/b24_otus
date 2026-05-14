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
    ],
    'PARAMETERS' => [
        'FORM_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_FORM_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'CONTROLLER' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_CONTROLLER'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'rexp:form.Designer',
        ],
        'EDITOR_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_EDITOR_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/designer/editor.php',
        ],
        'EMBED' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_EMBED'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
    ],
];
