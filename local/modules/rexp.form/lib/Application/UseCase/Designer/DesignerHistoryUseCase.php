<?php

namespace Rexp\Form\Application\UseCase\Designer;

use Rexp\Form\Application\Service\FormDraftService;
use Rexp\Form\Application\Service\FormReadService;
use Rexp\Form\Application\Service\FormVersionService;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Use case истории версий и черновиков формы.
 */
final class DesignerHistoryUseCase
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormReadService $readService
     * @param FormVersionService $versionService
     * @param FormDraftService $draftService
     */
    public function __construct(
        private readonly FormReadService $readService,
        private readonly FormVersionService $versionService,
        private readonly FormDraftService $draftService,
    ) {
    }

    /**
     * Возвращает список версий формы.
     *
     * @param int $formId
     *
     * @return array
     */
    public function versions(int $formId): array
    {
        return [
            'items' => $this->versionService->list($formId),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Сравнивает форму с выбранной версией.
     *
     * @param int $formId
     * @param int $versionId
     *
     * @return array
     */
    public function compareVersion(int $formId, int $versionId): array
    {
        return $this->versionService->compare($formId, $versionId) + [
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Восстанавливает форму из выбранной версии.
     *
     * @param int $formId
     * @param int $versionId
     * @param int $userId
     *
     * @return array
     */
    public function restoreVersion(int $formId, int $versionId, int $userId): array
    {
        return [
            'item' => $this->versionService->restore($formId, $versionId, $userId),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Сохраняет черновика.
     *
     * @param int $formId
     * @param array $form
     * @param int $userId
     *
     * @return array
     */
    public function saveDraft(int $formId, array $form, int $userId): array
    {
        return [
            'draft' => $this->draftService->save($formId, $form, $userId),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Возвращает черновика.
     *
     * @param int $formId
     * @param int $userId
     *
     * @return array
     */
    public function getDraft(int $formId, int $userId): array
    {
        return [
            'draft' => $this->draftService->get($formId, $userId),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Очищает черновика.
     *
     * @param int $formId
     * @param int $userId
     *
     * @return array
     */
    public function clearDraft(int $formId, int $userId): array
    {
        $this->draftService->clear($formId, $userId);

        return [
            'success' => true,
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Удаляет черновика entry.
     *
     * @param int $draftId
     * @param int $userId
     *
     * @return array
     */
    public function deleteDraftEntry(int $draftId, int $userId): array
    {
        $draft = $this->draftService->getById($draftId);
        if (!$draft) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_USECASE_DESIGNER_DESIGNERHISTORYUSECASE_001'));
        }

        $permissions = $this->readService->getPermissions();
        $authorId = (int)($draft['authorId'] ?? 0);
        if (empty($permissions['canManage']) && $authorId !== $userId) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_USECASE_DESIGNER_DESIGNERHISTORYUSECASE_002'));
        }

        $this->draftService->deleteById($draftId);

        return [
            'success' => true,
            'permissions' => $permissions,
        ];
    }
}
