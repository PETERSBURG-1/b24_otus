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
        'CONTROLLER' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_CONTROLLER'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'rexp:form.Designer',
        ],
    ],
];
