<?php


use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Rexp\Form\Bizproc\FormSubmissionDocument;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Компонент управления шаблонами бизнес-процессов формы.
 */
class RexpFormDesignerBizprocComponent extends CBitrixComponent
{
    /**
     * Нормализует входные параметры компонента.
     *
     * @param mixed $arParams
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['FORM_ID'] = (int)($arParams['FORM_ID'] ?? 0);
        $arParams['CONTROLLER'] = (string)($arParams['CONTROLLER'] ?? 'rexp:form.Designer');
        $arParams['EDITOR_URL'] = (string)($arParams['EDITOR_URL'] ?? '/forms/designer/editor.php');
        $arParams['EMBED'] = (string)($arParams['EMBED'] ?? ($_REQUEST['EMBED'] ?? 'N'));
        return $arParams;
    }

    /**
     * Подготавливает данные компонента и подключает шаблон.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule('rexp.form')) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_BIZPROC_CLASS_001'));
            return;
        }

        if (!Loader::includeModule('bizproc') || !Loader::includeModule('bizprocdesigner')) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_BIZPROC_CLASS_002'));
            return;
        }

        $permissions = $this->permissionService()->getPermissions();
        if (empty($permissions['canManageBizproc'])) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_BIZPROC_CLASS_003'));
            return;
        }

        $formId = (int)($_REQUEST['FORM_ID'] ?? $_REQUEST['id'] ?? $this->arParams['FORM_ID']);
        $form = $formId > 0 ? $this->readService()->get($formId, $this->getCurrentUserId()) : null;
        $embed = (string)$this->arParams['EMBED'] === 'Y';
        $baseUrl = '/forms/designer/bizproc.php?FORM_ID=' . $formId . ($embed ? '&EMBED=Y' : '');
        $templateId = (int)($_REQUEST['ID'] ?? $_REQUEST['id_template'] ?? $_REQUEST['template_id'] ?? 0);
        $deleteResult = $this->tryDeleteWorkflowTemplate($formId, $baseUrl);
        $isEdit = !$this->isDeleteRequest() && array_key_exists('ID', $_REQUEST);

        $this->arResult = [
            'FORM_ID' => $formId,
            'FORM' => $form,
            'IS_EDIT' => $isEdit,
            'TEMPLATE_ID' => $templateId,
            'DOCUMENT_TYPE_CODE' => $formId > 0 ? FormSubmissionDocument::getDocumentTypeCode($formId) : '',
            'DOCUMENT_CLASS' => FormSubmissionDocument::class,
            'LIST_URL' => $baseUrl,
            'EDIT_URL_TEMPLATE' => $baseUrl . '&ID=#ID#',
            'NEW_TEMPLATE_URL' => $baseUrl . '&ID=0',
            'NEW_STATEMACHINE_URL' => $baseUrl . '&ID=0&init=statemachine',
            'EDITOR_URL' => (string)$this->arParams['EDITOR_URL'],
            'EMBED' => $embed,
            'DELETE_RESULT' => $deleteResult,
        ];

        $this->includeComponentTemplate();
    }


    /**
     * Пытается удалить шаблон бизнес-процесса формы.
     *
     * @param int $formId
     * @param string $baseUrl
     *
     * @return array
     */
    private function tryDeleteWorkflowTemplate(int $formId, string $baseUrl): array
    {
        if ($formId <= 0 || !$this->isDeleteRequest()) {
            return ['success' => null, 'errors' => []];
        }

        $templateId = $this->getDeleteTemplateId();
        if ($templateId <= 0) {
            return ['success' => false, 'errors' => [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_BIZPROC_CLASS_004')]];
        }

        if (function_exists('check_bitrix_sessid') && !check_bitrix_sessid()) {
            return ['success' => false, 'errors' => [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_BIZPROC_CLASS_005')]];
        }

        $errors = [];
        \CBPDocument::DeleteWorkflowTemplate(
            $templateId,
            FormSubmissionDocument::getComplexDocumentType($formId),
            $errors
        );

        if (is_array($errors) && $errors !== []) {
            return [
                'success' => false,
                'errors' => array_map(
                    static fn($error): string => is_array($error) ? (string)($error['message'] ?? $error['code'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_BIZPROC_CLASS_006')) : (string)$error,
                    $errors
                ),
            ];
        }

        if (!headers_sent()) {
            LocalRedirect($baseUrl);
        }

        return ['success' => true, 'errors' => []];
    }

    /**
     * Проверяет, является ли текущий запрос удалением шаблона БП.
     *
     * @return bool
     */
    private function isDeleteRequest(): bool
    {
        $action = mb_strtolower(trim((string)($_REQUEST['action'] ?? $_REQUEST['ACTION'] ?? $_REQUEST['grid_action'] ?? '')));

        return $action === 'delete'
            || $action === 'delete_template'
            || $action === 'delete_workflow_template'
            || isset($_REQUEST['delete'])
            || isset($_REQUEST['delete_template'])
            || isset($_REQUEST['delete_template_id'])
            || isset($_REQUEST['delete_id']);
    }

    /**
     * Возвращает идентификатор удаляемого шаблона БП.
     *
     * @return int
     */
    private function getDeleteTemplateId(): int
    {
        foreach (['delete_template_id', 'delete_id', 'id_template', 'template_id', 'ID', 'id'] as $key) {
            $id = (int)($_REQUEST[$key] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    /**
     * Возвращает сервис чтения форм.
     *
     * @return mixed
     */
    private function readService()
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.read');
    }

    /**
     * Возвращает сервис проверки прав.
     *
     * @return mixed
     */
    private function permissionService()
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.permission');
    }

    /**
     * Возвращает идентификатор текущего пользователя.
     *
     * @return int
     */
    private function getCurrentUserId(): int
    {
        return (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();
    }
}
