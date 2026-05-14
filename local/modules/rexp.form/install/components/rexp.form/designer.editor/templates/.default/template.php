<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
\Bitrix\Main\UI\Extension::load(['main.core', 'main.core.events', 'ui.buttons', 'ui.buttons.icons', 'ui.notification', 'rexp.form.editor']);
$uid = 'rexp-form-editor-' . substr(md5(uniqid('', true)), 0, 8);
$messages = [
    'REXP_FORM_EDITOR_FORM_LABEL' => Loc::getMessage('REXP_FORM_EDITOR_FORM_LABEL'),
    'REXP_FORM_EDITOR_NEW_FORM' => Loc::getMessage('REXP_FORM_EDITOR_NEW_FORM'),
    'REXP_FORM_EDITOR_DIRTY_HINT' => Loc::getMessage('REXP_FORM_EDITOR_DIRTY_HINT'),
    'REXP_FORM_EDITOR_DIAGNOSTICS' => Loc::getMessage('REXP_FORM_EDITOR_DIAGNOSTICS'),
    'REXP_FORM_EDITOR_DIAGNOSTICS_PROGRESS' => Loc::getMessage('REXP_FORM_EDITOR_DIAGNOSTICS_PROGRESS'),
    'REXP_FORM_EDITOR_EXPORT' => Loc::getMessage('REXP_FORM_EDITOR_EXPORT'),
    'REXP_FORM_EDITOR_EXPORT_PROGRESS' => Loc::getMessage('REXP_FORM_EDITOR_EXPORT_PROGRESS'),
    'REXP_FORM_EDITOR_SAVE' => Loc::getMessage('REXP_FORM_EDITOR_SAVE'),
    'REXP_FORM_EDITOR_SAVE_PROGRESS' => Loc::getMessage('REXP_FORM_EDITOR_SAVE_PROGRESS'),
    'REXP_FORM_EDITOR_TAB_DRAFTS' => Loc::getMessage('REXP_FORM_EDITOR_TAB_DRAFTS'),
    'REXP_FORM_EDITOR_TAB_BIZPROC' => Loc::getMessage('REXP_FORM_EDITOR_TAB_BIZPROC'),
    'REXP_FORM_EDITOR_TAB_DIAGNOSTICS' => Loc::getMessage('REXP_FORM_EDITOR_TAB_DIAGNOSTICS'),
    'REXP_FORM_EDITOR_TAB_EXCHANGE' => Loc::getMessage('REXP_FORM_EDITOR_TAB_EXCHANGE'),
    'REXP_FORM_EDITOR_PANEL_DRAFTS' => Loc::getMessage('REXP_FORM_EDITOR_PANEL_DRAFTS'),
    'REXP_FORM_EDITOR_EXCHANGE_EXPORT' => Loc::getMessage('REXP_FORM_EDITOR_EXCHANGE_EXPORT'),
    'REXP_FORM_EDITOR_EXCHANGE_IMPORT' => Loc::getMessage('REXP_FORM_EDITOR_EXCHANGE_IMPORT'),
    'REXP_FORM_EDITOR_IMPORT_HINT' => Loc::getMessage('REXP_FORM_EDITOR_IMPORT_HINT'),
    'REXP_FORM_EDITOR_IMPORT_ACTION' => Loc::getMessage('REXP_FORM_EDITOR_IMPORT_ACTION'),
    'REXP_FORM_EDITOR_IMPORT_PROGRESS' => Loc::getMessage('REXP_FORM_EDITOR_IMPORT_PROGRESS'),
    'REXP_FORM_EDITOR_DRAFT_SAVE_SUCCESS' => Loc::getMessage('REXP_FORM_EDITOR_DRAFT_SAVE_SUCCESS'),
    'REXP_FORM_EDITOR_DRAFT_SAVE_ERROR' => Loc::getMessage('REXP_FORM_EDITOR_DRAFT_SAVE_ERROR'),
    'REXP_FORM_EDITOR_DRAFT_RESTORE_SUCCESS' => Loc::getMessage('REXP_FORM_EDITOR_DRAFT_RESTORE_SUCCESS'),
    'REXP_FORM_EDITOR_DRAFT_DELETE_SUCCESS' => Loc::getMessage('REXP_FORM_EDITOR_DRAFT_DELETE_SUCCESS'),
    'REXP_FORM_EDITOR_SUBTITLE' => Loc::getMessage('REXP_FORM_EDITOR_SUBTITLE'),
    'REXP_FORM_EDITOR_CORE_LOAD_ERROR' => Loc::getMessage('REXP_FORM_EDITOR_CORE_LOAD_ERROR'),
];
?>
<div id="<?=$uid?>"></div>
<script>
(function(window) {
    var options = {
        containerId: '<?=CUtil::JSEscape($uid)?>',
        controller: '<?=CUtil::JSEscape($arResult['CONTROLLER'])?>',
        formId: <?= (int)$arResult['FORM_ID'] ?>,
        versionsUrl: '<?=CUtil::JSEscape((string)$arResult['VERSIONS_URL'])?>',
        draftsUrl: '<?=CUtil::JSEscape((string)$arResult['DRAFTS_URL'])?>',
        bizprocUrl: '<?=CUtil::JSEscape((string)$arResult['BIZPROC_URL'])?>',
        runtimeUrl: '<?=CUtil::JSEscape((string)$arResult['RUNTIME_URL'])?>',
        messages: <?=CUtil::PhpToJSObject($messages)?>,
        moduleOptions: <?=CUtil::PhpToJSObject((array)($arResult['MODULE_OPTIONS'] ?? []))?>
    };
    var attempts = 0;
    var boot = function() {
        attempts += 1;
        if (window.BX && BX.ready && BX.RexpForm && BX.RexpForm.createEditor) {
            BX.ready(function() {
                BX.RexpForm.createEditor(options);
            });
            return;
        }
        if (attempts < 120) {
            window.setTimeout(boot, 50);
            return;
        }
        var container = document.getElementById(options.containerId);
        if (container) {
            container.innerHTML = '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">' + BX.util.htmlspecialchars(options.messages.REXP_FORM_EDITOR_CORE_LOAD_ERROR || 'REXP_FORM_EDITOR_CORE_LOAD_ERROR') + '</span></div>';
        }
    };
    boot();
})(window);
</script>
