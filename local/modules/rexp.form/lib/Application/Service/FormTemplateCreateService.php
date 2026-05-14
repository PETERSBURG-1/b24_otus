<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Application\DTO\SaveFormCommand;
use Rexp\Form\Infrastructure\Event\FormEventDispatcher;
use Rexp\Form\Infrastructure\Persistence\FormRepository;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис создания формы из шаблона.
 */
final class FormTemplateCreateService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormTemplateCatalogService $catalogService
     * @param FormRepository $formRepository
     * @param FormPermissionService $permissionService
     * @param FormEventDispatcher $eventDispatcher
     * @param FormTargetResolverService $targetResolver
     */
    public function __construct(
        private readonly FormTemplateCatalogService $catalogService,
        private readonly FormRepository $formRepository,
        private readonly FormPermissionService $permissionService,
        private readonly FormEventDispatcher $eventDispatcher,
        private readonly FormTargetResolverService $targetResolver,
    ) {
    }

    /**
     * Формирует create command.
     *
     * @param string $templateCode
     * @param int $userId
     *
     * @return SaveFormCommand
     */
    public function buildCreateCommand(string $templateCode, int $userId): SaveFormCommand
    {
        $this->permissionService->assertCanManage();

        $template = $this->catalogService->getByCode($templateCode);
        if (!$template) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECREATESERVICE_001'));
        }

        $baseCode = (string)($template['code'] ?? 'form_template');
        $uniqueCode = $this->formRepository->generateUniqueCode($baseCode, '_copy');
        $normalized = $this->targetResolver->normalizeFromRequest(['schema' => is_array($template['schema'] ?? null) ? $template['schema'] : []]);

        $command = new SaveFormCommand(
            0,
            trim((string)($template['name'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECREATESERVICE_002'))),
            $uniqueCode,
            false,
            false,
            $normalized['schema'],
            $userId,
            true
        );

        $this->eventDispatcher->dispatch('onFormCreateFromTemplatePrepared', [
            'templateCode' => $templateCode,
            'userId' => $userId,
            'command' => [
                'name' => $command->name,
                'code' => $command->code,
            ],
        ]);

        return $command;
    }
}
