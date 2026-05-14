<?php
if (!class_exists('Bitrix\Main\Loader')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
}
if (!defined('MODULE_ID')) {
    define('MODULE_ID', 'rexp.form');
}
if (!defined('ENTITY')) {
    define('ENTITY', '\\Rexp\\Form\\Bizproc\\FormSubmissionDocument');
}
if (class_exists('Bitrix\Main\Loader')) {
    \Bitrix\Main\Loader::includeModule('rexp.form');
    \Bitrix\Main\Loader::includeModule('crm');
    \Bitrix\Main\Loader::includeModule('bizproc');
    \Bitrix\Main\Loader::includeModule('bizprocdesigner');
}
$fp = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bizproc/admin/bizproc_selector.php';
if (is_file($fp)) {
    require $fp;
}
