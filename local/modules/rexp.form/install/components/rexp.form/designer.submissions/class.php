<?php


use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Rexp\Form\Application\Service\FormReadService;
use Rexp\Form\Infrastructure\Persistence\Orm\FormSubmissionTable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Компонент журнала отправок форм.
 */
class RexpFormDesignerSubmissionsComponent extends CBitrixComponent
{
    private int $currentFormId = 0;

    /**
     * Нормализует входные параметры компонента.
     *
     * @param mixed $arParams
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['FORM_ID'] = (int)($arParams['FORM_ID'] ?? 0);
        $arParams['GRID_ID'] = (string)($arParams['GRID_ID'] ?? 'rexp_form_submissions_grid');
        $arParams['FILTER_ID'] = (string)($arParams['FILTER_ID'] ?? 'rexp_form_submissions_filter');
        $arParams['EDITOR_URL'] = (string)($arParams['EDITOR_URL'] ?? '/forms/designer/editor.php');
        return $arParams;
    }

    /**
     * Подготавливает данные компонента и подключает шаблон.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule('rexp.form')) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_001'));
            return;
        }

        $permissions = $this->readService()->getPermissions();
        if (empty($permissions['canViewSubmissions'])) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_002'));
            return;
        }

        $formId = (int)($_REQUEST['FORM_ID'] ?? $this->arParams['FORM_ID']);
        $this->currentFormId = $formId;
        $gridId = $this->arParams['GRID_ID'] . ($formId > 0 ? '_' . $formId : '');
        $filterId = $this->arParams['FILTER_ID'] . ($formId > 0 ? '_' . $formId : '');

        $gridOptions = new GridOptions($gridId);
        $sort = $gridOptions->GetSorting([
            'sort' => ['CREATED_AT' => 'DESC', 'ID' => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);
        $sortBy = (array)($sort['sort'] ?? ['CREATED_AT' => 'DESC', 'ID' => 'DESC']);

        $navigation = new PageNavigation($gridId);
        $navigation->allowAllRecords(true)
            ->setPageSize($gridOptions->GetNavParams()['nPageSize'] ?? 20)
            ->initFromUri();

        $filter = $this->buildOrmFilter($filterId, $formId);
        $totalCount = FormSubmissionTable::getCount($filter);
        $navigation->setRecordCount($totalCount);

        $rows = [];
        $result = FormSubmissionTable::getList([
            'select' => ['*'],
            'filter' => $filter,
            'order' => $sortBy,
            'offset' => $navigation->getOffset(),
            'limit' => $navigation->getLimit(),
        ]);
        while ($row = $result->fetch()) {
            $rows[] = $row;
        }

        $this->arResult = [
            'FORM_ID' => $formId,
            'GRID_ID' => $gridId,
            'FILTER_ID' => $filterId,
            'FILTER_FIELDS' => $this->getFilterFields(),
            'FILTER_PRESETS' => $this->getFilterPresets($formId),
            'COLUMNS' => $this->getGridColumns(),
            'ROWS' => $this->buildRows($rows),
            'NAV_OBJECT' => $navigation,
            'TOTAL_ROWS_COUNT' => $totalCount,
            'SORT' => $sortBy,
            'FORM' => $formId > 0 ? $this->readService()->get($formId, $this->getCurrentUserId()) : null,
            'EDITOR_URL' => $this->buildEditorUrl($formId),
        ];

        $this->includeComponentTemplate();
    }

    /**
     * Формирует ORM-фильтр по параметрам грида.
     *
     * @param string $filterId
     * @param int $formId
     *
     * @return array
     */
    private function buildOrmFilter(string $filterId, int $formId): array
    {
        $filterData = (new FilterOptions($filterId))->getFilter($this->getFilterFields());
        $filter = [];
        if ($formId > 0) {
            $filter['=FORM_ID'] = $formId;
        }

        $find = trim((string)($filterData['FIND'] ?? ''));
        if ($find !== '') {
            $filter[] = [
                'LOGIC' => 'OR',
                '%FORM_CODE' => $find,
                '=ID' => (int)$find,
                '%STATUS' => $find,
            ];
        }

        $status = trim((string)($filterData['STATUS'] ?? ''));
        if ($status !== '') {
            $filter['=STATUS'] = $status;
        }

        $filterFormId = (int)($filterData['FORM_ID'] ?? 0);
        if ($filterFormId > 0) {
            $filter['=FORM_ID'] = $filterFormId;
        }

        return $filter;
    }

    /**
     * Возвращает описание полей фильтра.
     *
     * @return array
     */
    private function getFilterFields(): array
    {
        return [
            ['id' => 'FIND', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_003'), 'type' => 'string', 'default' => true],
            ['id' => 'FORM_ID', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_004'), 'type' => 'number'],
            ['id' => 'STATUS', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_005'), 'type' => 'list', 'items' => [
                'processing' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_006'),
                'success' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_007'),
                'partial_success' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_008'),
                'error' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_009'),
            ]],
        ];
    }

    /**
     * Возвращает предустановки фильтра.
     *
     * @param int $formId
     *
     * @return array
     */
    private function getFilterPresets(int $formId): array
    {
        $presets = [
            'recent_success' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_010'),
                'default' => true,
                'fields' => ['STATUS' => 'success'],
            ],
            'partial' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_011'),
                'fields' => ['STATUS' => 'partial_success'],
            ],
            'errors' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_012'),
                'fields' => ['STATUS' => 'error'],
            ],
        ];

        if ($formId > 0) {
            $presets['current_form'] = [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_013'),
                'fields' => ['FORM_ID' => $formId],
            ];
        }

        return $presets;
    }

    /**
     * Возвращает описание колонок грида.
     *
     * @return array
     */
    private function getGridColumns(): array
    {
        return [
            ['id' => 'ID', 'name' => 'ID', 'default' => true, 'sort' => 'ID'],
            ['id' => 'FORM', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_014'), 'default' => true],
            ['id' => 'USER', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_015'), 'default' => true, 'sort' => 'USER_ID'],
            ['id' => 'STATUS', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_016'), 'default' => true, 'sort' => 'STATUS'],
            ['id' => 'RESULT', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_017'), 'default' => true],
            ['id' => 'ERRORS', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_018'), 'default' => true],
            ['id' => 'CREATED_AT', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_019'), 'default' => true, 'sort' => 'CREATED_AT'],
        ];
    }

    /**
     * Формирует строки грида.
     *
     * @param array $items
     *
     * @return array
     */
    private function buildRows(array $items): array
    {
        $rows = [];
        foreach ($items as $item) {
            $result = $this->decodeJson((string)($item['RESULT_JSON'] ?? '{}'), []);
            $errors = $this->decodeJson((string)($item['ERRORS_JSON'] ?? '[]'), []);

            $rows[] = [
                'id' => (int)$item['ID'],
                'columns' => [
                    'ID' => '#' . (int)$item['ID'],
                    'FORM' => $this->renderFormCell($item),
                    'USER' => $this->renderUserCell($item),
                    'STATUS' => $this->renderStatusCell((string)($item['STATUS'] ?? ''), $errors),
                    'RESULT' => $this->renderResultCell($item, $result),
                    'ERRORS' => $this->renderErrorsCell($errors),
                    'CREATED_AT' => htmlspecialcharsbx(($item['CREATED_AT'] instanceof \Bitrix\Main\Type\DateTime) ? $item['CREATED_AT']->toString() : (string)($item['CREATED_AT'] ?? '')),
                ],
                'actions' => [],
            ];
        }

        return $rows;
    }

    /**
     * Формирует HTML-ячейку формы.
     *
     * @param array $item
     *
     * @return string
     */
    private function renderFormCell(array $item): string
    {
        $formId = (int)($item['FORM_ID'] ?? 0);
        $code = htmlspecialcharsbx((string)($item['FORM_CODE'] ?? ''));
        $editorUrl = $this->buildEditorUrl($formId);
        return '<div class="rf-grid-cell">'
            . '<a class="rf-grid-cell__name" href="' . htmlspecialcharsbx($editorUrl) . '">' . $code . '</a>'
            . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_020') . $formId . '</div>'
            . '</div>';
    }

    /**
     * Формирует HTML-ячейку пользователя.
     *
     * @param array $item
     *
     * @return string
     */
    private function renderUserCell(array $item): string
    {
        $userId = (int)($item['USER_ID'] ?? 0);
        if ($userId <= 0) {
            return '<span class="rf-grid-cell__meta">—</span>';
        }

        return \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_021') . $userId . '</div></div>';
    }

    /**
     * Формирует HTML-ячейку результата.
     *
     * @param array $item
     * @param array $result
     *
     * @return string
     */
    private function renderResultCell(array $item, array $result): string
    {
        $submissionId = (int)($item['ID'] ?? 0);
        $message = trim((string)($result['message'] ?? ''));
        if ($message === '') {
            $message = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_022') . $submissionId;
        }

        return '<div class="rf-grid-cell"><div>' . htmlspecialcharsbx($message) . '</div></div>';
    }

    /**
     * Формирует HTML-ячейку ошибок.
     *
     * @param array $errors
     *
     * @return string
     */
    private function renderErrorsCell(array $errors): string
    {
        if (!$errors) {
            return '<span class="rf-grid-cell__meta">—</span>';
        }

        return '<div class="rf-grid-cell__meta">'
            . htmlspecialcharsbx(implode('; ', array_slice(array_map('strval', $errors), 0, 3)))
            . '</div>';
    }

    /**
     * Формирует HTML-ячейку статуса.
     *
     * @param string $status
     * @param array $errors
     *
     * @return string
     */
    private function renderStatusCell(string $status, array $errors): string
    {
        $map = [
            'processing' => [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_023'), 'rf-badge--info'],
            'success' => [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_024'), 'rf-badge--success'],
            'partial_success' => [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_025'), 'rf-badge--warning'],
            'error' => [\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_026'), 'rf-badge--danger'],
        ];
        [$label, $class] = $map[$status] ?? [$status ?: '—', 'rf-badge--muted'];
        $html = '<span class="rf-badge ' . $class . '">' . htmlspecialcharsbx($label) . '</span>';
        if ($errors) {
            $html .= '<div class="rf-grid-cell__meta">' . htmlspecialcharsbx(implode('; ', array_slice(array_map('strval', $errors), 0, 2))) . '</div>';
        }
        return $html;
    }

    /**
     * Формирует HTML-ячейку целевой сущности.
     *
     * @param array $item
     * @param array $result
     * @param ?array $crmStep
     *
     * @return string
     */
    private function renderTargetCell(array $item, array $result, ?array $crmStep): string
    {
        $submissionId = (int)($item['ID'] ?? 0);
        $entryId = (int)($item['ENTRY_ID'] ?? ($result['entryId'] ?? 0));
        $message = trim((string)($result['message'] ?? ''));

        $parts = ['<div class="rf-grid-cell">'];
        $parts[] = '<div>' . htmlspecialcharsbx($message !== '' ? $message : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_027') . $submissionId) . '</div>';
        if ($entryId > 0) {
            $parts[] = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_028') . $entryId . '</div>';
        }
        $parts[] = '</div>';

        return implode('', $parts);
    }

    /**
     * Формирует HTML-ячейку цепочки обработки.
     *
     * @param array $pipeline
     * @param ?array $bpStep
     *
     * @return string
     */
    private function renderPipelineCell(array $pipeline, ?array $bpStep): string
    {
        if (!$pipeline) {
            return '<span class="rf-grid-cell__meta">—</span>';
        }

        $badges = [];
        foreach ($pipeline as $step) {
            if (!is_array($step)) {
                continue;
            }
            $code = htmlspecialcharsbx((string)($step['code'] ?? 'step'));
            $class = !empty($step['success']) ? 'rf-badge--success' : 'rf-badge--warning';
            $badges[] = '<span class="rf-badge ' . $class . '">' . $code . '</span>';
        }
        $meta = '';
        if ($bpStep && !empty($bpStep['workflowId'])) {
            $meta = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SUBMISSIONS_CLASS_029') . htmlspecialcharsbx((string)$bpStep['workflowId']) . '</div>';
        }
        return '<div class="rf-grid-cell">' . implode(' ', $badges) . $meta . '</div>';
    }

    /**
     * Ищет шаг обработки по коду.
     *
     * @param array $pipeline
     * @param string $code
     *
     * @return ?array
     */
    private function findStep(array $pipeline, string $code): ?array
    {
        foreach ($pipeline as $step) {
            if (is_array($step) && (string)($step['code'] ?? '') === $code) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Декодирует json.
     *
     * @param string $value
     * @param array $fallback
     *
     * @return array
     */
    private function decodeJson(string $value, array $fallback): array
    {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $fallback;
    }

    /**
     * Формирует URL редактора формы.
     *
     * @param int $formId
     *
     * @return string
     */
    private function buildEditorUrl(int $formId = 0): string
    {
        $url = (string)$this->arParams['EDITOR_URL'];
        return $formId > 0 ? $url . '?FORM_ID=' . $formId : $url;
    }

    /**
     * Возвращает идентификатор текущего пользователя.
     *
     * @return int
     */
    private function getCurrentUserId(): int
    {
        global $USER;
        return ($USER instanceof CUser && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
    }

    /**
     * Возвращает сервис чтения форм.
     *
     * @return FormReadService
     */
    private function readService(): FormReadService
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.read');
    }
}
