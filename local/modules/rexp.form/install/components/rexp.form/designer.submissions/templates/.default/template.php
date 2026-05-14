<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
\Bitrix\Main\UI\Extension::load(['rexp.form.ui']);
?>
<div class="rf-manager-wrap">
    <?php if ((int)($arResult['FORM_ID'] ?? 0) > 0): ?>
        <div class="rf-manager-actions">
            <a class="ui-btn ui-btn-light-border" href="<?=htmlspecialcharsbx((string)$arResult['EDITOR_URL'])?>"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_SUBMISSIONS_OPEN_FORM'))?></a>
        </div>
    <?php endif; ?>
    <?php
    $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
        'FILTER_ID' => $arResult['FILTER_ID'], 'GRID_ID' => $arResult['GRID_ID'], 'FILTER' => $arResult['FILTER_FIELDS'], 'FILTER_PRESETS' => $arResult['FILTER_PRESETS'], 'ENABLE_LIVE_SEARCH' => true, 'ENABLE_LABEL' => true, 'RESET_TO_DEFAULT_MODE' => true,
    ]);
    $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
        'GRID_ID' => $arResult['GRID_ID'], 'COLUMNS' => $arResult['COLUMNS'], 'ROWS' => $arResult['ROWS'], 'NAV_OBJECT' => $arResult['NAV_OBJECT'], 'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'], 'AJAX_MODE' => 'N', 'SHOW_ROW_CHECKBOXES' => false, 'SHOW_GRID_SETTINGS_MENU' => true, 'SHOW_NAVIGATION_PANEL' => true, 'SHOW_PAGINATION' => true, 'SHOW_SELECTED_COUNTER' => false, 'SHOW_TOTAL_COUNTER' => true, 'SHOW_PAGESIZE' => true, 'SHOW_ACTION_PANEL' => false, 'ALLOW_COLUMNS_SORT' => true, 'ALLOW_COLUMNS_RESIZE' => true, 'ALLOW_SORT' => true, 'ALLOW_PIN_HEADER' => true, 'ALLOW_HORIZONTAL_SCROLL' => true, 'ALLOW_CONTEXT_MENU' => true, 'ALLOW_INLINE_EDIT' => false, 'SORT' => $arResult['SORT'], 'PAGE_SIZES' => [['NAME'=>'10','VALUE'=>'10'],['NAME'=>'20','VALUE'=>'20'],['NAME'=>'50','VALUE'=>'50'],['NAME'=>'100','VALUE'=>'100']],
    ]);
    ?>
</div>
