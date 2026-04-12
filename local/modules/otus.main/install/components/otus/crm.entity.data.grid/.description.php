<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => GetMessage('OTUS_MAIN_COMPONENT_NAME'),
    'DESCRIPTION' => GetMessage('OTUS_MAIN_COMPONENT_DESCRIPTION'),
    'PATH' => [
        'ID' => 'otus',
        'NAME' => GetMessage('OTUS_MAIN_COMPONENT_SECTION'),
    ],
];
