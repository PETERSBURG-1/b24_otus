<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Application\DTO\SaveFormCommand;
use Rexp\Form\Infrastructure\Event\FormEventDispatcher;
use Rexp\Form\Infrastructure\Persistence\FormRepository;
use Rexp\Form\Infrastructure\Persistence\FormVersionRepository;

/**
 * Сервис подготовки опубликованных форм.
 */
final class FormPublishService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormRepository $formRepository
     * @param FormVersionRepository $formVersionRepository
     * @param FormDraftService $formDraftService
     * @param FormEventDispatcher $eventDispatcher
     * @param FormPermissionService $permissionService
     * @param FormTargetResolverService $targetResolver
     * @param FormModuleOptionsService $moduleOptions
     */
    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormVersionRepository $formVersionRepository,
        private readonly FormDraftService $formDraftService,
        private readonly FormEventDispatcher $eventDispatcher,
        private readonly FormPermissionService $permissionService,
        private readonly FormTargetResolverService $targetResolver,
        private readonly FormModuleOptionsService $moduleOptions,
    ) {
    }

    /**
     * Сохраняет данные и возвращает результат операции.
     *
     * @param SaveFormCommand $command
     *
     * @return array
     */
    public function save(SaveFormCommand $command): array
    {
        $this->permissionService->assertCanManage();
        $command = $this->targetResolver->normalizeCommand($command);
        if ($command->active) {
            $this->permissionService->assertCanPublish();
        }

        $this->eventDispatcher->dispatch('onFormBeforeSave', ['command' => $command]);
        $form = $this->formRepository->save($command);
        if ($command->createVersion && $this->moduleOptions->isVersionsEnabled()) {
            $this->formVersionRepository->createSnapshot($form, $command->userId);
        }
        $this->formDraftService->clear($form->getId(), $command->userId);
        $this->eventDispatcher->dispatchForm('onFormAfterSave', $form);

        return $this->targetResolver->decorateFormArray($form->toArray());
    }

    /**
     * Перемещает форму в архив.
     *
     * @param int $id
     * @param int $userId
     *
     * @return void
     */
    public function archive(int $id, int $userId): void
    {
        $this->permissionService->assertCanManage();
        $this->formRepository->archive($id, $userId);
        $form = $this->formRepository->getById($id);
        if ($form) {
            $this->eventDispatcher->dispatchForm('onFormArchived', $form);
        }
    }

    /**
     * Восстанавливает форму из архива или версии.
     *
     * @param int $id
     * @param int $userId
     *
     * @return void
     */
    public function restore(int $id, int $userId): void
    {
        $this->permissionService->assertCanManage();
        $this->formRepository->restore($id, $userId);
        $form = $this->formRepository->getById($id);
        if ($form) {
            $this->eventDispatcher->dispatchForm('onFormRestored', $form);
        }
    }

    /**
     * Создаёт копию формы.
     *
     * @param int $id
     * @param int $userId
     *
     * @return ?array
     */
    public function duplicate(int $id, int $userId): ?array
    {
        $this->permissionService->assertCanManage();
        $form = $this->formRepository->duplicate($id, $userId);
        if (!$form) {
            return null;
        }

        $this->eventDispatcher->dispatchForm('onFormDuplicated', $form, ['sourceId' => $id]);
        return $this->targetResolver->decorateFormArray($form->toArray());
    }

    /**
     * Удаляет данные.
     *
     * @param int $id
     * @param int $userId
     *
     * @return void
     */
    public function delete(int $id, int $userId): void
    {
        $this->permissionService->assertCanDelete();
        $form = $this->formRepository->getById($id);
        $this->formRepository->delete($id);

        if ($form) {
            $this->eventDispatcher->dispatchForm('onFormDeleted', $form, ['userId' => $userId]);
        }
    }
}
