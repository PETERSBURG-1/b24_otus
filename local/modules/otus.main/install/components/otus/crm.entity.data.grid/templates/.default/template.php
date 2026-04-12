<?php

use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arResult
 * @var array $arParams
 * @global CMain $APPLICATION
 * @global CBitrixComponent $component
 */

global $APPLICATION;

$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    [
        'GRID_ID' => $arResult['GRID_ID'],
        'HEADERS' => $arResult['COLUMNS'],
        'ROWS' => $arResult['ROWS'],
        'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
        'NAV_OBJECT' => $arResult['NAV_OBJECT'],
        'AJAX_MODE' => 'Y',
        'AJAX_LOADER' => $arParams['AJAX_LOADER'] ?? null,
        'ALLOW_COLUMNS_SORT' => true,
        'ALLOW_COLUMNS_RESIZE' => true,
        'ALLOW_HORIZONTAL_SCROLL' => true,
        'ALLOW_SORT' => true,
        'ALLOW_PIN_HEADER' => true,
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_ROW_CHECKBOXES' => false,
        'SHOW_ROW_ACTIONS_MENU' => false,
        'SHOW_GRID_SETTINGS_MENU' => true,
        'SHOW_NAVIGATION_PANEL' => true,
        'SHOW_PAGINATION' => true,
        'SHOW_SELECTED_COUNTER' => false,
        'SHOW_TOTAL_COUNTER' => true,
        'SHOW_PAGESIZE' => true,
        'SHOW_ACTION_PANEL' => false,
        'SHOW_MORE_BUTTON' => false,
        'PAGE_SIZES' => $arResult['PAGE_SIZES'],
    ],
    $component,
    ['HIDE_ICONS' => 'Y']
);

if (!empty($arParams['AJAX_LOADER'])) {
    ?>
    <script>
        BX.addCustomEvent('Grid::beforeRequest', function (gridData, args) {
            if (args.gridId !== '<?= CUtil::JSEscape($arResult['GRID_ID']); ?>') {
                return;
            }

            if (args.url === '') {
                args.url = '<?= CUtil::JSEscape($component->getPath()); ?>/lazyload.ajax.php?site=<?= CUtil::JSEscape(SITE_ID); ?>&internal=true&grid_id=<?= CUtil::JSEscape($arResult['GRID_ID']); ?>&grid_action=filter&';
            }

            args.method = 'POST';
            args.data = <?= Json::encode($arParams['AJAX_LOADER']['data']) ?>;
        });
    </script>
    <?php
}
