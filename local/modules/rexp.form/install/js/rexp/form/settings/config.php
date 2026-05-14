<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'js' => 'settings.bundle.js',
    'css' => 'style.css',
    'rel' => ['ajax', 'main.core', 'ui.buttons', 'ui.forms', 'ui.notification'],
    'skip_core' => false,
    'lang' => '/local/js/rexp/form/settings/lang/ru/config.php',
];
