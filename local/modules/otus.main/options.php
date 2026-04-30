<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!$USER->IsAdmin()) {
    return;
}

Loc::loadMessages(__FILE__);

$moduleId = 'otus.main';
Loader::includeModule($moduleId);
$request = Context::getCurrent()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    Option::set($moduleId, 'dadata_token', trim((string)$request->getPost('dadata_token')));
    Option::set($moduleId, 'dadata_secret', trim((string)$request->getPost('dadata_secret')));

    CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('OTUS_MAIN_OPTIONS_SAVED'),
        'TYPE' => 'OK',
    ]);
}

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('OTUS_MAIN_OPTIONS_TAB_DADATA'),
        'TITLE' => Loc::getMessage('OTUS_MAIN_OPTIONS_TAB_DADATA_TITLE'),
    ],
]);
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td width="40%">
            <label for="dadata_token"><?= Loc::getMessage('OTUS_MAIN_OPTIONS_DADATA_TOKEN') ?></label>
        </td>
        <td width="60%">
            <input type="text" name="dadata_token" id="dadata_token" size="60" value="<?= htmlspecialcharsbx(Option::get($moduleId, 'dadata_token', '')) ?>">
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="dadata_secret"><?= Loc::getMessage('OTUS_MAIN_OPTIONS_DADATA_SECRET') ?></label>
        </td>
        <td width="60%">
            <input type="password" name="dadata_secret" id="dadata_secret" size="60" value="<?= htmlspecialcharsbx(Option::get($moduleId, 'dadata_secret', '')) ?>">
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="<?= Loc::getMessage('MAIN_SAVE') ?>" class="adm-btn-save">
    <?php $tabControl->End(); ?>
</form>
