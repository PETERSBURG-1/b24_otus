<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);
\Bitrix\Main\UI\Extension::load(['rexp.form.ui', 'sidepanel', 'ajax', 'popup']);

$permissions = (array)($arResult['PERMISSIONS'] ?? []);
$controller = (string)($arResult['CONTROLLER'] ?? 'rexp:form.Designer');
$settingsMenu = (array)($arResult['SETTINGS_MENU'] ?? []);
$messages = [
    'REXP_FORM_LIST_AJAX_UNAVAILABLE' => Loc::getMessage('REXP_FORM_LIST_AJAX_UNAVAILABLE'),
    'REXP_FORM_LIST_ACTION_SUCCESS' => Loc::getMessage('REXP_FORM_LIST_ACTION_SUCCESS'),
    'REXP_FORM_LIST_ACTION_ERROR' => Loc::getMessage('REXP_FORM_LIST_ACTION_ERROR'),
    'REXP_FORM_LIST_SELECT_ACTION' => Loc::getMessage('REXP_FORM_LIST_SELECT_ACTION'),
    'REXP_FORM_LIST_CONFIRM_DUPLICATE' => Loc::getMessage('REXP_FORM_LIST_CONFIRM_DUPLICATE'),
    'REXP_FORM_LIST_CONFIRM_ARCHIVE' => Loc::getMessage('REXP_FORM_LIST_CONFIRM_ARCHIVE'),
    'REXP_FORM_LIST_CONFIRM_RESTORE' => Loc::getMessage('REXP_FORM_LIST_CONFIRM_RESTORE'),
    'REXP_FORM_LIST_CONFIRM_DELETE' => Loc::getMessage('REXP_FORM_LIST_CONFIRM_DELETE'),
    'REXP_FORM_LIST_SELECT_FORMS' => Loc::getMessage('REXP_FORM_LIST_SELECT_FORMS'),
    'REXP_FORM_LIST_GROUP_ACTION_ERROR' => Loc::getMessage('REXP_FORM_LIST_GROUP_ACTION_ERROR'),
];
?>
<div class="rf-manager-wrap">
    <div class="rf-manager-toolbar">
        <div>
            <div class="rf-manager-toolbar__title"><?=htmlspecialcharsbx((string)$arResult['TITLE'])?></div>
            <div class="rf-manager-toolbar__hint"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_HINT'))?></div>
        </div>
        <div class="rf-manager-toolbar__actions">
            <?php if (!empty($permissions['canManage'])): ?>
                <button class="ui-btn ui-btn-primary" onclick="window.RexpFormGrid.openEditor('<?=CUtil::JSEscape((string)$arResult['EDITOR_URL'])?>'); return false;"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_NEW'))?></button>
            <?php endif; ?>
            <?php if (!empty($settingsMenu)): ?>
                <button class="ui-btn ui-btn-light-border ui-btn-dropdown" onclick="window.RexpFormGrid.openSettingsMenu(this); return false;"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_SETTINGS'))?></button>
            <?php endif; ?>
        </div>
    </div>

    <?php
    $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
        'FILTER_ID' => $arResult['FILTER_ID'],
        'GRID_ID' => $arResult['GRID_ID'],
        'FILTER' => $arResult['FILTER_FIELDS'],
        'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
        'ENABLE_LIVE_SEARCH' => true,
        'ENABLE_LABEL' => true,
        'RESET_TO_DEFAULT_MODE' => true,
    ]);

    $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
        'GRID_ID' => $arResult['GRID_ID'],
        'COLUMNS' => $arResult['COLUMNS'],
        'ROWS' => $arResult['ROWS'],
        'NAV_OBJECT' => $arResult['NAV_OBJECT'],
        'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
        'AJAX_MODE' => 'N',
        'AJAX_ID' => '',
        'PAGE_SIZES' => [
            ['NAME' => '10', 'VALUE' => '10'],
            ['NAME' => '20', 'VALUE' => '20'],
            ['NAME' => '50', 'VALUE' => '50'],
            ['NAME' => '100', 'VALUE' => '100'],
        ],
        'SHOW_ROW_CHECKBOXES' => true,
        'SHOW_GRID_SETTINGS_MENU' => true,
        'SHOW_NAVIGATION_PANEL' => true,
        'SHOW_PAGINATION' => true,
        'SHOW_SELECTED_COUNTER' => true,
        'SHOW_TOTAL_COUNTER' => true,
        'SHOW_PAGESIZE' => true,
        'SHOW_ACTION_PANEL' => false,
        'ALLOW_COLUMNS_SORT' => true,
        'ALLOW_COLUMNS_RESIZE' => true,
        'ALLOW_SORT' => true,
        'ALLOW_PIN_HEADER' => true,
        'ALLOW_HORIZONTAL_SCROLL' => true,
        'ALLOW_CONTEXT_MENU' => true,
        'ALLOW_INLINE_EDIT' => false,
        'ACTION_PANEL' => ['GROUPS' => []],
        'SORT' => $arResult['SORT'],
    ]);
    ?>

    <?php if (!empty($permissions['canManage']) || !empty($permissions['canDelete'])): ?>
        <div class="rf-bulk-actions">
            <span class="rf-bulk-actions__label"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_BULK_LABEL'))?></span>
            <select id="rexp_form_group_action" class="ui-ctl-element">
                <option value=""><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_BULK_SELECT'))?></option>
                <?php if (!empty($permissions['canManage'])): ?>
                    <option value="duplicate"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_BULK_DUPLICATE'))?></option>
                    <option value="archive"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_BULK_ARCHIVE'))?></option>
                    <option value="restore"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_BULK_RESTORE'))?></option>
                <?php endif; ?>
                <?php if (!empty($permissions['canDelete'])): ?>
                    <option value="delete"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_BULK_DELETE'))?></option>
                <?php endif; ?>
            </select>
            <button type="button" class="ui-btn ui-btn-primary" onclick="BX.RexpFormGrid.confirmGroupAction(); return false;"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_LIST_BULK_APPLY'))?></button>
        </div>
    <?php endif; ?>
</div>
<script>
var BXRef = window.BX || (window.top && window.top.BX) || null;
window.RexpFormGrid = {
    controller: '<?=CUtil::JSEscape($controller)?>',
    gridId: '<?=CUtil::JSEscape((string)$arResult['GRID_ID'])?>',
    settingsMenu: <?=Json::encode($settingsMenu)?>,
    currentGroupAction: '',
    messages: <?=Json::encode($messages)?>,
    msg: function(code) { return this.messages && this.messages[code] ? this.messages[code] : code; },
    openSidePanel: function(url) {
        if (BXRef && BXRef.SidePanel && BXRef.SidePanel.Instance) { BXRef.SidePanel.Instance.open(url, {cacheable: false, width: 1280}); return; }
        window.location.href = url;
    },
    openEditor: function(url) { this.openSidePanel(url); },
    openPublicForm: function(url) {
        if (!url) { return; }
        window.open(url, '_blank');
    },
    openPage: function(url) {
        if (!url) { return; }
        window.location.href = url;
    },
    openSettingsMenu: function(button) {
        if (!BXRef || !BXRef.PopupMenu) { window.location.href = this.settingsMenu[0] && this.settingsMenu[0].url ? this.settingsMenu[0].url : '#'; return; }
        var self = this;
        var items = (this.settingsMenu || []).map(function(item) {
            if (item.delimiter) {
                return {delimiter: true};
            }
            return {text: item.text, onclick: function() {
                BXRef.PopupMenu.getCurrentMenu().close();
                self.openSidePanel(item.url);
            }};
        });
        BXRef.PopupMenu.show('rexp-form-settings-menu', button, items, {autoHide: true, offsetLeft: 0, offsetTop: 0});
    },
    reloadGrid: function() {
        // Полная перезагрузка безопаснее AJAX-перезагрузки грида: при ajax reload Bitrix повторно выполняет
        // скрипты ui.toolbar/main.ui.filter и может падать с ошибкой duplicate toolbar id.
        window.location.reload();
    },
    notify: function(message, category) {
        if (BXRef && BXRef.UI && BXRef.UI.Notification && BXRef.UI.Notification.Center) {
            BXRef.UI.Notification.Center.notify({content: message, category: category || 'success'});
            return;
        }
        if (category === 'error') { alert(message); }
    },
    runAction: function(action, data, confirmText) {
        if (confirmText && !window.confirm(confirmText)) { return; }
        if (!BXRef || !BXRef.ajax || !BXRef.ajax.runAction) { this.notify(this.msg('REXP_FORM_LIST_AJAX_UNAVAILABLE'), 'error'); return; }
        var self = this;
        BXRef.ajax.runAction(this.controller + '.' + action, {data: data || {}}).then(function() {
            self.notify(self.msg('REXP_FORM_LIST_ACTION_SUCCESS'));
            self.reloadGrid();
        }, function(response) {
            var message = response && response.errors && response.errors[0] ? response.errors[0].message : self.msg('REXP_FORM_LIST_ACTION_ERROR');
            self.notify(message, 'error');
        });
    },
    setCurrentGroupAction: function(action) {
        this.currentGroupAction = action || '';
    },
    getCurrentGroupAction: function() {
        if (this.currentGroupAction) { return this.currentGroupAction; }
        var control = document.querySelector('[name="action_button_' + this.gridId + '"], #action_button_' + this.gridId + ', [name="rexp_form_group_action"], #rexp_form_group_action');
        if (control && control.value && control.value !== 'none') { return control.value; }
        return '';
    },
    getSelectedIds: function() {
        var grid = BXRef && BXRef.Main && BXRef.Main.gridManager ? BXRef.Main.gridManager.getInstanceById(this.gridId) : null;
        var ids = grid && grid.getRows && grid.getRows().getSelectedIds ? grid.getRows().getSelectedIds() : [];
        if (ids && ids.length) { return ids; }

        var fallback = [];
        document.querySelectorAll('input.main-grid-row-checkbox:checked, input[type="checkbox"][name="ID[]"]:checked').forEach(function(input) {
            var row = input.closest ? input.closest('tr[data-id], .main-grid-row[data-id]') : null;
            var id = row ? row.getAttribute('data-id') : input.value;
            if (id && String(id) !== 'on') { fallback.push(id); }
        });
        return fallback;
    },
    confirmGroupAction: function() {
        var action = this.getCurrentGroupAction();
        if (!action) { this.notify(this.msg('REXP_FORM_LIST_SELECT_ACTION'), 'error'); return; }
        var confirmText = '';
        if (action === 'duplicate') { confirmText = this.msg('REXP_FORM_LIST_CONFIRM_DUPLICATE'); }
        if (action === 'archive') { confirmText = this.msg('REXP_FORM_LIST_CONFIRM_ARCHIVE'); }
        if (action === 'restore') { confirmText = this.msg('REXP_FORM_LIST_CONFIRM_RESTORE'); }
        if (action === 'delete') { confirmText = this.msg('REXP_FORM_LIST_CONFIRM_DELETE'); }
        this.runGridBulkAction(action, confirmText);
    },
    runGridBulkAction: function(action, confirmText) {
        if (confirmText && !window.confirm(confirmText)) { return; }
        if (!BXRef || !BXRef.ajax || !BXRef.ajax.runAction) { this.notify(this.msg('REXP_FORM_LIST_AJAX_UNAVAILABLE'), 'error'); return; }
        var ids = this.getSelectedIds();
        if (!ids.length) { this.notify(this.msg('REXP_FORM_LIST_SELECT_FORMS'), 'error'); return; }
        var self = this;
        Promise.all(ids.map(function(id) {
            return BXRef.ajax.runAction(self.controller + '.' + action, {data: {id: Number(id)}});
        })).then(function() {
            self.notify(self.msg('REXP_FORM_LIST_ACTION_SUCCESS'));
            self.reloadGrid();
        }, function(response) {
            var message = response && response.errors && response.errors[0] ? response.errors[0].message : self.msg('REXP_FORM_LIST_GROUP_ACTION_ERROR');
            self.notify(message, 'error');
        });
    }
};
if (BXRef) {
    BXRef.RexpFormGrid = window.RexpFormGrid;
}
</script>
