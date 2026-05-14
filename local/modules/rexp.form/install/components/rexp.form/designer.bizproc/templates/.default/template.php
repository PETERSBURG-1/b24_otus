<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $APPLICATION;
\Bitrix\Main\UI\Extension::load(['rexp.form.ui']);

$form = (array)($arResult['FORM'] ?? []);
$formId = (int)($arResult['FORM_ID'] ?? 0);
$isEdit = !empty($arResult['IS_EDIT']);
$documentTypeCode = (string)($arResult['DOCUMENT_TYPE_CODE'] ?? '');
$documentClass = (string)($arResult['DOCUMENT_CLASS'] ?? '');
$listUrl = (string)($arResult['LIST_URL'] ?? '');
$editUrlTemplate = (string)($arResult['EDIT_URL_TEMPLATE'] ?? '');
$newTemplateUrl = (string)($arResult['NEW_TEMPLATE_URL'] ?? '');
$newStateMachineUrl = (string)($arResult['NEW_STATEMACHINE_URL'] ?? '');
$templateId = (int)($arResult['TEMPLATE_ID'] ?? 0);
$editorUrl = (string)($arResult['EDITOR_URL'] ?? '/forms/designer/editor.php');
$isEmbed = !empty($arResult['EMBED']);
$deleteResult = is_array($arResult['DELETE_RESULT'] ?? null) ? $arResult['DELETE_RESULT'] : ['success' => null, 'errors' => []];
?>
<div class="rf-bizproc-wrap" id="rexp-form-bizproc-wrap">
  <div class="rf-bizproc-card">
    <div class="rf-bizproc-title"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_BIZPROC_TITLE'))?></div>
    <?php if (!empty($deleteResult['errors'])): ?>
      <div class="rf-bizproc-empty rf-bizproc-empty--danger"><?=htmlspecialcharsbx(implode('; ', (array)$deleteResult['errors']))?></div>
    <?php endif; ?>
    <?php if ($formId <= 0): ?>
      <div class="rf-bizproc-empty"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_BIZPROC_SAVE_FORM_FIRST'))?></div>
    <?php else: ?>
      <div class="rf-bizproc-meta"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_BIZPROC_FORM_LABEL'))?>: <strong><?=htmlspecialcharsbx((string)($form['name'] ?? $form['NAME'] ?? ('#' . $formId)))?></strong> · <?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_BIZPROC_DOCUMENT_TYPE_LABEL'))?>: <code><?=htmlspecialcharsbx($documentTypeCode)?></code></div>
      <?php if (!$isEmbed): ?>
      <div class="rf-bizproc-toolbar rf-bizproc-toolbar--spaced">
        <a class="ui-btn ui-btn-primary" href="<?=htmlspecialcharsbx($newTemplateUrl)?>"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_BIZPROC_NEW_SEQUENTIAL'))?></a>
        <a class="ui-btn ui-btn-light-border" href="<?=htmlspecialcharsbx($newStateMachineUrl)?>"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_BIZPROC_NEW_STATE_MACHINE'))?></a>
        <?php if ($isEdit): ?>
          <a class="ui-btn ui-btn-light-border" href="<?=htmlspecialcharsbx($listUrl)?>"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_BIZPROC_BACK_TO_LIST'))?></a>
        <?php endif; ?>
        <a class="ui-btn ui-btn-light-border" href="<?=htmlspecialcharsbx($editorUrl . '?FORM_ID=' . $formId)?>"><?=htmlspecialcharsbx(Loc::getMessage('REXP_FORM_BIZPROC_BACK_TO_EDITOR'))?></a>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($formId > 0): ?>
    <div class="rf-bizproc-card">
      <?php if ($isEdit): ?>
        <?php
        $APPLICATION->IncludeComponent('bitrix:bizproc.workflow.edit', '', [
            'MODULE_ID' => \Rexp\Form\Bizproc\FormSubmissionDocument::BIZPROC_MODULE_ID,
            'ENTITY' => $documentClass,
            'DOCUMENT_TYPE' => $documentTypeCode,
            'ID' => $templateId,
            'EDIT_PAGE_TEMPLATE' => $editUrlTemplate,
            'LIST_PAGE_URL' => $listUrl,
            'SHOW_TOOLBAR' => 'N',
            'SET_TITLE' => 'N',
        ], $component, ['HIDE_ICONS' => 'Y']);
        ?>
      <?php else: ?>
        <?php
        $APPLICATION->IncludeComponent('bitrix:bizproc.workflow.list', '.default', [
            'MODULE_ID' => \Rexp\Form\Bizproc\FormSubmissionDocument::BIZPROC_MODULE_ID,
            'ENTITY' => $documentClass,
            'DOCUMENT_TYPE' => $documentTypeCode,
            'DOCUMENT_ID' => $documentTypeCode,
            'CREATE_DEFAULT_TEMPLATE' => 'N',
            'EDIT_URL' => $editUrlTemplate,
            'SET_TITLE' => 'N',
        ], $component, ['HIDE_ICONS' => 'Y']);
        ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>


<?php if (!$isEmbed): ?>
<script>
(function() {
  var root = document.getElementById('rexp-form-bizproc-wrap');
  if (!root) { return; }
  var markLinks = function() {
    var links = root.querySelectorAll('a[href]');
    links.forEach(function(link) {
      var href = String(link.getAttribute('href') || '');
      if (href.indexOf('/forms/designer/bizproc.php?FORM_ID=') !== -1) {
        link.setAttribute('data-slider-ignore-autobinding', 'true');
      }
    });
  };
  markLinks();
  root.addEventListener('click', function(event) {
    var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
    if (!link) { return; }
    var href = String(link.getAttribute('href') || '');
    if (href.indexOf('/forms/designer/bizproc.php?FORM_ID=') === -1) { return; }
    event.preventDefault();
    event.stopPropagation();
    window.location.assign(href);
  }, true);
})();
</script>
<?php endif; ?>
