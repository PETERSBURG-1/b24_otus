<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('REXP_FORM_SETTINGS_TITLE'));
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-background no-all-paddings');

\Bitrix\Main\Loader::includeModule('ui');
Extension::load(['rexp.form.ui', 'ui.icons', 'ui.notification', 'ui.accessrights']);
Toolbar::deleteFavoriteStar();

$componentId = 'rexp-form-access-group';
$initPopupEvent = 'rexpForm:onAccessRightsLoad';
$openPopupEvent = 'rexpForm:onAccessRightsOpen';
?>

<span id="<?=htmlspecialcharsbx($componentId)?>"></span>

<?php
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.selector',
    '.default',
    [
        'API_VERSION' => 3,
        'ID' => $componentId,
        'BIND_ID' => $componentId,
        'ITEMS_SELECTED' => [],
        'CALLBACK' => [
            'select' => 'AccessRights.onMemberSelect',
            'unSelect' => 'AccessRights.onMemberUnselect',
            'openDialog' => 'function(){}',
            'closeDialog' => 'function(){}',
        ],
        'OPTIONS' => [
            'eventInit' => $initPopupEvent,
            'eventOpen' => $openPopupEvent,
            'useContainer' => 'Y',
            'lazyLoad' => 'Y',
            'context' => 'REXP_FORM_PERMISSION',
            'contextCode' => '',
            'useSearch' => 'Y',
            'useClientDatabase' => 'Y',
            'allowEmailInvitation' => 'N',
            'enableAll' => 'N',
            'enableUsers' => 'Y',
            'enableDepartments' => 'Y',
            'enableGroups' => 'Y',
            'enableUserGroups' => 'Y',
            'enableUserGroup' => 'Y',
            'departmentSelectDisable' => 'N',
            'allowAddUser' => 'N',
            'allowAddCrmContact' => 'N',
            'allowAddSocNetGroup' => 'N',
            'allowSearchEmailUsers' => 'N',
            'allowSearchCrmEmailUsers' => 'N',
            'allowSearchNetworkUsers' => 'N',
            'useNewCallback' => 'Y',
            'multiple' => 'Y',
            'enableSonetgroups' => 'Y',
            'showVacations' => 'Y',
        ],
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
?>

<div class="rexp-form-access-rights-page rexp-form-access-rights-page--slider">
    <div class="rexp-form-access-rights-page__hint">
        <?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_SETTINGS_HINT'))?>
    </div>
    <div id="rexp-form-config-permissions"></div>
</div>

<script>
(function(window) {
    var boot = function() {
        if (!window.BX || !BX.UI || !BX.UI.AccessRights) {
            window.setTimeout(boot, 50);
            return;
        }

        window.AccessRights = new BX.UI.AccessRights({
            component: 'rexp.form:designer.settings',
            actionSave: 'save',
            actionDelete: 'delete',
            actionLoad: 'load',
            renderTo: document.getElementById('rexp-form-config-permissions'),
            userGroups: <?=CUtil::PhpToJSObject($arResult['USER_GROUPS'])?>,
            accessRights: <?=CUtil::PhpToJSObject($arResult['ACCESS_RIGHTS'])?>,
            initPopupEvent: '<?=CUtil::JSEscape($initPopupEvent)?>',
            openPopupEvent: '<?=CUtil::JSEscape($openPopupEvent)?>',
            popupContainer: '<?=CUtil::JSEscape($componentId)?>'
        });

        AccessRights.draw();
        BX.ready(function() {
            window.setTimeout(function() {
                BX.onCustomEvent('<?=CUtil::JSEscape($initPopupEvent)?>', [{openDialogWhenInit: false}]);
            }, 100);
        });
    };

    boot();
})(window);
</script>

<?php
$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
    'HIDE' => true,
    'BUTTONS' => [
        [
            'TYPE' => 'save',
            'ONCLICK' => 'AccessRights.sendActionRequest()',
        ],
        [
            'TYPE' => 'cancel',
            'ONCLICK' => 'AccessRights.fireEventReset()',
        ],
    ],
]);
