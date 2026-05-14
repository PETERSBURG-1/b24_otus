<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Rexp\Form\Application\Service\FormModuleOptionsService;

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_TITLE'));
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-background no-all-paddings');

\Bitrix\Main\Loader::includeModule('ui');
Extension::load(['rexp.form.ui', 'ui.notification']);
Toolbar::deleteFavoriteStar();

$options = (array)($arResult['OPTIONS'] ?? []);
$isEnabled = static fn(string $name): bool => !empty($options[$name]);
?>

<div class="rexp-form-module-settings">
    <?php if (!empty($arResult['SAVED'])): ?>
        <div class="ui-alert ui-alert-success"><span class="ui-alert-message"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_SAVED'))?></span></div>
    <?php endif; ?>

    <form method="post">
        <?=bitrix_sessid_post()?>
        <input type="hidden" name="save_module_settings" value="Y">

        <div class="rexp-form-module-settings__card">
            <div class="rexp-form-module-settings__card-title"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_FEATURES'))?></div>

            <label class="rexp-form-module-settings__option">
                <input type="checkbox" name="<?=htmlspecialcharsbx(FormModuleOptionsService::OPTION_SUBMISSION_LOG_ENABLED)?>" value="Y" <?=$isEnabled(FormModuleOptionsService::OPTION_SUBMISSION_LOG_ENABLED) ? 'checked' : ''?>>
                <span>
                    <span class="rexp-form-module-settings__option-title"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_SUBMISSION_LOG'))?></span>
                </span>
            </label>

            <label class="rexp-form-module-settings__option">
                <input type="checkbox" name="<?=htmlspecialcharsbx(FormModuleOptionsService::OPTION_DRAFTS_ENABLED)?>" value="Y" <?=$isEnabled(FormModuleOptionsService::OPTION_DRAFTS_ENABLED) ? 'checked' : ''?>>
                <span>
                    <span class="rexp-form-module-settings__option-title"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_DRAFTS'))?></span>
                </span>
            </label>

            <label class="rexp-form-module-settings__option">
                <input type="checkbox" name="<?=htmlspecialcharsbx(FormModuleOptionsService::OPTION_VERSIONS_ENABLED)?>" value="Y" <?=$isEnabled(FormModuleOptionsService::OPTION_VERSIONS_ENABLED) ? 'checked' : ''?>>
                <span>
                    <span class="rexp-form-module-settings__option-title"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_VERSIONS'))?></span>
                </span>
            </label>
        </div>

        <div class="rexp-form-module-settings__card">
            <div class="rexp-form-module-settings__card-title"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_EDITOR'))?></div>
            <label class="rexp-form-module-settings__option">
                <input type="checkbox" name="<?=htmlspecialcharsbx(FormModuleOptionsService::OPTION_EDITOR_COUNTERS_ENABLED)?>" value="Y" <?=$isEnabled(FormModuleOptionsService::OPTION_EDITOR_COUNTERS_ENABLED) ? 'checked' : ''?>>
                <span>
                    <span class="rexp-form-module-settings__option-title"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_COUNTERS'))?></span>
                </span>
            </label>
        </div>

        <div class="rexp-form-module-settings__actions">
            <button type="submit" class="ui-btn ui-btn-success"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_SAVE'))?></button>
            <a href="/forms/designer/" class="ui-btn ui-btn-light-border"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_MODULE_SETTINGS_BACK'))?></a>
        </div>
    </form>
</div>
