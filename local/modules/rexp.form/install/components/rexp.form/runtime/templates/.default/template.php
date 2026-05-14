<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

Extension::load(['main.core', 'main.core.events', 'ui.vue3', 'ui.alerts', 'ui.buttons', 'ui.buttons.icons', 'ui.notification', 'ui.entity-selector', 'rexp.form_runtime']);
$uid = 'rexp-form-runtime-' . substr(md5(uniqid('', true)), 0, 8);
?>
<div class="rexp-public-form-runtime-page">
    <div id="<?=$uid?>"></div>
    <script>
(function(window) {
    var selector = '#<?=$uid?>';
    var options = {
        formCode: <?=CUtil::PhpToJSObject($arResult['FORM_CODE'])?>,
        controller: <?=CUtil::PhpToJSObject($arResult['CONTROLLER'])?>,
        title: <?=CUtil::PhpToJSObject($arResult['TITLE'])?>,
        mode: <?=CUtil::PhpToJSObject($arResult['MODE'])?>,
        entryId: <?=CUtil::PhpToJSObject((int)$arResult['ENTRY_ID'])?>,
        messages: <?=CUtil::PhpToJSObject([
            'REXP_FORM_RUNTIME_CORE_LOAD_ERROR' => Loc::getMessage('REXP_FORM_RUNTIME_CORE_LOAD_ERROR'),
            'REXP_FORM_RUNTIME_EXTENSION_LOAD_ERROR' => Loc::getMessage('REXP_FORM_RUNTIME_EXTENSION_LOAD_ERROR'),
        ])?>
    };
    var renderError = function(message) {
        var container = document.querySelector(selector);
        if (container) {
            container.innerHTML = '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">' + message + '</span></div>';
        }
    };
    var boot = function() {
        if (!window.BX || !BX.ready) {
            renderError(options.messages.REXP_FORM_RUNTIME_CORE_LOAD_ERROR || 'REXP_FORM_RUNTIME_CORE_LOAD_ERROR');
            return;
        }
        BX.ready(function() {
            if (BX.Rexp && BX.Rexp.FormRuntime && typeof BX.Rexp.FormRuntime.mount === 'function') {
                BX.Rexp.FormRuntime.mount(selector, options);
                return;
            }
            renderError(options.messages.REXP_FORM_RUNTIME_EXTENSION_LOAD_ERROR || 'REXP_FORM_RUNTIME_EXTENSION_LOAD_ERROR');
        });
    };
    boot();
})(window);
</script>
</div>
