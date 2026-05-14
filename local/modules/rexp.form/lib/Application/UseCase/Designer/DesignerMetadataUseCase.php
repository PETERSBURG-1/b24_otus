<?php

namespace Rexp\Form\Application\UseCase\Designer;

use Rexp\Form\Application\Service\FormDiagnosticsService;
use Rexp\Form\Application\Service\FormPermissionService;
use Rexp\Form\Application\Service\FormReadService;
use Rexp\Form\Application\Service\FormSettingsService;
use Rexp\Form\Application\Service\FormStateAssetService;
use Rexp\Form\Application\Service\FormTargetResolverService;
use Rexp\Form\Application\Service\UserFieldTypeAdapterService;

/**
 * Use case метаданных редактора формы.
 */
final class DesignerMetadataUseCase
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormReadService $readService
     * @param FormPermissionService $permissionService
     * @param FormDiagnosticsService $diagnosticsService
     * @param FormSettingsService $settingsService
     * @param FormStateAssetService $stateAssetService
     * @param FormTargetResolverService $targetResolver
     * @param UserFieldTypeAdapterService $userFieldAdapter
     */
    public function __construct(
        private readonly FormReadService $readService,
        private readonly FormPermissionService $permissionService,
        private readonly FormDiagnosticsService $diagnosticsService,
        private readonly FormSettingsService $settingsService,
        private readonly FormStateAssetService $stateAssetService,
        private readonly FormTargetResolverService $targetResolver,
        private readonly UserFieldTypeAdapterService $userFieldAdapter,
    ) {
    }

    /**
     * Возвращает результаты диагностики формы.
     *
     * @param int $id
     *
     * @return array
     */
    public function diagnostics(int $id): array
    {
        $this->permissionService->assertCanManage();

        return $this->diagnosticsService->runForForm($id) + [
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Возвращает поля types.
     *
     * @param string $entityId
     *
     * @return array
     */
    public function getFieldTypes(string $entityId = ''): array
    {
        $this->permissionService->assertCanManage();

        return [
            'fieldTypes' => $this->targetResolver->getFieldTypeDefinitions(),
            'ruleOperators' => $this->targetResolver->getRuleOperators(),
            'ruleActions' => $this->targetResolver->getRuleActions(),
            'userFieldTypes' => $this->userFieldAdapter->getUserFieldTypes(),
            'userFields' => trim($entityId) !== '' ? $this->userFieldAdapter->getFieldsForEntity($entityId) : [],
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Возвращает пользователя полей.
     *
     * @param string $entityId
     *
     * @return array
     */
    public function getUserFields(string $entityId): array
    {
        $this->permissionService->assertCanManage();

        return [
            'items' => $this->userFieldAdapter->getFieldsForEntity($entityId),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Возвращает настроек.
     *
     * @return array
     */
    public function getSettings(): array
    {
        $this->permissionService->assertCanManageSettings();

        return [
            'settings' => $this->settingsService->getSettings(),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Сохраняет настроек.
     *
     * @param array $settings
     *
     * @return array
     */
    public function saveSettings(array $settings): array
    {
        return [
            'settings' => $this->settingsService->saveSettings($settings),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Загружает изображение для состояния формы.
     *
     * @param array $file
     *
     * @return array
     */
    public function uploadStateImage(array $file): array
    {
        $this->permissionService->assertCanManage();

        return [
            'file' => $this->stateAssetService->uploadImage($file),
            'permissions' => $this->readService->getPermissions(),
        ];
    }
}
