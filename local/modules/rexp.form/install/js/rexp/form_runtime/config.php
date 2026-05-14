<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'js' => 'dist/form-runtime.bundle.js',
    'css' => 'dist/style.css',
    'rel' => ['ajax', 'main.core', 'ui.vue3', 'ui.buttons', 'ui.buttons.icons', 'rexp.form.ui', 'ui.forms', 'ui.alerts', 'ui.notification', 'ui.entity-selector'],
    'skip_core' => false,
    'lang' => '/local/js/rexp/form_runtime/lang/ru/config.php',
];
