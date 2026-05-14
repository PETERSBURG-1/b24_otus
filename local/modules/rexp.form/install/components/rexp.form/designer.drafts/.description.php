<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
$arComponentDescription = [
    'NAME' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_DESCRIPTION_001'),
    'DESCRIPTION' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_DESCRIPTION_002'),
    'PATH' => [
        'ID' => 'rexp.form',
        'NAME' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_DESCRIPTION_003'),
    ],
];
