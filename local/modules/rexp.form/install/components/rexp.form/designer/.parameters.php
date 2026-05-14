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
        'VISUAL' => ['NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_GROUP_VISUAL')],
    ],
    'PARAMETERS' => [
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
        'SETTINGS_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_SETTINGS_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/designer/settings.php',
        ],
        'MODULE_SETTINGS_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_MODULE_SETTINGS_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/designer/module-settings.php',
        ],
        'SUBMISSIONS_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_SUBMISSIONS_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/designer/submissions.php',
        ],
        'BIZPROC_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_BIZPROC_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/designer/bizproc.php',
        ],
        'VERSIONS_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_VERSIONS_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/designer/versions.php',
        ],
        'DRAFTS_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_DRAFTS_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/designer/drafts.php',
        ],
        'RUNTIME_URL' => [
            'PARENT' => 'URLS',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_RUNTIME_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/forms/runtime/index.php',
        ],
        'TITLE' => [
            'PARENT' => 'VISUAL',
            'NAME' => Loc::getMessage('REXP_FORM_CMP_PARAMS_TITLE'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
    ],
];
