<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$controller = (string)($arResult['CONTROLLER'] ?? 'rexp:form.Designer');
$permissions = (array)($arResult['PERMISSIONS'] ?? []);
$form = (array)($arResult['FORM'] ?? []);
$isEmbed = !empty($arResult['EMBED']);
\Bitrix\Main\UI\Extension::load($isEmbed ? ['rexp.form.ui', 'ajax', 'ui.notification'] : ['rexp.form.ui', 'sidepanel', 'ajax', 'ui.notification']);
?>
<div class="rf-versions-wrap<?= $isEmbed ? ' rf-embed-mode' : '' ?>">
    <?php if (!$isEmbed && !empty($arResult['FORM_ID'])): ?>
        <div class="rf-versions-toolbar">
            <div class="rf-versions-toolbar__actions">
                <button class="ui-btn ui-btn-light-border" onclick="window.RexpFormVersionsGrid.openSidePanel('<?=CUtil::JSEscape((string)$arResult['DRAFTS_URL'])?>'); return false;"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_VERSIONS_DRAFTS'))?></button>
                <button class="ui-btn ui-btn-light-border" onclick="window.location.href='<?=CUtil::JSEscape((string)$arResult['EDITOR_URL'])?>'; return false;"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_VERSIONS_OPEN_FORM'))?></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($arResult['FORM_ID']) && empty($form)): ?>
        <div class="rf-alert"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_VERSIONS_NOT_FOUND'))?></div>
    <?php else: ?>
        <?php
        $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
            'FILTER_ID' => $arResult['FILTER_ID'], 'GRID_ID' => $arResult['GRID_ID'], 'FILTER' => $arResult['FILTER_FIELDS'], 'FILTER_PRESETS' => $arResult['FILTER_PRESETS'], 'ENABLE_LIVE_SEARCH' => true, 'ENABLE_LABEL' => true, 'RESET_TO_DEFAULT_MODE' => true,
        ]);
        $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
            'GRID_ID' => $arResult['GRID_ID'], 'COLUMNS' => $arResult['COLUMNS'], 'ROWS' => $arResult['ROWS'], 'NAV_OBJECT' => $arResult['NAV_OBJECT'], 'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'], 'AJAX_MODE' => 'N', 'AJAX_ID' => '', 'PAGE_SIZES' => [['NAME'=>'10','VALUE'=>'10'],['NAME'=>'20','VALUE'=>'20'],['NAME'=>'50','VALUE'=>'50'],['NAME'=>'100','VALUE'=>'100']], 'SHOW_ROW_CHECKBOXES' => false, 'SHOW_GRID_SETTINGS_MENU' => true, 'SHOW_NAVIGATION_PANEL' => true, 'SHOW_PAGINATION' => true, 'SHOW_SELECTED_COUNTER' => false, 'SHOW_TOTAL_COUNTER' => true, 'SHOW_PAGESIZE' => true, 'SHOW_ACTION_PANEL' => false, 'ALLOW_COLUMNS_SORT' => true, 'ALLOW_COLUMNS_RESIZE' => true, 'ALLOW_SORT' => true, 'ALLOW_PIN_HEADER' => true, 'ALLOW_HORIZONTAL_SCROLL' => true, 'ALLOW_CONTEXT_MENU' => true, 'ALLOW_INLINE_EDIT' => false, 'SORT' => $arResult['SORT'],
        ]);
        ?>
    <?php endif; ?>
</div>
<script>
var BXRef = window.BX || (window.top && window.top.BX) || null;
window.RexpFormVersionsGrid = {
    controller: '<?=CUtil::JSEscape($controller)?>',
    isEmbed: <?= $isEmbed ? 'true' : 'false' ?>,
    formId: <?= (int)($arResult['FORM_ID'] ?? 0) ?>,
    messages: {
        ajaxUnavailable: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_VERSIONS_AJAX_UNAVAILABLE'))?>,
        compareError: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_VERSIONS_COMPARE_ERROR'))?>,
        compareTitle: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_VERSIONS_COMPARE_TITLE'))?>,
        added: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_VERSIONS_ADDED'))?>,
        removed: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_VERSIONS_REMOVED'))?>,
        changed: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_VERSIONS_CHANGED'))?>,
        restoreConfirm: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_VERSIONS_RESTORE_CONFIRM'))?>,
        restoreError: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_VERSIONS_RESTORE_ERROR'))?>
    },
    openSidePanel: function(url) {
        if (this.isEmbed) { window.location.href = url; return; }
        if (BXRef && BXRef.SidePanel && BXRef.SidePanel.Instance) { BXRef.SidePanel.Instance.open(url, {cacheable: false, width: 1180}); return; }
        window.location.href = url;
    },
    notify: function(message) {
        if (BXRef && BXRef.UI && BXRef.UI.Notification && BXRef.UI.Notification.Center) { BXRef.UI.Notification.Center.notify({content: message, autoHideDelay: 5000}); return; }
        alert(message);
    },
    compareVersion: function(versionId, formId) {
        var self = this;
        formId = Number(formId || this.formId || 0);
        if (!formId) { this.notify(this.messages.compareError); return; }
        if (!BXRef || !BXRef.ajax || !BXRef.ajax.runAction) { self.notify(this.messages.ajaxUnavailable); return; }
        BXRef.ajax.runAction(this.controller + '.compareVersion', {data: {formId: formId, versionId: versionId}}).then(function(response) {
            var data = response && response.data ? response.data : {};
            if (!data.ok) { self.notify(data.message || self.messages.compareError); return; }
            var summary = data.summary || {};
            var message = self.messages.compareTitle + versionId + '<br>' + self.messages.added + ': ' + (summary.addedFields || 0) + '<br>' + self.messages.removed + ': ' + (summary.removedFields || 0) + '<br>' + self.messages.changed + ': ' + (summary.changedFields || 0);
            self.notify(message);
        }, function(response) {
            var message = self.messages.compareError;
            if (response && response.errors && response.errors[0] && response.errors[0].message) { message = response.errors[0].message; }
            self.notify(message);
        });
    },
    restoreVersion: function(versionId, formId) {
        if (!confirm(this.messages.restoreConfirm)) { return; }
        var self = this;
        formId = Number(formId || this.formId || 0);
        if (!formId) { this.notify(this.messages.restoreError); return; }
        if (!BXRef || !BXRef.ajax || !BXRef.ajax.runAction) { self.notify(this.messages.ajaxUnavailable); return; }
        BXRef.ajax.runAction(this.controller + '.restoreVersion', {data: {formId: formId, versionId: versionId}}).then(function() { window.location.reload(); }, function(response) {
            var message = self.messages.restoreError;
            if (response && response.errors && response.errors[0] && response.errors[0].message) { message = response.errors[0].message; }
            self.notify(message);
        });
    }
};
</script>
