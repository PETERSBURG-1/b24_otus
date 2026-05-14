<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$controller = (string)($arResult['CONTROLLER'] ?? 'rexp:form.Designer');
$form = (array)($arResult['FORM'] ?? []);
$isEmbed = !empty($arResult['EMBED']);
\Bitrix\Main\UI\Extension::load($isEmbed ? ['rexp.form.ui', 'ajax', 'ui.notification'] : ['rexp.form.ui', 'sidepanel', 'ajax', 'ui.notification']);
?>
<div class="rf-drafts-wrap<?= $isEmbed ? ' rf-embed-mode' : '' ?>">
    <?php if (!$isEmbed && !empty($arResult['FORM_ID'])): ?>
        <div class="rf-drafts-toolbar">
            <div class="rf-drafts-toolbar__actions">
                <button class="ui-btn ui-btn-light-border" onclick="window.RexpFormDraftsGrid.openSidePanel('<?=CUtil::JSEscape((string)$arResult['VERSIONS_URL'])?>'); return false;"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_DRAFTS_VERSIONS'))?></button>
                <button class="ui-btn ui-btn-light-border" onclick="window.location.href='<?=CUtil::JSEscape((string)$arResult['EDITOR_URL'])?>'; return false;"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_DRAFTS_OPEN_FORM'))?></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($arResult['FORM_ID']) && empty($form)): ?>
        <div class="rf-alert"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_DRAFTS_NOT_FOUND'))?></div>
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
window.RexpFormDraftsGrid = {
    controller: '<?=CUtil::JSEscape($controller)?>',
    isEmbed: <?= $isEmbed ? 'true' : 'false' ?>,
    messages: {
        deleteConfirm: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_DRAFTS_DELETE_CONFIRM'))?>,
        ajaxUnavailable: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_DRAFTS_AJAX_UNAVAILABLE'))?>,
        deleteError: <?=CUtil::PhpToJSObject(Loc::getMessage('REXP_FORM_DRAFTS_DELETE_ERROR'))?>
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
    deleteDraft: function(draftId) {
        if (!confirm(this.messages.deleteConfirm)) { return; }
        var self = this;
        if (!BXRef || !BXRef.ajax || !BXRef.ajax.runAction) { self.notify(this.messages.ajaxUnavailable); return; }
        BXRef.ajax.runAction(this.controller + '.deleteDraftEntry', {data: {draftId: draftId}}).then(function() { window.location.reload(); }, function(response) {
            var message = self.messages.deleteError;
            if (response && response.errors && response.errors[0] && response.errors[0].message) { message = response.errors[0].message; }
            self.notify(message);
        });
    }
};
</script>
