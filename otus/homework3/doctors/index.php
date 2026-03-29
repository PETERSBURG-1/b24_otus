<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Врачи");

$APPLICATION->IncludeComponent(
    'otus:doctors',
    '.default',
    [
        'IBLOCK_DOCTORS_ID' => 16,
        'IBLOCK_PROCEDURES_ID' => 17,
        'PROP_PROCEDURES_CODE' => 'PROCEDURES',
        'SEF_MODE' => 'Y',
        'SEF_FOLDER' => '/doctors/',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'detail' => '#ID#/',
        ],
    ]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");