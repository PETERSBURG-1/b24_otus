<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!check_bitrix_sessid()) {
    return;
}

Loc::loadMessages(__FILE__);
?>
<p><?=Loc::getMessage('OTUS_MAIN_UNINSTALL_MESSAGE');?></p>
