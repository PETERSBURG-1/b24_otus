<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

use Bitrix\Main\UI\Extension;

Extension::load([
    'main.core',
    'main.popup',
    'sidepanel',
    'ui.buttons',
    'ui.forms',
    'ui.alerts',
    'ui.notification',
]);

global $APPLICATION;


/**
 * SidePanel открывает страницу в iframe (?IFRAME=Y).
 * На некоторых версиях ядра head-ассеты (скрипты/строки) в iframe не печатаются,
 * поэтому BX может быть не определён. Ниже — безопасный кросс-версийный “допечатчик”.
 */
CJSCore::Init(['ajax', 'popup', 'sidepanel', 'ui']);

if ((string)($_REQUEST['IFRAME'] ?? '') === 'Y')
{
    // Новые ядра
    if (class_exists('CJSCore') && method_exists('CJSCore', 'GetHeadStrings'))
    {
        echo CJSCore::GetHeadStrings();
    }
    else
    {
        // Старые ядра: методы ShowHead* печатают теги напрямую
        if (is_object($APPLICATION))
        {
            ob_start();
            $APPLICATION->ShowHeadStrings();
            $APPLICATION->ShowHeadScripts();
            $APPLICATION->ShowCSS();
            echo ob_get_clean();
        }
    }
}

// Стили подключаем напрямую (чтобы работало и в iframe slider)
?>
<link rel="stylesheet" href="<?=htmlspecialcharsbx($templateFolder)?>/style.css">
<?php


$page   = (string)($arResult['COMPONENT_PAGE'] ?? 'list');
$signed = (string)($arResult['SIGNED_PARAMS'] ?? '');

$folder    = rtrim((string)($arResult['FOLDER'] ?? $arParams['SEF_FOLDER']), '/') . '/';
$detailTpl = (string)($arResult['URL_TEMPLATES']['detail'] ?? '#ID#/');

$buildDetailUrl = static function (int $id) use ($folder, $detailTpl): string {
    return $folder . CComponentEngine::makePathFromTemplate($detailTpl, ['ID' => $id]);
};

$proceduresAll = (array)($arResult['PROCEDURES_ALL'] ?? []);
?>
<div class="doctors">

    <?php if (!empty($arResult['ERRORS'])): ?>
        <div class="ui-alert ui-alert-danger">
            <span class="ui-alert-message">
                <?php foreach ((array)$arResult['ERRORS'] as $e): ?>
                    <div><?=htmlspecialcharsbx($e)?></div>
                <?php endforeach; ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if ($page === 'detail'): ?>
        <?php
        $doctor = $arResult['DOCTOR'] ?? null;
        $doctorProcIds = (array)($arResult['DOCTOR_PROC_IDS'] ?? []);
        $doctorProcedures = (array)($arResult['DOCTOR_PROCEDURES'] ?? []);
        ?>

        <div class="head">
            <?php if ($doctor): ?>
                <button type="button" class="ui-btn ui-btn-primary ui-btn-sm" id="edit-doctor">
                    <?=htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTORS_EDIT_DOCTOR'))?>
                </button>
            <?php endif; ?>
        </div>

        <?php if (!$doctor): ?>
            <div class="ui-alert ui-alert-danger">
                <span class="ui-alert-message"><?=htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTORS_NOT_FOUND'))?></span>
            </div>
        <?php else: ?>
            <h1 class="title"><?=htmlspecialcharsbx((string)$doctor['NAME'])?></h1>

            <h3 class="subtitle"><?=htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTORS_PROCS_TITLE'))?></h3>

            <?php if (empty($doctorProcedures)): ?>
                <div class="ui-alert ui-alert-warning">
                    <span class="ui-alert-message"><?=htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTORS_EMPTY_PROCS'))?></span>
                </div>
            <?php else: ?>
                <div class="proc-grid">
                    <?php foreach ($doctorProcedures as $p): ?>
                        <span class="proc-chip"><?=htmlspecialcharsbx((string)$p['NAME'])?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <script>
        (function () {
            if (!window.BX) { return; }

            BX.ready(function () {
                var signed = '<?=CUtil::JSEscape($signed)?>';
                var doctorId = <?= (int)($doctor['ID'] ?? 0) ?>;
                var doctorName = '<?=CUtil::JSEscape((string)($doctor['NAME'] ?? ''))?>';
                var allProcs = <?=CUtil::PhpToJSObject($proceduresAll)?>;
                var selected = <?=CUtil::PhpToJSObject(array_values(array_map('intval', $doctorProcIds)))?>;

                function notify(text) {
                    if (BX && BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
                        BX.UI.Notification.Center.notify({content: text});
                    } else { alert(text); }
                }

                function buildOptions(all, selectedIds) {
                    var set = {};
                    for (var i = 0; i < selectedIds.length; i++) { set[selectedIds[i]] = true; }
                    var html = '';
                    for (var j = 0; j < all.length; j++) {
                        var id = parseInt(all[j].ID, 10) || 0;
                        var name = String(all[j].NAME || '');
                        html += '<option value="' + id + '"' + (set[id] ? ' selected' : '') + '>' + BX.util.htmlspecialchars(name) + '</option>';
                    }
                    return html;
                }

                function openEditDoctor() {
                    var content =
                        '<div class="popup">' +
                            '<div class="field">' +
                                '<div class="label"><?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_DOCTOR_NAME'))?></div>' +
                                '<div class="ui-ctl ui-ctl-textbox ui-ctl-w100"><input class="ui-ctl-element" id="otus_doc_name" type="text" value="' + BX.util.htmlspecialchars(doctorName) + '"></div>' +
                            '</div>' +
                            '<div class="field">' +
                                '<div class="label"><?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_SELECT_PROCS'))?></div>' +
                                '<div class="ui-ctl ui-ctl-multiple-select ui-ctl-w100">' +
                                    '<select class="ui-ctl-element" id="otus_doc_procs" multiple size="8">' +
                                        buildOptions(allProcs, selected) +
                                    '</select>' +
                                '</div>' +
                            '</div>' +
                        '</div>';

                    var popup = BX.PopupWindowManager.create('otus_edit_doctor', null, {
                        titleBar: {content: BX.create('span', {text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_EDIT_DOCTOR'))?>'})},
                        content: content,
                        closeIcon: true,
                        overlay: true,
                        autoHide: true,
                        buttons: [
                            new BX.PopupWindowButton({
                                text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_SAVE'))?>',
                                className: 'ui-btn ui-btn-success',
                                events: {click: function () {
                                    var name = BX.util.trim(BX('otus_doc_name').value || '');
                                    var sel = BX('otus_doc_procs');
                                    var ids = [];
                                    for (var k = 0; k < sel.options.length; k++) {
                                        if (sel.options[k].selected) { ids.push(parseInt(sel.options[k].value, 10)); }
                                    }

                                    BX.ajax.runComponentAction('otus:doctors', 'updateDoctor', {
                                        mode: 'class',
                                        signedParameters: signed,
                                        data: {id: doctorId, name: name, procedureIds: ids}
                                    }).then(function () { location.reload(); }, function (err) {
                                        notify((err && err.errors && err.errors[0] && err.errors[0].message) ? err.errors[0].message : '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_JS_ERROR'))?>');
                                    });
                                }}
                            }),
                            new BX.PopupWindowButton({
                                text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_CANCEL'))?>',
                                className: 'ui-btn ui-btn-light-border',
                                events: {click: function () { popup.close(); }}
                            })
                        ]
                    });

                    popup.show();
                }

                var btn = BX('edit-doctor');
                if (btn) { BX.bind(btn, 'click', openEditDoctor); }
            });
        })();
        </script>

    <?php else: ?>
        <?php $doctors = (array)($arResult['DOCTORS'] ?? []); ?>

        <div class="head">
            <h1 class="title title--no-m"><?=htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTORS_LIST_TITLE'))?></h1>

            <div class="actions">
                <button type="button" class="ui-btn ui-btn-primary ui-btn-sm" id="add-doctor">
                    <?=htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTORS_ADD_DOCTOR'))?>
                </button>
                <button type="button" class="ui-btn ui-btn-light-border ui-btn-sm" id="add-proc">
                    <?=htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTORS_ADD_PROC'))?>
                </button>
            </div>
        </div>

        <div class="doctors-grid" id="doctors-grid">
            <?php foreach ($doctors as $d): ?>
                <?php $url = $buildDetailUrl((int)$d['ID']); ?>
                <a class="doctor-card js-open" href="<?=htmlspecialcharsbx($url)?>" data-url="<?=htmlspecialcharsbx($url)?>">
                    <div class="doctor-name"><?=htmlspecialcharsbx((string)$d['NAME'])?></div>
                    <div class="doctor-hint"><?=htmlspecialcharsbx(Loc::getMessage('OTUS_DOCTORS_OPEN_HINT'))?></div>
                </a>
            <?php endforeach; ?>
        </div>

        <script>
        (function () {
            if (!window.BX) { return; }

            BX.ready(function () {
                var signed = '<?=CUtil::JSEscape($signed)?>';
                var allProcs = <?=CUtil::PhpToJSObject($proceduresAll)?>;

                function notify(text) {
                    if (BX && BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
                        BX.UI.Notification.Center.notify({content: text});
                    } else { alert(text); }
                }

                function buildOptions(all) {
                    var html = '';
                    for (var j = 0; j < all.length; j++) {
                        var id = parseInt(all[j].ID, 10) || 0;
                        var name = String(all[j].NAME || '');
                        html += '<option value="' + id + '">' + BX.util.htmlspecialchars(name) + '</option>';
                    }
                    return html;
                }

                function openAddDoctor() {
                    var content =
                        '<div class="popup">' +
                            '<div class="field">' +
                                '<div class="label"><?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_DOCTOR_NAME'))?></div>' +
                                '<div class="ui-ctl ui-ctl-textbox ui-ctl-w100"><input class="ui-ctl-element" id="otus_new_doc_name" type="text"></div>' +
                            '</div>' +
                            '<div class="field">' +
                                '<div class="label"><?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_SELECT_PROCS'))?></div>' +
                                '<div class="ui-ctl ui-ctl-multiple-select ui-ctl-w100">' +
                                    '<select class="ui-ctl-element" id="otus_new_doc_procs" multiple size="8">' +
                                        buildOptions(allProcs) +
                                    '</select>' +
                                '</div>' +
                            '</div>' +
                        '</div>';

                    var popup = BX.PopupWindowManager.create('otus_add_doctor', null, {
                        titleBar: {content: BX.create('span', {text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_ADD_DOCTOR'))?>'})},
                        content: content,
                        closeIcon: true,
                        overlay: true,
                        autoHide: true,
                        buttons: [
                            new BX.PopupWindowButton({
                                text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_SAVE'))?>',
                                className: 'ui-btn ui-btn-success',
                                events: {click: function () {
                                    var name = BX.util.trim(BX('otus_new_doc_name').value || '');
                                    var sel = BX('otus_new_doc_procs');
                                    var ids = [];
                                    for (var k = 0; k < sel.options.length; k++) {
                                        if (sel.options[k].selected) { ids.push(parseInt(sel.options[k].value, 10)); }
                                    }

                                    BX.ajax.runComponentAction('otus:doctors', 'addDoctor', {
                                        mode: 'class',
                                        signedParameters: signed,
                                        data: {name: name, procedureIds: ids}
                                    }).then(function (res) {
                                        if (res && res.data && res.data.url) { location.href = res.data.url; return; }
                                        location.reload();
                                    }, function (err) {
                                        notify((err && err.errors && err.errors[0] && err.errors[0].message) ? err.errors[0].message : '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_JS_ERROR'))?>');
                                    });
                                }}
                            }),
                            new BX.PopupWindowButton({
                                text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_CANCEL'))?>',
                                className: 'ui-btn ui-btn-light-border',
                                events: {click: function () { popup.close(); }}
                            })
                        ]
                    });

                    popup.show();
                }

                function openAddProcedure() {
                    var content =
                        '<div class="popup">' +
                            '<div class="field">' +
                                '<div class="label"><?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_PROC_NAME'))?></div>' +
                                '<div class="ui-ctl ui-ctl-textbox ui-ctl-w100"><input class="ui-ctl-element" id="otus_new_proc_name" type="text"></div>' +
                            '</div>' +
                        '</div>';

                    var popup = BX.PopupWindowManager.create('otus_add_proc', null, {
                        titleBar: {content: BX.create('span', {text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_ADD_PROC'))?>'})},
                        content: content,
                        closeIcon: true,
                        overlay: true,
                        autoHide: true,
                        buttons: [
                            new BX.PopupWindowButton({
                                text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_SAVE'))?>',
                                className: 'ui-btn ui-btn-success',
                                events: {click: function () {
                                    var name = BX.util.trim(BX('otus_new_proc_name').value || '');

                                    BX.ajax.runComponentAction('otus:doctors', 'addProcedure', {
                                        mode: 'class',
                                        signedParameters: signed,
                                        data: {name: name}
                                    }).then(function () { location.reload(); }, function (err) {
                                        notify((err && err.errors && err.errors[0] && err.errors[0].message) ? err.errors[0].message : '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_JS_ERROR'))?>');
                                    });
                                }}
                            }),
                            new BX.PopupWindowButton({
                                text: '<?=CUtil::JSEscape(Loc::getMessage('OTUS_DOCTORS_CANCEL'))?>',
                                className: 'ui-btn ui-btn-light-border',
                                events: {click: function () { popup.close(); }}
                            })
                        ]
                    });

                    popup.show();
                }

                function initSliderLinks() {
                    var wrap = BX('doctors-grid');
                    if (!wrap) { return; }

                    var nodes = BX.findChildren(wrap, {className: 'js-open'}, true);
                    if (!nodes || !nodes.length) { return; }

                    for (var i = 0; i < nodes.length; i++) {
                        BX.bind(nodes[i], 'click', function (e) {
                            var url = this.getAttribute('data-url') || this.href;
                            if (BX.SidePanel && BX.SidePanel.Instance && url) {
                                e.preventDefault();
                                BX.SidePanel.Instance.open(url, {cacheable: false, width: 860});
                            }
                        });
                    }
                }

                var b1 = BX('add-doctor');
                var b2 = BX('add-proc');
                if (b1) { BX.bind(b1, 'click', openAddDoctor); }
                if (b2) { BX.bind(b2, 'click', openAddProcedure); }

                initSliderLinks();
            });
        })();
        </script>

    <?php endif; ?>
</div>
