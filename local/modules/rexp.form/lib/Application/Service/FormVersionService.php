<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Application\DTO\SaveFormCommand;
use Rexp\Form\Infrastructure\Event\FormEventDispatcher;
use Rexp\Form\Infrastructure\Persistence\FormVersionRepository;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис работы с версиями форм.
 */
final class FormVersionService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormReadService $formReadService
     * @param FormPublishService $formPublishService
     * @param FormVersionRepository $versionRepository
     * @param FormPermissionService $permissionService
     * @param FormEventDispatcher $eventDispatcher
     */
    public function __construct(
        private readonly FormReadService $formReadService,
        private readonly FormPublishService $formPublishService,
        private readonly FormVersionRepository $versionRepository,
        private readonly FormPermissionService $permissionService,
        private readonly FormEventDispatcher $eventDispatcher,
    ) {
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
        $this->permissionService->assertCanManage();
        return $this->versionRepository->listByFormId($formId);
    }


    /**
     * Возвращает полный список записей.
     *
     * @return array
     */
    public function listAll(): array
    {
        $this->permissionService->assertCanManage();
        return $this->versionRepository->listAll();
    }

    /**
     * Сравнивает текущую форму с выбранной версией.
     *
     * @param int $formId
     * @param int $versionId
     *
     * @return array
     */
    public function compare(int $formId, int $versionId): array
    {
        $this->permissionService->assertCanManage();
        $current = $this->formReadService->get($formId);
        if (!$current) {
            return ['ok' => false, 'message' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMVERSIONSERVICE_001')];
        }

        $snapshot = $this->versionRepository->getSnapshot($versionId);
        if (!$snapshot || (int)$snapshot['formId'] !== $formId) {
            return ['ok' => false, 'message' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMVERSIONSERVICE_002')];
        }

        $currentSchema = is_array($current['schema'] ?? null) ? $current['schema'] : [];
        $versionSchema = is_array($snapshot['schema'] ?? null) ? $snapshot['schema'] : [];
        $currentFields = $this->indexFieldsByCode($currentSchema['fields'] ?? []);
        $versionFields = $this->indexFieldsByCode($versionSchema['fields'] ?? []);
        $added = [];
        $removed = [];
        $changed = [];

        foreach ($currentFields as $code => $field) {
            if (!isset($versionFields[$code])) {
                $added[] = ['code' => $code, 'label' => (string)($field['label'] ?? $code)];
                continue;
            }

            $fieldChanges = [];
            foreach (['label', 'type', 'required', 'multiple', 'sectionId', 'width'] as $propertyName) {
                $currentValue = $field[$propertyName] ?? null;
                $versionValue = $versionFields[$code][$propertyName] ?? null;
                if ($currentValue !== $versionValue) {
                    $fieldChanges[] = [
                        'property' => $propertyName,
                        'current' => $currentValue,
                        'version' => $versionValue,
                    ];
                }
            }

            if ($fieldChanges) {
                $changed[] = [
                    'code' => $code,
                    'label' => (string)($field['label'] ?? $code),
                    'changes' => $fieldChanges,
                ];
            }
        }

        foreach ($versionFields as $code => $field) {
            if (!isset($currentFields[$code])) {
                $removed[] = ['code' => $code, 'label' => (string)($field['label'] ?? $code)];
            }
        }

        return [
            'ok' => true,
            'versionId' => $versionId,
            'versionNumber' => (int)($snapshot['versionNumber'] ?? 0),
            'summary' => [
                'addedFields' => count($added),
                'removedFields' => count($removed),
                'changedFields' => count($changed),
            ],
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
        ];
    }

    /**
     * Восстанавливает форму из архива или версии.
     *
     * @param int $formId
     * @param int $versionId
     * @param int $userId
     *
     * @return ?array
     */
    public function restore(int $formId, int $versionId, int $userId): ?array
    {
        $this->permissionService->assertCanManage();

        $snapshot = $this->versionRepository->getSnapshot($versionId);
        if (!$snapshot || (int)$snapshot['formId'] !== $formId) {
            return null;
        }

        if ((string)($snapshot['status'] ?? 'draft') === 'published') {
            $this->permissionService->assertCanPublish();
        }

        $restored = $this->formPublishService->save(new SaveFormCommand(
            $formId,
            (string)$snapshot['name'],
            (string)$snapshot['code'],
            (string)($snapshot['status'] ?? 'draft') === 'published',
            false,
            is_array($snapshot['schema'] ?? null) ? $snapshot['schema'] : [],
            $userId,
            true,
        ));

        if ($restored) {
            $this->eventDispatcher->dispatch('onVersionRestored', [
                'formId' => $formId,
                'versionId' => $versionId,
                'userId' => $userId,
                'form' => $restored,
            ]);
        }

        return $restored;
    }

    /**
     * Индексирует поля формы по символьному коду.
     *
     * @param array $fields
     *
     * @return array
     */
    private function indexFieldsByCode(array $fields): array
    {
        $items = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $code = trim((string)($field['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $items[$code] = $field;
        }

        return $items;
    }
}
