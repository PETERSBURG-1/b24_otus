<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Type\DateTime;
use Rexp\Form\Application\DTO\SaveFormCommand;
use Rexp\Form\Infrastructure\Event\FormEventDispatcher;
use Rexp\Form\Infrastructure\Persistence\FormRepository;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис импорта и экспорта форм.
 */
final class FormImportExportService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormRepository $formRepository
     * @param FormPermissionService $permissionService
     * @param FormEventDispatcher $eventDispatcher
     * @param FormTargetResolverService $targetResolver
     */
    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormPermissionService $permissionService,
        private readonly FormEventDispatcher $eventDispatcher,
        private readonly FormTargetResolverService $targetResolver,
    ) {
    }

    /**
     * Подготавливает данные формы к экспорту.
     *
     * @param array $item
     *
     * @return array
     */
    public function export(array $item): array
    {
        if (empty($item['id'])) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMIMPORTEXPORTSERVICE_001'));
        }

        $payload = [
            'name' => (string)$item['name'],
            'code' => (string)$item['code'],
            'exportedAt' => (new DateTime())->toString(),
            'schemaVersion' => 1,
            'module' => 'rexp.form',
            'form' => [
                'name' => (string)$item['name'],
                'code' => (string)$item['code'],
                'status' => !empty($item['active']) ? 'published' : 'draft',
                'schema' => is_array($item['schema'] ?? null) ? $item['schema'] : [],
            ],
        ];

        $this->eventDispatcher->dispatch('onFormExport', [
            'formId' => (int)$item['id'],
            'payload' => $payload,
        ]);

        return $payload;
    }

    /**
     * Формирует import command.
     *
     * @param array $payload
     * @param int $userId
     *
     * @return SaveFormCommand
     */
    public function buildImportCommand(array $payload, int $userId): SaveFormCommand
    {
        $this->permissionService->assertCanManage();

        $form = is_array($payload['form'] ?? null) ? $payload['form'] : [];
        $name = trim((string)($form['name'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMIMPORTEXPORTSERVICE_002'))) ?: \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMIMPORTEXPORTSERVICE_003');
        $baseCode = trim((string)($form['code'] ?? 'imported_form')) ?: 'imported_form';
        $uniqueCode = $this->formRepository->generateUniqueCode($baseCode, '_import');
        $normalized = $this->targetResolver->normalizeFromRequest(['schema' => is_array($form['schema'] ?? null) ? $form['schema'] : []]);

        $command = new SaveFormCommand(
            0,
            $name,
            $uniqueCode,
            (string)($form['status'] ?? 'draft') === 'published',
            false,
            $normalized['schema'],
            $userId,
            true,
        );

        $this->eventDispatcher->dispatch('onFormImportPrepared', [
            'userId' => $userId,
            'payload' => $payload,
            'command' => [
                'name' => $command->name,
                'code' => $command->code,
            ],
        ]);

        return $command;
    }
}
