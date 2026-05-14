<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'js' => 'editor.bundle.js',
    'css' => 'style.css',
    'rel' => ['ajax', 'main.core', 'ui.buttons', 'ui.buttons.icons', 'rexp.form.ui', 'ui.forms', 'ui.notification'],
    'skip_core' => false,
    'lang' => '/local/js/rexp/form/editor/lang/ru/config.php',
];
