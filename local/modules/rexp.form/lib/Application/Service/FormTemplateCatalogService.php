<?php

namespace Rexp\Form\Application\Service;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис каталога шаблонов форм.
 */
final class FormTemplateCatalogService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param TemplatePreviewService $previewService
     */
    public function __construct(
        private readonly TemplatePreviewService $previewService,
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
     * Возвращает по кода.
     *
     * @param string $templateCode
     *
     * @return ?array
     */
    public function getByCode(string $templateCode): ?array
    {
        foreach ($this->getRawTemplates() as $item) {
            if ((string)($item['code'] ?? '') === $templateCode) {
                return $item;
            }
        }

        return null;
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
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_001'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_002'),
                'schema' => $this->normalizeSchema([]),
            ],
            [
                'code' => 'request_basic',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_003'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_004'),
                'schema' => $this->normalizeSchema([
                    'sections' => [
                        ['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_005'), 'description' => ''],
                    ],
                    'fields' => [
                        ['uid' => 'field_name', 'sectionId' => 'main', 'width' => 6, 'code' => 'full_name', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_006'), 'type' => 'string', 'required' => true],
                        ['uid' => 'field_department', 'sectionId' => 'main', 'width' => 6, 'code' => 'department', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_007'), 'type' => 'department', 'required' => true],
                        ['uid' => 'field_comment', 'sectionId' => 'main', 'width' => 12, 'code' => 'comment', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_008'), 'type' => 'textarea', 'required' => false],
                        ['uid' => 'field_file', 'sectionId' => 'main', 'width' => 12, 'code' => 'attachment', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_009'), 'type' => 'file', 'required' => false],
                    ],
                ]),
            ],
            [
                'code' => 'absence_request',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_010'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_011'),
                'schema' => $this->normalizeSchema([
                    'sections' => [
                        ['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_012'), 'description' => ''],
                    ],
                    'fields' => [
                        ['uid' => 'field_employee', 'sectionId' => 'main', 'width' => 6, 'code' => 'employee', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_013'), 'type' => 'user', 'required' => true],
                        ['uid' => 'field_absence_type', 'sectionId' => 'main', 'width' => 6, 'code' => 'absence_type', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_014'), 'type' => 'select', 'required' => true, 'items' => [
                            ['value' => 'vacation', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_015')],
                            ['value' => 'sick_leave', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_016')],
                            ['value' => 'business_trip', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_017')],
                        ]],
                        ['uid' => 'field_date_from', 'sectionId' => 'main', 'width' => 6, 'code' => 'date_from', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_018'), 'type' => 'date', 'required' => true],
                        ['uid' => 'field_date_to', 'sectionId' => 'main', 'width' => 6, 'code' => 'date_to', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_019'), 'type' => 'date', 'required' => true],
                        ['uid' => 'field_reason', 'sectionId' => 'main', 'width' => 12, 'code' => 'reason', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_020'), 'type' => 'textarea', 'required' => false],
                    ],
                ]),
            ],
            [
                'code' => 'absence_request_mcart',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_021'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_022'),
                'schema' => $this->buildMcartAbsenceSchema(),
            ],
            [
                'code' => 'approval_request',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_023'),
                'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_024'),
                'schema' => $this->normalizeSchema([
                    'sections' => [
                        ['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_025'), 'description' => ''],
                    ],
                    'fields' => [
                        ['uid' => 'field_initiator', 'sectionId' => 'main', 'width' => 6, 'code' => 'initiator', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_026'), 'type' => 'user', 'required' => true],
                        ['uid' => 'field_approver', 'sectionId' => 'main', 'width' => 6, 'code' => 'approver', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_027'), 'type' => 'user', 'required' => true],
                        ['uid' => 'field_urgent', 'sectionId' => 'main', 'width' => 4, 'code' => 'urgent', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_028'), 'type' => 'boolean', 'required' => false],
                        ['uid' => 'field_theme', 'sectionId' => 'main', 'width' => 8, 'code' => 'theme', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_029'), 'type' => 'string', 'required' => true],
                        ['uid' => 'field_description', 'sectionId' => 'main', 'width' => 12, 'code' => 'description', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_030'), 'type' => 'textarea', 'required' => true],
                    ],
                ]),
            ],
        ];
    }

    /**
     * Формирует mcart absence схемы.
     *
     * @return array
     */
    private function buildMcartAbsenceSchema(): array
    {
        $absenceTypes = [
            ['value' => '1', 'xmlId' => 'PAID_VACATION', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_031')],
            ['value' => '2', 'xmlId' => 'LEAVEUNPAYED', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_032')],
            ['value' => '3', 'xmlId' => 'REMOTE_DAY', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_033')],
            ['value' => '4', 'xmlId' => 'PROMOTIONAL', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_034')],
            ['value' => '5', 'xmlId' => 'SAMOTOUR', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_035')],
            ['value' => '6', 'xmlId' => 'ASSIGNMENT', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_036')],
            ['value' => '7', 'xmlId' => 'PERSONAL_DAY', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_037')],
        ];

        $currencyItems = [
            ['value' => 'rub', 'xmlId' => 'RUB', 'label' => 'RUB'],
            ['value' => 'usd', 'xmlId' => 'USD', 'label' => 'USD'],
            ['value' => 'eur', 'xmlId' => 'EUR', 'label' => 'EUR'],
        ];

        $transportItems = [
            ['value' => 'plane', 'xmlId' => 'plane', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_038')],
            ['value' => 'train', 'xmlId' => 'train', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_039')],
            ['value' => 'taxi', 'xmlId' => 'taxi', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_040')],
            ['value' => 'other', 'xmlId' => 'other', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_041')],
        ];

        $foodItems = [
            ['value' => 'included', 'xmlId' => 'included', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_042')],
            ['value' => 'self', 'xmlId' => 'self', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_043')],
            ['value' => 'unknown', 'xmlId' => 'unknown', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_044')],
        ];

        $additionalCostItems = [
            ['value' => 'none', 'xmlId' => 'none', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_045')],
            ['value' => 'visa', 'xmlId' => 'visa', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_046')],
            ['value' => 'baggage', 'xmlId' => 'baggage', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_047')],
            ['value' => 'other', 'xmlId' => 'other', 'label' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_048')],
        ];

        $schema = [
            'sections' => [
                ['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_049'), 'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_050')],
                ['uid' => 'travel', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_051'), 'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_052')],
                ['uid' => 'finance', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_053'), 'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_054')],
                ['uid' => 'service', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_055'), 'description' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_056')],
            ],
            'fields' => [
                $this->makeSelectField('absence_type', 'main', 'UF_TYPE_OF_ABSENCE', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_057'), $absenceTypes, 12, true),
                $this->makeUserField('users', 'main', 'UF_USERS', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_058'), 12, true, true),
                $this->makeUserField('user', 'main', 'UF_USER', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_059'), 12, false),
                $this->makeUserField('user_samotur', 'main', 'UF_USER_SAMOTUR', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_060'), 12, false),
                $this->makeUserField('user_assignment', 'main', 'UF_USER_ASSIGNMENT', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_061'), 12, false),
                $this->makeDateField('date_from', 'main', 'UF_DATE_FROM', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_062'), 6, true),
                $this->makeDateField('date_to', 'main', 'UF_DATE_TO', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_063'), 6, false),
                $this->makeTextareaField('comment', 'main', 'UF_COMMENT', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_064'), 12),
                $this->makeTextareaField('reason_absence', 'main', 'UF_REASON_FOR_ABSENCE', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_065'), 12),
                $this->makeTextareaField('reason_paid_vacation', 'main', 'UF_REASON_FOR_PAID_VACATION', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_066'), 12),

                $this->makeStringField('direction', 'travel', 'UF_DIRECTION', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_067'), 6),
                $this->makeStringField('tour_number', 'travel', 'UF_TOUR_NUMBER', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_068'), 6),
                $this->makeStringField('location', 'travel', 'UF_LOCATION', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_069'), 6),
                $this->makeStringField('organization', 'travel', 'UF_ORGANIZATION', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_070'), 6),
                $this->makeTextareaField('purpose', 'travel', 'UF_PURPOSE', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_071'), 12),
                $this->makeTextareaField('reason_assignment', 'travel', 'UF_REASON_FOR_ASSIGNMENT', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_072'), 12),
                $this->makeDateField('departure', 'travel', 'UF_TIME_DEPARTURE', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_073'), 6),
                $this->makeDateField('return', 'travel', 'UF_TIME_RETURN', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_074'), 6),
                $this->makeStringField('program_link', 'travel', 'UF_PROGRAM_LINK', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_075'), 6),
                $this->makeFileField('program_file', 'travel', 'UF_PROGRAM_FILE', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_076'), 6),

                $this->makeNumberField('sum', 'finance', 'UF_SUM', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_077'), 4),
                $this->makeNumberField('sum_assignment', 'finance', 'UF_SUM_FOR_ASSIGNMENT', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_078'), 4),
                $this->makeSelectField('currency', 'finance', 'UF_CURRENCY', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_079'), $currencyItems, 4),
                $this->makeTextareaField('sum_reason_assignment', 'finance', 'UF_SUM_REASON_FOR_ASSIGNMENT', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_080'), 12),
                $this->makeSelectField('transport', 'finance', 'UF_PLANNED_TRANSPORT_COST', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_081'), $transportItems, 6),
                $this->makeTextareaField('taxi_reason', 'finance', 'UF_TAXI_REASON', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_082'), 6),
                $this->makeStringField('transport_other', 'finance', 'UF_PLANNED_TRANSPORT_COST_OTHER', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_083'), 6),
                $this->makeSelectField('food_type', 'finance', 'UF_FOOD_TYPE', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_084'), $foodItems, 6),
                $this->makeSelectField('add_costs', 'finance', 'UF_ADD_COSTS', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_085'), $additionalCostItems, 6),
                $this->makeStringField('add_costs_other', 'finance', 'UF_ADD_COSTS_OTHER', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_086'), 6),
                $this->makeTextareaField('unforeseen_expenses', 'finance', 'UF_UNFORESEEN_EXPENSES', \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_087'), 12),

                $this->makeHiddenField('source', 'service', 'UF_SOURCE', 'absence_request_mcart'),
                $this->makeHiddenField('initiator', 'service', 'UF_INITIATOR_ID', '', ['defaultValueMode' => 'currentUserId']),
                $this->makeHiddenField('channel', 'service', 'UF_CHANNEL', 'designer_rules'),
            ],
            'rules' => [],
            'settings' => [
                'submitText' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_088'),
                'appearance' => [
                    'preset' => 'soft_card',
                    'layoutVariant' => 'application',
                    'pageBackground' => '#f4f5f7',
                    'panelBackground' => '#e9ecef',
                    'panelBorderColor' => '#dfe3e8',
                    'titleColor' => '#0f172a',
                    'textColor' => '#2d3340',
                    'mutedColor' => '#6b7280',
                    'inputBackground' => '#ffffff',
                    'inputBorderColor' => '#cfd7e3',
                    'inputTextColor' => '#111827',
                    'buttonBackground' => '#ef3340',
                    'buttonTextColor' => '#ffffff',
                    'buttonSecondaryTextColor' => '#ef3340',
                    'radius' => 20,
                    'inputRadius' => 14,
                    'buttonRadius' => 12,
                    'formMaxWidth' => 860,
                    'panelPadding' => 28,
                    'secondaryActionText' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_089'),
                    'secondaryActionUrl' => '',
                ],
            ],
        ];

        $absenceMainRuleSets = [
            ['field' => 'UF_USER', 'xml' => ['PAID_VACATION', 'LEAVEUNPAYED', 'REMOTE_DAY', 'PERSONAL_DAY'], 'required' => false],
            ['field' => 'UF_USER_SAMOTUR', 'xml' => ['PROMOTIONAL', 'SAMOTOUR'], 'required' => true],
            ['field' => 'UF_USER_ASSIGNMENT', 'xml' => ['ASSIGNMENT'], 'required' => true],
            ['field' => 'UF_DATE_TO', 'xml' => ['PAID_VACATION', 'LEAVEUNPAYED', 'PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => true],
            ['field' => 'UF_REASON_FOR_ABSENCE', 'xml' => ['LEAVEUNPAYED', 'SAMOTOUR', 'PROMOTIONAL', 'REMOTE_DAY', 'PERSONAL_DAY'], 'required' => true],
            ['field' => 'UF_REASON_FOR_PAID_VACATION', 'xml' => ['PAID_VACATION'], 'required' => false],
            ['field' => 'UF_DIRECTION', 'xml' => ['PROMOTIONAL', 'SAMOTOUR'], 'required' => true],
            ['field' => 'UF_TOUR_NUMBER', 'xml' => ['PROMOTIONAL', 'SAMOTOUR'], 'required' => true],
            ['field' => 'UF_SUM', 'xml' => ['PROMOTIONAL', 'SAMOTOUR'], 'required' => true],
            ['field' => 'UF_LOCATION', 'xml' => ['ASSIGNMENT'], 'required' => true],
            ['field' => 'UF_ORGANIZATION', 'xml' => ['ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_PURPOSE', 'xml' => ['ASSIGNMENT'], 'required' => true],
            ['field' => 'UF_REASON_FOR_ASSIGNMENT', 'xml' => ['ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_SUM_FOR_ASSIGNMENT', 'xml' => ['ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_CURRENCY', 'xml' => ['ASSIGNMENT', 'PROMOTIONAL', 'SAMOTOUR'], 'required' => false],
            ['field' => 'UF_SUM_REASON_FOR_ASSIGNMENT', 'xml' => ['ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_TIME_DEPARTURE', 'xml' => ['PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_TIME_RETURN', 'xml' => ['PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_PLANNED_TRANSPORT_COST', 'xml' => ['PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_FOOD_TYPE', 'xml' => ['PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_ADD_COSTS', 'xml' => ['PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_UNFORESEEN_EXPENSES', 'xml' => ['PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_PROGRAM_LINK', 'xml' => ['PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => false],
            ['field' => 'UF_PROGRAM_FILE', 'xml' => ['PROMOTIONAL', 'SAMOTOUR', 'ASSIGNMENT'], 'required' => false],
        ];

        foreach ($absenceMainRuleSets as $ruleConfig) {
            $schema['rules'] = array_merge(
                $schema['rules'],
                $this->makeVisibilityRuleSet('UF_TYPE_OF_ABSENCE', $ruleConfig['xml'], $ruleConfig['field'], (bool)$ruleConfig['required'])
            );
        }

        $schema['rules'] = array_merge(
            $schema['rules'],
            $this->makeVisibilityRuleSet('UF_PLANNED_TRANSPORT_COST', ['taxi'], 'UF_TAXI_REASON', false),
            $this->makeVisibilityRuleSet('UF_PLANNED_TRANSPORT_COST', ['other'], 'UF_PLANNED_TRANSPORT_COST_OTHER', false),
            $this->makeVisibilityRuleSet('UF_ADD_COSTS', ['other'], 'UF_ADD_COSTS_OTHER', false)
        );

        return $this->normalizeSchema($schema);
    }

    /** @return array<int,array<string,mixed>> */
    /**
     * Формирует набор правил видимости для шаблона.
     *
     * @param string $sourceFieldCode
     * @param array $xmlIds
     * @param string $targetFieldCode
     * @param bool $required
     *
     * @return array
     */
    private function makeVisibilityRuleSet(string $sourceFieldCode, array $xmlIds, string $targetFieldCode, bool $required): array
    {
        $showActions = [
            ['type' => 'show', 'fieldCode' => $targetFieldCode, 'clearOnHide' => true],
        ];
        if ($required) {
            $showActions[] = ['type' => 'require', 'fieldCode' => $targetFieldCode];
        }

        return [
            [
                'uid' => 'rule_show_' . mb_strtolower($targetFieldCode),
                'enabled' => true,
                'when' => [
                    'enabled' => true,
                    'fieldCode' => $sourceFieldCode,
                    'operator' => 'in',
                    'values' => array_values($xmlIds),
                    'compareBy' => 'xmlId',
                ],
                'actions' => $showActions,
            ],
            [
                'uid' => 'rule_hide_' . mb_strtolower($targetFieldCode),
                'enabled' => true,
                'when' => [
                    'enabled' => true,
                    'fieldCode' => $sourceFieldCode,
                    'operator' => 'not_in',
                    'values' => array_values($xmlIds),
                    'compareBy' => 'xmlId',
                ],
                'actions' => [
                    ['type' => 'hide', 'fieldCode' => $targetFieldCode, 'clearOnHide' => true],
                    ['type' => 'unrequire', 'fieldCode' => $targetFieldCode],
                    ['type' => 'clear_value', 'fieldCode' => $targetFieldCode],
                ],
            ],
        ];
    }

    /** @param array<int,array<string,mixed>> $items */
    /**
     * Формирует поле типа список для шаблона.
     *
     * @param string $uid
     * @param string $sectionId
     * @param string $code
     * @param string $label
     * @param array $items
     * @param int $width
     * @param bool $required
     *
     * @return array
     */
    private function makeSelectField(string $uid, string $sectionId, string $code, string $label, array $items, int $width = 12, bool $required = false): array
    {
        return [
            'uid' => 'field_' . $uid,
            'sectionId' => $sectionId,
            'width' => $width,
            'code' => $code,
            'label' => $label,
            'type' => 'select',
            'required' => $required,
            'items' => $items,
        ];
    }

    /**
     * Формирует поле выбора пользователя для шаблона.
     *
     * @param string $uid
     * @param string $sectionId
     * @param string $code
     * @param string $label
     * @param int $width
     * @param bool $required
     * @param bool $multiple
     *
     * @return array
     */
    private function makeUserField(string $uid, string $sectionId, string $code, string $label, int $width = 12, bool $required = false, bool $multiple = false): array
    {
        return [
            'uid' => 'field_' . $uid,
            'sectionId' => $sectionId,
            'width' => $width,
            'code' => $code,
            'label' => $label,
            'type' => 'user',
            'required' => $required,
            'multiple' => $multiple,
        ];
    }

    /**
     * Формирует поле даты для шаблона.
     *
     * @param string $uid
     * @param string $sectionId
     * @param string $code
     * @param string $label
     * @param int $width
     * @param bool $required
     *
     * @return array
     */
    private function makeDateField(string $uid, string $sectionId, string $code, string $label, int $width = 12, bool $required = false): array
    {
        return [
            'uid' => 'field_' . $uid,
            'sectionId' => $sectionId,
            'width' => $width,
            'code' => $code,
            'label' => $label,
            'type' => 'date',
            'required' => $required,
        ];
    }

    /**
     * Формирует строковое поле для шаблона.
     *
     * @param string $uid
     * @param string $sectionId
     * @param string $code
     * @param string $label
     * @param int $width
     *
     * @return array
     */
    private function makeStringField(string $uid, string $sectionId, string $code, string $label, int $width = 12): array
    {
        return [
            'uid' => 'field_' . $uid,
            'sectionId' => $sectionId,
            'width' => $width,
            'code' => $code,
            'label' => $label,
            'type' => 'string',
            'required' => false,
        ];
    }

    /**
     * Формирует числовое поле для шаблона.
     *
     * @param string $uid
     * @param string $sectionId
     * @param string $code
     * @param string $label
     * @param int $width
     *
     * @return array
     */
    private function makeNumberField(string $uid, string $sectionId, string $code, string $label, int $width = 12): array
    {
        return [
            'uid' => 'field_' . $uid,
            'sectionId' => $sectionId,
            'width' => $width,
            'code' => $code,
            'label' => $label,
            'type' => 'number',
            'required' => false,
        ];
    }

    /**
     * Формирует многострочное поле для шаблона.
     *
     * @param string $uid
     * @param string $sectionId
     * @param string $code
     * @param string $label
     * @param int $width
     *
     * @return array
     */
    private function makeTextareaField(string $uid, string $sectionId, string $code, string $label, int $width = 12): array
    {
        return [
            'uid' => 'field_' . $uid,
            'sectionId' => $sectionId,
            'width' => $width,
            'code' => $code,
            'label' => $label,
            'type' => 'textarea',
            'required' => false,
        ];
    }

    /**
     * Формирует файловое поле для шаблона.
     *
     * @param string $uid
     * @param string $sectionId
     * @param string $code
     * @param string $label
     * @param int $width
     *
     * @return array
     */
    private function makeFileField(string $uid, string $sectionId, string $code, string $label, int $width = 12): array
    {
        return [
            'uid' => 'field_' . $uid,
            'sectionId' => $sectionId,
            'width' => $width,
            'code' => $code,
            'label' => $label,
            'type' => 'file',
            'required' => false,
        ];
    }

    /** @param array<string,mixed> $hiddenSettings */
    /**
     * Формирует скрытое поле для шаблона.
     *
     * @param string $uid
     * @param string $sectionId
     * @param string $code
     * @param mixed $defaultValue
     * @param array $hiddenSettings
     *
     * @return array
     */
    private function makeHiddenField(string $uid, string $sectionId, string $code, mixed $defaultValue = '', array $hiddenSettings = []): array
    {
        $settings = [
            'defaultValueMode' => 'static',
            'defaultValue' => $defaultValue,
            'urlParam' => '',
            'sourceFieldCode' => '',
        ];

        foreach ($hiddenSettings as $key => $value) {
            $settings[$key] = $value;
        }

        return [
            'uid' => 'field_' . $uid,
            'sectionId' => $sectionId,
            'width' => 12,
            'code' => $code,
            'label' => $code,
            'type' => 'hidden',
            'required' => false,
            'hiddenSettings' => $settings,
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
            : [['uid' => 'main', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTEMPLATECATALOGSERVICE_090'), 'description' => '']];
        $schema['fields'] = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $schema['rules'] = is_array($schema['rules'] ?? null) ? $schema['rules'] : [];
        $schema['settings'] = is_array($schema['settings'] ?? null) ? $schema['settings'] : [];

        return $schema;
    }
}
