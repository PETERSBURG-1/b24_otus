<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'css' => 'style.css',
    'rel' => [
        'main.core',
        'ui.buttons',
        'ui.buttons.icons',
        'ui.forms',
        'ui.alerts',
    ],
    'skip_core' => false,
];
