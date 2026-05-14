<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Infrastructure\Event\FormEventDispatcher;
use Rexp\Form\Infrastructure\Persistence\FormDraftRepository;

/**
 * Сервис работы с черновиками форм.
 */
final class FormDraftService
{
    private RuntimeDirectoryService $directoryService;

    /** @var array<int,array<string,mixed>>|null */
    private ?array $cachedEmployeeItems = null;

    /** @var array<int,array<string,mixed>>|null */
    private ?array $cachedDepartmentItems = null;

    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormDraftRepository $draftRepository
     * @param FormEventDispatcher $eventDispatcher
     * @param FormModuleOptionsService $moduleOptions
     * @param ?RuntimeDirectoryService $directoryService
     */
    public function __construct(
        private readonly FormDraftRepository $draftRepository,
        private readonly FormEventDispatcher $eventDispatcher,
        private readonly FormModuleOptionsService $moduleOptions,
        ?RuntimeDirectoryService $directoryService = null,
    ) {
        $this->directoryService = $directoryService ?? new RuntimeDirectoryService();
    }

    /**
     * Возвращает данные по переданным параметрам.
     *
     * @param int $formId
     * @param int $userId
     *
     * @return ?array
     */
    public function get(int $formId, int $userId): ?array
    {
        if ($formId <= 0 || $userId <= 0) {
            return null;
        }

        $draft = $this->draftRepository->get($formId, $userId);
        return $draft ? $this->enrichSelectorFields($draft) : null;
    }

    /**
     * Возвращает карту метаданных по идентификаторам форм.
     *
     * @param array $formIds
     * @param int $userId
     *
     * @return array
     */
    public function getMetaMap(array $formIds, int $userId): array
    {
        if ($userId <= 0 || empty($formIds)) {
            return [];
        }

        return $this->draftRepository->getMetaMap($formIds, $userId);
    }


    /**
     * Возвращает запись по идентификатору.
     *
     * @param int $draftId
     *
     * @return ?array
     */
    public function getById(int $draftId): ?array
    {
        if ($draftId <= 0) {
            return null;
        }

        $draft = $this->draftRepository->getById($draftId);
        return $draft ? $this->enrichSelectorFields($draft) : null;
    }

    /**
     * Возвращает список записей.
     *
     * @param int $formId
     *
     * @return array
     */
    public function list(int $formId): array
    {
        if ($formId <= 0) {
            return [];
        }

        return array_map(fn(array $draft): array => $this->enrichSelectorFields($draft), $this->draftRepository->listByFormId($formId));
    }


    /**
     * Возвращает полный список записей.
     *
     * @return array
     */
    public function listAll(): array
    {
        return array_map(fn(array $draft): array => $this->enrichSelectorFields($draft), $this->draftRepository->listAll());
    }

    /**
     * Удаляет запись по идентификатору.
     *
     * @param int $draftId
     *
     * @return void
     */
    public function deleteById(int $draftId): void
    {
        if ($draftId <= 0) {
            return;
        }

        $draft = $this->draftRepository->getById($draftId);
        $this->draftRepository->deleteById($draftId);

        if ($draft) {
            $this->eventDispatcher->dispatch('onDraftCleared', [
                'formId' => (int)($draft['formId'] ?? 0),
                'userId' => (int)($draft['authorId'] ?? 0),
                'draftId' => $draftId,
            ]);
        }
    }

    /**
     * Сохраняет данные и возвращает результат операции.
     *
     * @param int $formId
     * @param array $form
     * @param int $userId
     *
     * @return ?array
     */
    public function save(int $formId, array $form, int $userId): ?array
    {
        if ($formId <= 0 || $userId <= 0 || !$this->moduleOptions->isDraftsEnabled()) {
            return null;
        }

        $draft = $this->draftRepository->save($formId, $userId, $form);
        $this->eventDispatcher->dispatch('onDraftSaved', [
            'formId' => $formId,
            'userId' => $userId,
            'draft' => $draft,
        ]);

        return $draft;
    }

    /**
     * Очищает данные по переданным параметрам.
     *
     * @param int $formId
     * @param int $userId
     *
     * @return void
     */
    public function clear(int $formId, int $userId): void
    {
        if ($formId <= 0 || $userId <= 0) {
            return;
        }

        $this->draftRepository->clear($formId, $userId);
        $this->eventDispatcher->dispatch('onDraftCleared', [
            'formId' => $formId,
            'userId' => $userId,
        ]);
    }

    /**
     * Дополняет поля селекторов актуальными справочными значениями.
     *
     * @param array $draft
     *
     * @return array
     */
    private function enrichSelectorFields(array $draft): array
    {
        $form = is_array($draft['form'] ?? null) ? $draft['form'] : [];
        if ($form === []) {
            return $draft;
        }

        if (is_array($form['schema'] ?? null)) {
            $schema = $form['schema'];
            $schema['fields'] = $this->enrichFields(is_array($schema['fields'] ?? null) ? $schema['fields'] : []);
            $form['schema'] = $schema;
        } else {
            $form['fields'] = $this->enrichFields(is_array($form['fields'] ?? null) ? $form['fields'] : []);
        }

        $draft['form'] = $form;
        return $draft;
    }

    /**
     * Дополняет поля формы справочными значениями.
     *
     * @param array $fields
     *
     * @return array
     */
    private function enrichFields(array $fields): array
    {
        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            $type = trim((string)($field['type'] ?? ''));
            $legacyType = trim((string)($field['legacyType'] ?? ''));
            $source = is_array($field['source'] ?? null) ? $field['source'] : [];
            $selectorEntities = is_array($field['selectorEntities'] ?? null) && !empty($field['selectorEntities'])
                ? $field['selectorEntities']
                : (is_array($source['entities'] ?? null) ? $source['entities'] : []);
            $isDepartmentSelector = $type === 'department'
                || ($type === 'entity_selector' && (in_array('department', $selectorEntities, true) || $legacyType === 'department' || ($field['relationType'] ?? '') === 'department'));
            $isUserSelector = $type === 'user'
                || ($type === 'entity_selector' && (in_array('user', $selectorEntities, true) || in_array($legacyType, ['user', 'employee'], true)));

            if ($isDepartmentSelector && $this->shouldRefreshDepartmentItems($field)) {
                $field['items'] = $this->getDepartmentItems();
                $field['relationType'] = 'department';
                $field['selectorEntities'] = ['structure-node'];
                $source = is_array($field['source'] ?? null) ? $field['source'] : [];
                $source['provider'] = trim((string)($source['provider'] ?? 'ui.entity-selector')) ?: 'ui.entity-selector';
                $source['context'] = trim((string)($source['context'] ?? 'REXP_FORM')) ?: 'REXP_FORM';
                $source['entities'] = ['structure-node'];
                $field['source'] = $source;
                $fields[$index] = $field;
                continue;
            }

            if ($isUserSelector && $this->shouldRefreshUserItems($field)) {
                $field['items'] = $this->getEmployeeItems();
                $field['relationType'] = trim((string)($field['relationType'] ?? '')) ?: 'user';
                $field['selectorEntities'] = $selectorEntities !== [] ? $selectorEntities : ['user'];
                $fields[$index] = $field;
            }
        }

        return $fields;
    }

    /** @param array<string,mixed> $field */
    /**
     * Проверяет, нужно ли обновить список сотрудников для поля.
     *
     * @param array $field
     *
     * @return bool
     */
    private function shouldRefreshUserItems(array $field): bool
    {
        $items = is_array($field['items'] ?? null) ? $field['items'] : [];
        if ($items === []) {
            return true;
        }

        $hasStructuredDepartments = false;
        $hasUsersWithDepartments = false;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $entityId = trim((string)($item['entityId'] ?? ''));
            $value = trim((string)($item['value'] ?? ''));
            $itemId = trim((string)($item['id'] ?? ''));
            $isDepartment = $entityId === 'department'
                || str_starts_with($value, 'department:')
                || trim((string)($item['parentId'] ?? '')) !== ''
                || trim((string)($item['pathLabel'] ?? '')) !== ''
                || (int)($item['depth'] ?? 1) > 1;

            if ($isDepartment) {
                if ($itemId !== '' && (trim((string)($item['pathLabel'] ?? '')) !== '' || trim((string)($item['parentId'] ?? '')) !== '' || (int)($item['depth'] ?? 1) > 1)) {
                    $hasStructuredDepartments = true;
                }
                continue;
            }

            if (!empty($item['departments']) && is_array($item['departments'])) {
                $hasUsersWithDepartments = true;
            }
        }

        return !$hasStructuredDepartments || !$hasUsersWithDepartments;
    }

    /** @param array<string,mixed> $field */
    /**
     * Проверяет, нужно ли обновить список отделов для поля.
     *
     * @param array $field
     *
     * @return bool
     */
    private function shouldRefreshDepartmentItems(array $field): bool
    {
        $items = is_array($field['items'] ?? null) ? $field['items'] : [];
        if ($items === []) {
            return true;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $value = trim((string)($item['value'] ?? ''));
            $itemId = trim((string)($item['id'] ?? ''));
            if ($itemId !== '' && str_starts_with($value, 'department:') && (trim((string)($item['pathLabel'] ?? '')) !== '' || trim((string)($item['parentId'] ?? '')) !== '' || (int)($item['depth'] ?? 1) > 1)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int,array<string,mixed>> */
    /**
     * Возвращает сотрудника элементов.
     *
     * @return array
     */
    private function getEmployeeItems(): array
    {
        $this->cachedEmployeeItems ??= $this->directoryService->getEmployeeItems();
        return $this->cachedEmployeeItems;
    }

    /** @return array<int,array<string,mixed>> */
    /**
     * Возвращает отдела элементов.
     *
     * @return array
     */
    private function getDepartmentItems(): array
    {
        $this->cachedDepartmentItems ??= $this->directoryService->getDepartmentItems();
        return $this->cachedDepartmentItems;
    }
}
