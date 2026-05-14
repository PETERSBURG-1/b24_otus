<?php

namespace Rexp\Form\Controller;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Контроллер AJAX-операций редактора форм.
 */
final class Designer extends Controller
{
    /**
     * Описывает AJAX-действия контроллера.
     *
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'list' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'get' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'save' => ['prefilters' => [new Authentication()]],
            'archive' => ['prefilters' => [new Authentication()]],
            'restore' => ['prefilters' => [new Authentication()]],
            'duplicate' => ['prefilters' => [new Authentication()]],
            'delete' => ['prefilters' => [new Authentication()]],
            'diagnostics' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'versions' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'compareVersion' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'restoreVersion' => ['prefilters' => [new Authentication()]],
            'saveDraft' => ['prefilters' => [new Authentication()]],
            'getDraft' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'clearDraft' => ['prefilters' => [new Authentication()]],
            'deleteDraftEntry' => ['prefilters' => [new Authentication()]],
            'templates' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'createFromTemplate' => ['prefilters' => [new Authentication()]],
            'export' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'import' => ['prefilters' => [new Authentication()]],
            'probeSubmit' => ['prefilters' => [new Authentication()]],
            'runMockSubmitDiagnostics' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'getFieldTypes' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'getUserFields' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'getSettings' => ['prefilters' => [new Authentication(), new Csrf(false)]],
            'saveSettings' => ['prefilters' => [new Authentication()]],
            'uploadStateImage' => ['prefilters' => [new Authentication()]],
        ];
    }

    /**
     * Возвращает список форм.
     *
     * @param bool $withArchived
     *
     * @return array
     */
    public function listAction(bool $withArchived = false): array
    {
        return $this->forms()->list($withArchived, $this->userId());
    }

    /**
     * Возвращает данные формы.
     *
     * @param int $id
     *
     * @return array
     */
    public function getAction(int $id): array
    {
        return $this->forms()->get($id, $this->userId());
    }

    /**
     * Сохраняет переданные данные.
     *
     * @param array $form
     *
     * @return array
     */
    public function saveAction(array $form): array
    {
        return $this->forms()->save($form, $this->userId());
    }

    /**
     * Перемещает форму в архив.
     *
     * @param int $id
     *
     * @return array
     */
    public function archiveAction(int $id): array
    {
        return $this->forms()->archive($id, $this->userId());
    }

    /**
     * Восстанавливает форму из архива.
     *
     * @param int $id
     *
     * @return array
     */
    public function restoreAction(int $id): array
    {
        return $this->forms()->restore($id, $this->userId());
    }

    /**
     * Создаёт копию формы.
     *
     * @param int $id
     *
     * @return array
     */
    public function duplicateAction(int $id): array
    {
        return $this->forms()->duplicate($id, $this->userId());
    }

    /**
     * Удаляет указанный объект.
     *
     * @param int $id
     *
     * @return array
     */
    public function deleteAction(int $id): array
    {
        return $this->forms()->delete($id, $this->userId());
    }

    /**
     * Запускает диагностику формы.
     *
     * @param int $id
     *
     * @return array
     */
    public function diagnosticsAction(int $id): array
    {
        return $this->metadata()->diagnostics($id);
    }

    /**
     * Возвращает список версий формы.
     *
     * @param int $formId
     *
     * @return array
     */
    public function versionsAction(int $formId): array
    {
        return $this->history()->versions($formId);
    }

    /**
     * Возвращает данные для сравнения версии формы.
     *
     * @param int $formId
     * @param int $versionId
     *
     * @return array
     */
    public function compareVersionAction(int $formId, int $versionId): array
    {
        return $this->history()->compareVersion($formId, $versionId);
    }

    /**
     * Восстанавливает форму из версии.
     *
     * @param int $formId
     * @param int $versionId
     *
     * @return array
     */
    public function restoreVersionAction(int $formId, int $versionId): array
    {
        return $this->history()->restoreVersion($formId, $versionId, $this->userId());
    }

    /**
     * Сохраняет черновик формы.
     *
     * @param int $formId
     * @param array $form
     *
     * @return array
     */
    public function saveDraftAction(int $formId, array $form): array
    {
        return $this->history()->saveDraft($formId, $form, $this->userId());
    }

    /**
     * Возвращает черновик формы.
     *
     * @param int $formId
     *
     * @return array
     */
    public function getDraftAction(int $formId): array
    {
        return $this->history()->getDraft($formId, $this->userId());
    }

    /**
     * Удаляет черновик текущего пользователя.
     *
     * @param int $formId
     *
     * @return array
     */
    public function clearDraftAction(int $formId): array
    {
        return $this->history()->clearDraft($formId, $this->userId());
    }

    /**
     * Удаляет запись черновика.
     *
     * @param int $draftId
     *
     * @return array
     */
    public function deleteDraftEntryAction(int $draftId): array
    {
        return $this->history()->deleteDraftEntry($draftId, $this->userId());
    }

    /**
     * Возвращает список шаблонов форм.
     *
     * @return array
     */
    public function templatesAction(): array
    {
        return $this->forms()->templates();
    }

    /**
     * Создаёт форму из шаблона.
     *
     * @param string $templateCode
     *
     * @return array
     */
    public function createFromTemplateAction(string $templateCode): array
    {
        return $this->forms()->createFromTemplate($templateCode, $this->userId());
    }

    /**
     * Экспортирует форму.
     *
     * @param int $id
     *
     * @return array
     */
    public function exportAction(int $id): array
    {
        return $this->forms()->export($id, $this->userId());
    }

    /**
     * Импортирует форму из переданных данных.
     *
     * @param array $payload
     *
     * @return array
     */
    public function importAction(array $payload): array
    {
        return $this->forms()->import($payload, $this->userId());
    }

    /**
     * Выполняет пробную отправку формы.
     *
     * @param int $id
     * @param array $values
     *
     * @return array
     */
    public function probeSubmitAction(int $id, array $values): array
    {
        return $this->submit()->probeSubmit($id, $values, $this->userId());
    }

    /**
     * Запускает диагностику тестовой отправки формы.
     *
     * @param int $id
     *
     * @return array
     */
    public function runMockSubmitDiagnosticsAction(int $id): array
    {
        return $this->submit()->runMockSubmitDiagnostics($id, $this->userId());
    }

    /**
     * Возвращает типы полей для редактора.
     *
     * @param string $entityId
     *
     * @return array
     */
    public function getFieldTypesAction(string $entityId = ''): array
    {
        return $this->metadata()->getFieldTypes($entityId);
    }

    /**
     * Возвращает пользовательские поля выбранной сущности.
     *
     * @param string $entityId
     *
     * @return array
     */
    public function getUserFieldsAction(string $entityId): array
    {
        return $this->metadata()->getUserFields($entityId);
    }

    /**
     * Возвращает настройки редактора.
     *
     * @return array
     */
    public function getSettingsAction(): array
    {
        return $this->metadata()->getSettings();
    }

    /**
     * Сохраняет настройки редактора.
     *
     * @param array $settings
     *
     * @return array
     */
    public function saveSettingsAction(array $settings): array
    {
        return $this->metadata()->saveSettings($settings);
    }

    /**
     * Загружает изображение состояния формы.
     *
     * @return array
     */
    public function uploadStateImageAction(): array
    {
        $file = $this->getRequest()->getFile('file');
        if (!is_array($file)) {
            $this->addError(new \Bitrix\Main\Error(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_CONTROLLER_DESIGNER_001')));
            return [];
        }

        return $this->metadata()->uploadStateImage($file);
    }

    /**
     * Возвращает use case операций с формами.
     *
     * @return mixed
     */
    private function forms()
    {
        return ServiceLocator::getInstance()->get('rexp.form.usecase.designer.form');
    }

    /**
     * Возвращает use case метаданных редактора.
     *
     * @return mixed
     */
    private function metadata()
    {
        return ServiceLocator::getInstance()->get('rexp.form.usecase.designer.metadata');
    }

    /**
     * Возвращает use case истории формы.
     *
     * @return mixed
     */
    private function history()
    {
        return ServiceLocator::getInstance()->get('rexp.form.usecase.designer.history');
    }

    /**
     * Возвращает use case диагностической отправки.
     *
     * @return mixed
     */
    private function submit()
    {
        return ServiceLocator::getInstance()->get('rexp.form.usecase.designer.submit');
    }

    /**
     * Возвращает идентификатор текущего пользователя.
     *
     * @return int
     */
    private function userId(): int
    {
        return (int)CurrentUser::get()->getId();
    }
}
