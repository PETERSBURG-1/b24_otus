<?php

namespace Rexp\Form\Application\UseCase\Designer;

use Rexp\Form\Application\DTO\SaveFormCommand;
use Rexp\Form\Application\Service\FormDraftService;
use Rexp\Form\Application\Service\FormExportService;
use Rexp\Form\Application\Service\FormImportService;
use Rexp\Form\Application\Service\FormPermissionService;
use Rexp\Form\Application\Service\FormPublishService;
use Rexp\Form\Application\Service\FormReadService;
use Rexp\Form\Application\Service\FormTargetResolverService;
use Rexp\Form\Application\Service\FormTemplateCatalogService;
use Rexp\Form\Application\Service\FormTemplateCreateService;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Use case операций с формами в редакторе конструктора.
 */
final class DesignerFormUseCase
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormReadService $readService
     * @param FormPublishService $publishService
     * @param FormDraftService $draftService
     * @param FormTemplateCatalogService $templateCatalogService
     * @param FormTemplateCreateService $templateCreateService
     * @param FormImportService $importService
     * @param FormExportService $exportService
     * @param FormPermissionService $permissionService
     * @param FormTargetResolverService $targetResolver
     */
    public function __construct(
        private readonly FormReadService $readService,
        private readonly FormPublishService $publishService,
        private readonly FormDraftService $draftService,
        private readonly FormTemplateCatalogService $templateCatalogService,
        private readonly FormTemplateCreateService $templateCreateService,
        private readonly FormImportService $importService,
        private readonly FormExportService $exportService,
        private readonly FormPermissionService $permissionService,
        private readonly FormTargetResolverService $targetResolver,
    ) {
    }

    /**
     * Возвращает список записей.
     *
     * @param bool $withArchived
     * @param int $userId
     *
     * @return array
     */
    public function list(bool $withArchived, int $userId): array
    {
        return [
            'items' => $this->readService->list($withArchived, $userId),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Возвращает данные по переданным параметрам.
     *
     * @param int $id
     * @param int $userId
     *
     * @return array
     */
    public function get(int $id, int $userId): array
    {
        return [
            'item' => $this->readService->get($id, $userId),
            'draft' => $this->draftService->get($id, $userId),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Сохраняет данные и возвращает результат операции.
     *
     * @param array $form
     * @param int $userId
     *
     * @return array
     */
    public function save(array $form, int $userId): array
    {
        $normalized = $this->targetResolver->normalizeFromRequest($form);

        $command = new SaveFormCommand(
            (int)($form['id'] ?? 0),
            (string)($form['name'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_USECASE_DESIGNER_DESIGNERFORMUSECASE_001')),
            (string)($form['code'] ?? 'new_form'),
            $this->toBool($form['active'] ?? true),
            $this->toBool($form['archived'] ?? false),
            $normalized['schema'],
            $userId,
            true,
        );

        return [
            'item' => $this->publishService->save($command),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Перемещает форму в архив.
     *
     * @param int $id
     * @param int $userId
     *
     * @return array
     */
    public function archive(int $id, int $userId): array
    {
        $this->publishService->archive($id, $userId);

        return [
            'success' => true,
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Восстанавливает форму из архива или версии.
     *
     * @param int $id
     * @param int $userId
     *
     * @return array
     */
    public function restore(int $id, int $userId): array
    {
        $this->publishService->restore($id, $userId);

        return [
            'success' => true,
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Создаёт копию формы.
     *
     * @param int $id
     * @param int $userId
     *
     * @return array
     */
    public function duplicate(int $id, int $userId): array
    {
        return [
            'item' => $this->publishService->duplicate($id, $userId),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Удаляет данные.
     *
     * @param int $id
     * @param int $userId
     *
     * @return array
     */
    public function delete(int $id, int $userId): array
    {
        $this->publishService->delete($id, $userId);

        return [
            'success' => true,
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Возвращает список шаблонов форм.
     *
     * @return array
     */
    public function templates(): array
    {
        $this->permissionService->assertCanManage();

        return [
            'items' => $this->templateCatalogService->list(),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Создаёт from шаблона.
     *
     * @param string $templateCode
     * @param int $userId
     *
     * @return array
     */
    public function createFromTemplate(string $templateCode, int $userId): array
    {
        $command = $this->templateCreateService->buildCreateCommand($templateCode, $userId);

        return [
            'item' => $this->publishService->save($command),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Подготавливает данные формы к экспорту.
     *
     * @param int $id
     * @param int $userId
     *
     * @return array
     */
    public function export(int $id, int $userId): array
    {
        $item = $this->readService->get($id, $userId);
        if (!$item) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_USECASE_DESIGNER_DESIGNERFORMUSECASE_002'));
        }

        return [
            'item' => $this->exportService->export($item),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Подготавливает импорт формы.
     *
     * @param array $payload
     * @param int $userId
     *
     * @return array
     */
    public function import(array $payload, int $userId): array
    {
        $command = $this->importService->buildImportCommand($payload, $userId);

        return [
            'item' => $this->publishService->save($command),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Приводит значение к булевому виду.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value > 0;
        }

        $value = mb_strtolower(trim((string)$value));
        if ($value === '') {
            return false;
        }

        return in_array($value, ['1', 'y', 'yes', 'true', 'on', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_USECASE_DESIGNER_DESIGNERFORMUSECASE_003')], true);
    }
}
