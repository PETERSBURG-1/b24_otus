<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);
?>
<div>
    <p>
        <strong><?= htmlspecialcharsbx(Loc::getMessage('OTUS_CURRENCY_RATE_TEMPLATE_SELECTED')) ?>:</strong>
        <?= htmlspecialcharsbx($arResult['CURRENCY_NAME']) ?> (<?= htmlspecialcharsbx($arResult['CURRENCY']) ?>)
    </p>
    <p>
        <strong><?= htmlspecialcharsbx(Loc::getMessage('OTUS_CURRENCY_RATE_TEMPLATE_RATE')) ?>:</strong>
        <?= htmlspecialcharsbx($arResult['RATE_TEXT']) ?>
    </p>
</div>