<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Application\DTO\SaveFormCommand;
use Rexp\Form\Infrastructure\Event\FormEventDispatcher;
use Rexp\Form\Infrastructure\Persistence\FormRepository;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис получения шаблонов форм.
 */
final class FormTemplateService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param TemplatePreviewService $previewService
     * @param FormRepository $formRepository
     * @param FormPermissionService $permissionService
     * @param FormEventDispatcher $eventDispatcher
     */
    public function __construct(
        private readonly TemplatePreviewService $previewService,
        private readonly FormRepository $formRepository,
        private readonly FormPermissionService $permissionService,
        private readonly FormEventDispatcher $eventDispatcher,
    ) {
    }

    /**
     * Возвращает список записей.
     *
     * @return array
     */
    public function list(): array
    {
        return $this->previewService->enrichTemplates($this->getRawTemplates());
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

        $template = null;
        foreach ($this->getRawTemplates() as $item) {
            if ((string)($item['code'] ?? '') === $templateCode) {
                $template = $item;
                break;
            }
        }

        if (!$template) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_001'));
        }

        $baseCode = (string)($template['code'] ?? 'form_template');
        $uniqueCode = $this->formRepository->generateUniqueCode($baseCode, '_copy');
        $command = new SaveFormCommand(
            0,
            trim((string)($template['name'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_002'))),
            $uniqueCode,
            false,
            false,
            is_array($template['schema'] ?? null) ? $template['schema'] : [],
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

    /**
     * Возвращает raw шаблонов.
     *
     * @return array
     */
    private function getRawTemplates(): array
    {
        return [
            [
                'code' => 'blank',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_003'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_004'),
                'schema' => $this->normalizeSchema([]),
            ],
            [
                'code' => 'request_basic',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_005'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_006'),
                'schema' => $this->normalizeSchema([
                    'sections' => [
                        ['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_007'), 'description' => ''],
                    ],
                    'fields' => [
                        ['uid' => 'field_name', 'sectionId' => 'main', 'width' => 6, 'code' => 'full_name', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_008'), 'type' => 'string', 'required' => true],
                        ['uid' => 'field_department', 'sectionId' => 'main', 'width' => 6, 'code' => 'department', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_009'), 'type' => 'department', 'required' => true],
                        ['uid' => 'field_comment', 'sectionId' => 'main', 'width' => 12, 'code' => 'comment', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_010'), 'type' => 'textarea', 'required' => false],
                        ['uid' => 'field_file', 'sectionId' => 'main', 'width' => 12, 'code' => 'attachment', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_011'), 'type' => 'file', 'required' => false],
                    ],
                ]),
            ],
            [
                'code' => 'absence_request',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_012'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_013'),
                'schema' => $this->normalizeSchema([
                    'sections' => [
                        ['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_014'), 'description' => ''],
                    ],
                    'fields' => [
                        ['uid' => 'field_employee', 'sectionId' => 'main', 'width' => 6, 'code' => 'employee', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_015'), 'type' => 'user', 'required' => true],
                        ['uid' => 'field_absence_type', 'sectionId' => 'main', 'width' => 6, 'code' => 'absence_type', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_016'), 'type' => 'select', 'required' => true, 'items' => [
                            ['value' => 'vacation', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_017')],
                            ['value' => 'sick_leave', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_018')],
                            ['value' => 'business_trip', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_019')],
                        ]],
                        ['uid' => 'field_date_from', 'sectionId' => 'main', 'width' => 6, 'code' => 'date_from', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_020'), 'type' => 'date', 'required' => true],
                        ['uid' => 'field_date_to', 'sectionId' => 'main', 'width' => 6, 'code' => 'date_to', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_021'), 'type' => 'date', 'required' => true],
                        ['uid' => 'field_reason', 'sectionId' => 'main', 'width' => 12, 'code' => 'reason', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_022'), 'type' => 'textarea', 'required' => false],
                    ],
                ]),
            ],
            [
                'code' => 'approval_request',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_023'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_024'),
                'schema' => $this->normalizeSchema([
                    'sections' => [
                        ['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_025'), 'description' => ''],
                    ],
                    'fields' => [
                        ['uid' => 'field_initiator', 'sectionId' => 'main', 'width' => 6, 'code' => 'initiator', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_026'), 'type' => 'user', 'required' => true],
                        ['uid' => 'field_approver', 'sectionId' => 'main', 'width' => 6, 'code' => 'approver', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_027'), 'type' => 'user', 'required' => true],
                        ['uid' => 'field_urgent', 'sectionId' => 'main', 'width' => 4, 'code' => 'urgent', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_028'), 'type' => 'boolean', 'required' => false],
                        ['uid' => 'field_theme', 'sectionId' => 'main', 'width' => 8, 'code' => 'theme', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_029'), 'type' => 'string', 'required' => true],
                        ['uid' => 'field_description', 'sectionId' => 'main', 'width' => 12, 'code' => 'description', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_030'), 'type' => 'textarea', 'required' => true],
                    ],
                ]),
            ],
        ];
    }

    /**
     * Нормализует схемы.
     *
     * @param array $schema
     *
     * @return array
     */
    private function normalizeSchema(array $schema): array
    {
        $schema['version'] = (int)($schema['version'] ?? 1);
        $schema['sections'] = is_array($schema['sections'] ?? null) && !empty($schema['sections'])
            ? $schema['sections']
            : [['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATESERVICE_031'), 'description' => '']];
        $schema['fields'] = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $schema['settings'] = is_array($schema['settings'] ?? null) ? $schema['settings'] : [];

        return $schema;
    }
}
