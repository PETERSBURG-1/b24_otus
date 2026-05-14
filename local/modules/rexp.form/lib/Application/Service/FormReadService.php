<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Infrastructure\Persistence\FormRepository;

/**
 * Сервис чтения форм и подготовки данных для интерфейса.
 */
final class FormReadService
{
    private RuntimeDirectoryService $directoryService;

    /** @var array<int,array<string,mixed>>|null */
    private ?array $cachedEmployeeItems = null;

    /** @var array<int,array<string,mixed>>|null */
    private ?array $cachedDepartmentItems = null;

    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormRepository $formRepository
     * @param FormDraftService $formDraftService
     * @param FormPermissionService $permissionService
     * @param FormTargetResolverService $targetResolver
     * @param ?RuntimeDirectoryService $directoryService
     */
    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormDraftService $formDraftService,
        private readonly FormPermissionService $permissionService,
        private readonly FormTargetResolverService $targetResolver,
        ?RuntimeDirectoryService $directoryService = null,
    ) {
        $this->directoryService = $directoryService ?? new RuntimeDirectoryService();
    }

    /**
     * Возвращает набор доступных разрешений текущего пользователя.
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissionService->getPermissions();
    }

    /**
     * Возвращает список записей.
     *
     * @param bool $withArchived
     * @param int $userId
     *
     * @return array
     */
    public function list(bool $withArchived = false, int $userId = 0): array
    {
        $this->permissionService->assertCanReadForms();

        $forms = array_map(function ($form) {
            return $this->enrichSelectorFields($this->targetResolver->decorateFormArray($form->toArray()));
        }, $this->formRepository->list($withArchived));

        if ($userId > 0 && !empty($forms)) {
            $metaMap = $this->formDraftService->getMetaMap(array_column($forms, 'id'), $userId);
            foreach ($forms as &$form) {
                $meta = $metaMap[(int)$form['id']] ?? null;
                $form['draft'] = $meta ?: ['hasDraft' => false];
            }
            unset($form);
        }

        return $forms;
    }

    /**
     * Возвращает данные по переданным параметрам.
     *
     * @param int $id
     * @param int $userId
     *
     * @return ?array
     */
    public function get(int $id, int $userId = 0): ?array
    {
        $this->permissionService->assertCanReadForms();
        return $this->getInternal($id, $userId, false);
    }

    /**
     * Возвращает public по ID.
     *
     * @param int $id
     *
     * @return ?array
     */
    public function getPublicById(int $id): ?array
    {
        return $this->getInternal($id, 0, true);
    }

    /**
     * Возвращает по кода.
     *
     * @param string $code
     *
     * @return ?array
     */
    public function getByCode(string $code): ?array
    {
        $form = $this->formRepository->getActiveByCode($code)?->toArray();
        return $form ? $this->enrichSelectorFields($this->targetResolver->decorateFormArray($form)) : null;
    }

    /**
     * Возвращает internal.
     *
     * @param int $id
     * @param int $userId
     * @param bool $publicOnly
     *
     * @return ?array
     */
    private function getInternal(int $id, int $userId = 0, bool $publicOnly = false): ?array
    {
        $form = $publicOnly
            ? $this->formRepository->getActiveById($id)?->toArray()
            : $this->formRepository->getById($id)?->toArray();
        if (!$form) {
            return null;
        }

        $form = $this->enrichSelectorFields($this->targetResolver->decorateFormArray($form));
        $form['draft'] = $userId > 0
            ? ($this->formDraftService->get($id, $userId) ?: ['hasDraft' => false])
            : ['hasDraft' => false];

        return $form;
    }

    /**
     * Дополняет поля селекторов актуальными справочными значениями.
     *
     * @param array $form
     *
     * @return array
     */
    private function enrichSelectorFields(array $form): array
    {
        $schema = is_array($form['schema'] ?? null) ? $form['schema'] : [];
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        if ($fields === []) {
            return $form;
        }

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

        $schema['fields'] = $fields;
        $form['schema'] = $schema;

        return $form;
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
