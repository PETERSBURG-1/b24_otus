<?php


use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Json;
use Rexp\Form\Application\Service\FormDraftService;
use Rexp\Form\Application\Service\FormReadService;
use Rexp\Form\Infrastructure\Persistence\Orm\FormTable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Компонент списка форм конструктора.
 */
class RexpFormDesignerComponent extends CBitrixComponent
{
    private const GRID_ID = 'rexp_form_designer_grid';
    private const FILTER_ID = 'rexp_form_designer_filter';

    /**
     * Нормализует входные параметры компонента.
     *
     * @param mixed $arParams
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['CONTROLLER'] = (string)($arParams['CONTROLLER'] ?? 'rexp:form.Designer');
        $arParams['EDITOR_URL'] = (string)($arParams['EDITOR_URL'] ?? '/forms/designer/editor.php');
        $arParams['SETTINGS_URL'] = (string)($arParams['SETTINGS_URL'] ?? '/forms/designer/settings.php');
        $arParams['MODULE_SETTINGS_URL'] = (string)($arParams['MODULE_SETTINGS_URL'] ?? '/forms/designer/module-settings.php');
        $arParams['SUBMISSIONS_URL'] = (string)($arParams['SUBMISSIONS_URL'] ?? '/forms/designer/submissions.php');
        $arParams['BIZPROC_URL'] = (string)($arParams['BIZPROC_URL'] ?? '/forms/designer/bizproc.php');
        $arParams['VERSIONS_URL'] = (string)($arParams['VERSIONS_URL'] ?? '/forms/designer/versions.php');
        $arParams['DRAFTS_URL'] = (string)($arParams['DRAFTS_URL'] ?? '/forms/designer/drafts.php');
        $arParams['RUNTIME_URL'] = (string)($arParams['RUNTIME_URL'] ?? '/forms/runtime/index.php');
        $arParams['TITLE'] = (string)($arParams['TITLE'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_001'));
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
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_002'));
            return;
        }

        $permissions = $this->readService()->getPermissions();
        if (empty($permissions['canReadForms'])) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_003'));
            return;
        }

        $gridOptions = new GridOptions(self::GRID_ID);
        $sort = $gridOptions->GetSorting([
            'sort' => ['UPDATED_AT' => 'DESC', 'ID' => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);
        $sortBy = (array)($sort['sort'] ?? ['UPDATED_AT' => 'DESC']);

        $navigation = new PageNavigation(self::GRID_ID);
        $navigation->allowAllRecords(true)
            ->setPageSize($gridOptions->GetNavParams()['nPageSize'] ?? 20)
            ->initFromUri();

        $filter = $this->buildOrmFilter();
        $totalCount = FormTable::getCount($filter);
        $navigation->setRecordCount($totalCount);

        $result = FormTable::getList([
            'select' => [
                'ID',
                'NAME',
                'CODE',
                'ACTIVE',
                'ARCHIVED',
                'CREATED_AT',
                'UPDATED_AT',
            ],
            'filter' => $filter,
            'order' => $sortBy,
            'offset' => $navigation->getOffset(),
            'limit' => $navigation->getLimit(),
        ]);

        $items = [];
        while ($row = $result->fetch()) {
            $items[] = $row;
        }

        $draftMetaMap = $this->draftService()->getMetaMap(
            array_map(static fn(array $item) => (int)$item['ID'], $items),
            $this->getCurrentUserId()
        );

        $this->arResult = [
            'TITLE' => $this->arParams['TITLE'],
            'CONTROLLER' => $this->arParams['CONTROLLER'],
            'EDITOR_URL' => $this->arParams['EDITOR_URL'],
            'SETTINGS_URL' => $this->arParams['SETTINGS_URL'],
            'MODULE_SETTINGS_URL' => $this->arParams['MODULE_SETTINGS_URL'],
            'SUBMISSIONS_URL' => $this->arParams['SUBMISSIONS_URL'],
            'BIZPROC_URL' => $this->arParams['BIZPROC_URL'],
            'VERSIONS_URL' => $this->arParams['VERSIONS_URL'],
            'DRAFTS_URL' => $this->arParams['DRAFTS_URL'],
            'RUNTIME_URL' => $this->arParams['RUNTIME_URL'],
            'GRID_ID' => self::GRID_ID,
            'FILTER_ID' => self::FILTER_ID,
            'FILTER_FIELDS' => $this->getFilterFields(),
            'FILTER_PRESETS' => $this->getFilterPresets(),
            'COLUMNS' => $this->getGridColumns(),
            'ROWS' => $this->buildRows($items, $draftMetaMap),
            'NAV_OBJECT' => $navigation,
            'TOTAL_ROWS_COUNT' => $totalCount,
            'SORT' => $sortBy,
            'PERMISSIONS' => $permissions,
            'ACTION_PANEL' => $this->getActionPanel(),
            'SETTINGS_MENU' => $this->getSettingsMenu(),
        ];

        $this->includeComponentTemplate();
    }

    /**
     * Возвращает настроек menu.
     *
     * @return array
     */
    private function getSettingsMenu(): array
    {
        $permissions = $this->readService()->getPermissions();
        if (empty($permissions['canManageSettings'])) {
            return [];
        }

        return [
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_004'),
                'url' => (string)$this->arParams['MODULE_SETTINGS_URL'],
            ],
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_005'),
                'url' => (string)$this->arParams['SETTINGS_URL'],
            ],
            [
                'delimiter' => true,
            ],
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_006'),
                'url' => (string)$this->arParams['SUBMISSIONS_URL'],
            ],
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_007'),
                'url' => (string)$this->arParams['DRAFTS_URL'],
            ],
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_008'),
                'url' => (string)$this->arParams['VERSIONS_URL'],
            ],
        ];
    }

    /**
     * Возвращает описание полей фильтра.
     *
     * @return array
     */
    private function getFilterFields(): array
    {
        return [
            ['id' => 'FIND', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_009'), 'type' => 'string', 'default' => true],
            ['id' => 'NAME', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_010'), 'type' => 'string'],
            ['id' => 'CODE', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_011'), 'type' => 'string'],
            ['id' => 'ACTIVE', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_012'), 'type' => 'list', 'items' => ['Y' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_013'), 'N' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_014')]],
            ['id' => 'ARCHIVED', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_015'), 'type' => 'list', 'items' => ['Y' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_016'), 'N' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_017')]],
        ];
    }

    /**
     * Возвращает предустановки фильтра.
     *
     * @return array
     */
    private function getFilterPresets(): array
    {
        return [
            'active' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_018'),
                'default' => true,
                'fields' => [
                    'ACTIVE' => 'Y',
                    'ARCHIVED' => 'N',
                ],
            ],
            'archived' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_019'),
                'fields' => [
                    'ARCHIVED' => 'Y',
                ],
            ],
        ];
    }

    /**
     * Возвращает описание колонок грида.
     *
     * @return array
     */
    private function getGridColumns(): array
    {
        return [
            ['id' => 'NAME', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_020'), 'default' => true, 'sort' => 'NAME'],
            ['id' => 'STATUS', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_021'), 'default' => true, 'sort' => 'ACTIVE'],
            ['id' => 'DRAFT', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_022'), 'default' => true],
            ['id' => 'UPDATED_AT', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_023'), 'default' => true, 'sort' => 'UPDATED_AT'],
        ];
    }

    /**
     * Возвращает описание панели массовых действий грида.
     *
     * @return array
     */
    private function getActionPanel(): array
    {
        return ['GROUPS' => []];
    }

    /**
     * Формирует ORM-фильтр по параметрам грида.
     *
     * @return array
     */
    private function buildOrmFilter(): array
    {
        $filterData = (new FilterOptions(self::FILTER_ID))->getFilter($this->getFilterFields());
        $filter = [];

        $find = trim((string)($filterData['FIND'] ?? ''));
        if ($find !== '') {
            $filter[] = [
                'LOGIC' => 'OR',
                '%NAME' => $find,
                '%CODE' => $find,
            ];
        }

        $name = trim((string)($filterData['NAME'] ?? ''));
        if ($name !== '') {
            $filter['%NAME'] = $name;
        }

        $code = trim((string)($filterData['CODE'] ?? ''));
        if ($code !== '') {
            $filter['%CODE'] = $code;
        }

        $active = (string)($filterData['ACTIVE'] ?? '');
        if (in_array($active, ['Y', 'N'], true)) {
            $filter['=ACTIVE'] = $active;
        }

        $archived = (string)($filterData['ARCHIVED'] ?? '');
        if (in_array($archived, ['Y', 'N'], true)) {
            $filter['=ARCHIVED'] = $archived;
        }

        return $filter;
    }

    /**
     * Формирует строки грида.
     *
     * @param array $items
     * @param array $draftMetaMap
     *
     * @return array
     */
    private function buildRows(array $items, array $draftMetaMap): array
    {
        $rows = [];
        foreach ($items as $item) {
            $id = (int)$item['ID'];
            $draft = $draftMetaMap[$id] ?? null;
            $editorUrl = $this->buildEditorUrl($id);
            $bizprocUrl = $this->buildBizprocUrl($id);
            $versionsUrl = $this->buildVersionsUrl($id);
            $draftsUrl = $this->buildDraftsUrl($id);
            $runtimeUrl = $this->buildRuntimeUrl((string)($item['CODE'] ?? ''));

            $rows[] = [
                'id' => $id,
                'columns' => [
                    'NAME' => $this->renderNameCell($item, $editorUrl),
                    'STATUS' => $this->renderStatusCell($item),
                    'DRAFT' => $this->renderDraftCell($draft),
                    'UPDATED_AT' => $this->formatDateTime($item['UPDATED_AT'] ?? null),
                ],
                'actions' => $this->buildRowActions($item, $editorUrl, $runtimeUrl, $bizprocUrl, $versionsUrl, $draftsUrl),
            ];
        }

        return $rows;
    }

    /**
     * Формирует действия строки грида.
     *
     * @param array $item
     * @param string $editorUrl
     * @param string $runtimeUrl
     * @param string $bizprocUrl
     * @param string $versionsUrl
     * @param string $draftsUrl
     *
     * @return array
     */
    private function buildRowActions(array $item, string $editorUrl, string $runtimeUrl, string $bizprocUrl, string $versionsUrl, string $draftsUrl): array
    {
        $permissions = $this->readService()->getPermissions();
        $id = (int)($item['ID'] ?? 0);
        $isArchived = (string)($item['ARCHIVED'] ?? 'N') === 'Y';

        $items = [
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_024'),
                'onclick' => 'BX.RexpFormGrid.openEditor(\'' . CUtil::JSEscape($editorUrl) . '\');',
            ],
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_025'),
                'onclick' => 'BX.RexpFormGrid.openPublicForm(\'' . CUtil::JSEscape($runtimeUrl) . '\');',
            ],
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_026'),
                'onclick' => 'BX.RexpFormGrid.openSidePanel(\'' . CUtil::JSEscape($versionsUrl) . '\');',
            ],
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_027'),
                'onclick' => 'BX.RexpFormGrid.openSidePanel(\'' . CUtil::JSEscape($draftsUrl) . '\');',
            ],
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_028'),
                'onclick' => 'BX.RexpFormGrid.openPage(\'' . CUtil::JSEscape($bizprocUrl) . '\');',
            ],
        ];

        if (!empty($permissions['canManage']) || !empty($permissions['canDelete'])) {
            $items[] = [
                'delimiter' => true,
            ];
        }

        if (!empty($permissions['canManage'])) {
            $items[] = [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_029'),
                'onclick' => 'BX.RexpFormGrid.runAction(\'duplicate\', {id: ' . $id . '});',
            ];

            if ($isArchived) {
                $items[] = [
                    'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_030'),
                    'onclick' => 'BX.RexpFormGrid.runAction(\'restore\', {id: ' . $id . '}, \'' . CUtil::JSEscape((string)\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_031')) . '\');',
                ];
            } else {
                $items[] = [
                    'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_032'),
                    'onclick' => 'BX.RexpFormGrid.runAction(\'archive\', {id: ' . $id . '}, \'' . CUtil::JSEscape((string)\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_033')) . '\');',
                ];
            }
        }

        if (!empty($permissions['canDelete'])) {
            $items[] = [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_034'),
                'onclick' => 'BX.RexpFormGrid.runAction(\'delete\', {id: ' . $id . '}, \'' . CUtil::JSEscape((string)\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_035')) . '\');',
            ];
        }

        return $items;
    }


    /**
     * Формирует HTML-ячейку названия.
     *
     * @param array $item
     * @param string $editorUrl
     *
     * @return string
     */
    private function renderNameCell(array $item, string $editorUrl): string
    {
        $name = htmlspecialcharsbx((string)($item['NAME'] ?? '—'));
        $code = htmlspecialcharsbx((string)($item['CODE'] ?? ''));

        return '<div class="rf-grid-cell">'
            . '<a class="rf-grid-cell__name" href="' . htmlspecialcharsbx($editorUrl) . '" onclick="BX.RexpFormGrid.openEditor(\'' . CUtil::JSEscape($editorUrl) . '\'); return false;">' . $name . '</a>'
            . '<div class="rf-grid-cell__meta">' . $code . '</div>'
            . '</div>';
    }

    /**
     * Формирует HTML-ячейку статуса.
     *
     * @param array $item
     *
     * @return string
     */
    private function renderStatusCell(array $item): string
    {
        $badges = [];
        $badges[] = '<span class="rf-badge ' . ((string)$item['ACTIVE'] === 'Y' ? 'rf-badge--success' : 'rf-badge--muted') . '">'
            . ((string)$item['ACTIVE'] === 'Y' ? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_036') : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_037'))
            . '</span>';
        if ((string)$item['ARCHIVED'] === 'Y') {
            $badges[] = '<span class="rf-badge rf-badge--warning">' . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_038') . '</span>';
        }

        return implode(' ', $badges);
    }

    /**
     * Формирует HTML-ячейку черновика.
     *
     * @param ?array $draft
     *
     * @return string
     */
    private function renderDraftCell(?array $draft): string
    {
        if (!$draft || empty($draft['hasDraft'])) {
            return '<span class="rf-grid-cell__meta">—</span>';
        }

        $date = htmlspecialcharsbx((string)($draft['updatedAtLabel'] ?? $draft['updatedAt'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_039')));
        return '<div class="rf-grid-cell">'
            . '<span class="rf-badge rf-badge--draft">' . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_CLASS_040') . '</span>'
            . '<div class="rf-grid-cell__meta">' . $date . '</div>'
            . '</div>';
    }


    /**
     * Форматирует дату и время для вывода.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function formatDateTime(mixed $value): string
    {
        if ($value instanceof \Bitrix\Main\Type\DateTime) {
            return htmlspecialcharsbx($value->toString());
        }

        return htmlspecialcharsbx((string)$value);
    }

    /**
     * Формирует URL редактора формы.
     *
     * @param int $id
     *
     * @return string
     */
    private function buildEditorUrl(int $id = 0): string
    {
        $url = $this->arParams['EDITOR_URL'];
        return $id > 0 ? $url . '?FORM_ID=' . $id : $url;
    }

    /**
     * Формирует URL страницы версий формы.
     *
     * @param int $id
     *
     * @return string
     */
    private function buildVersionsUrl(int $id = 0): string
    {
        $url = $this->arParams['VERSIONS_URL'];
        return $id > 0 ? $url . '?FORM_ID=' . $id : $url;
    }

    /**
     * Формирует URL страницы черновиков формы.
     *
     * @param int $id
     *
     * @return string
     */
    private function buildDraftsUrl(int $id = 0): string
    {
        $url = $this->arParams['DRAFTS_URL'];
        return $id > 0 ? $url . '?FORM_ID=' . $id : $url;
    }


    /**
     * Формирует URL страницы шаблонов бизнес-процессов формы.
     *
     * @param int $id
     *
     * @return string
     */
    private function buildBizprocUrl(int $id = 0): string
    {
        $url = $this->arParams['BIZPROC_URL'];
        return $id > 0 ? $url . '?FORM_ID=' . $id : $url;
    }

    /**
     * Формирует URL публичной формы.
     *
     * @param string $code
     *
     * @return string
     */
    private function buildRuntimeUrl(string $code): string
    {
        $url = (string)$this->arParams['RUNTIME_URL'];
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'code=' . rawurlencode($code);
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

    /**
     * Возвращает сервис черновиков форм.
     *
     * @return FormDraftService
     */
    private function draftService(): FormDraftService
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.draft');
    }
}
