<?php


use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Rexp\Form\Application\Service\FormReadService;
use Rexp\Form\Application\Service\FormVersionService;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Компонент журнала версий форм.
 */
class RexpFormDesignerVersionsComponent extends CBitrixComponent
{
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
        $arParams['FORM_ID'] = (int)($arParams['FORM_ID'] ?? 0);
        $arParams['EMBED'] = (string)($arParams['EMBED'] ?? ($_REQUEST['EMBED'] ?? 'N'));
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
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_001'));
            return;
        }

        $permissions = $this->permissionService()->getPermissions();
        if (empty($permissions['canManage'])) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_002'));
            return;
        }

        $formId = (int)($_REQUEST['FORM_ID'] ?? $_REQUEST['id'] ?? $this->arParams['FORM_ID']);
        $form = $formId > 0 ? $this->readService()->get($formId, $this->getCurrentUserId()) : null;

        $gridId = 'rexp_form_versions_grid_' . ($formId > 0 ? $formId : '0');
        $filterId = 'rexp_form_versions_filter_' . ($formId > 0 ? $formId : '0');

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

        $items = $this->normalizeItems($formId > 0 ? $this->versionService()->list($formId) : $this->versionService()->listAll());
        $items = $this->applyFilter($items, $filterId);
        $items = $this->sortItems($items, $sortBy);

        $navigation->setRecordCount(count($items));
        $pageItems = array_slice($items, $navigation->getOffset(), $navigation->getLimit());

        $this->arResult = [
            'CONTROLLER' => $this->arParams['CONTROLLER'],
            'EDITOR_URL' => $this->buildEditorUrl($formId),
            'DRAFTS_URL' => $this->buildDraftsUrl($formId),
            'FORM_ID' => $formId,
            'FORM' => $form,
            'GRID_ID' => $gridId,
            'FILTER_ID' => $filterId,
            'FILTER_FIELDS' => $this->getFilterFields(),
            'FILTER_PRESETS' => $this->getFilterPresets(),
            'COLUMNS' => $this->getGridColumns(),
            'ROWS' => $this->buildRows($pageItems),
            'NAV_OBJECT' => $navigation,
            'TOTAL_ROWS_COUNT' => count($items),
            'SORT' => $sortBy,
            'PERMISSIONS' => $this->readService()->getPermissions(),
            'EMBED' => (string)$this->arParams['EMBED'] === 'Y',
        ];

        $this->includeComponentTemplate();
    }


    /**
     * Нормализует список элементов для вывода.
     *
     * @param array $items
     *
     * @return array
     */
    private function normalizeItems(array $items): array
    {
        return array_map(static function(array $item): array {
            $item['NAME'] = (string)($item['name'] ?? '');
            $item['CODE'] = (string)($item['code'] ?? '');
            $item['TYPE'] = (string)($item['type'] ?? '');
            $item['FORM_ID'] = (int)($item['formId'] ?? 0);
            $item['STATUS'] = (string)($item['status'] ?? '');
            $item['CREATED_AT'] = (string)($item['createdAt'] ?? '');
            $item['ID'] = (int)($item['id'] ?? 0);
            return $item;
        }, $items);
    }

    /**
     * Возвращает описание полей фильтра.
     *
     * @return array
     */
    private function getFilterFields(): array
    {
        return [
            ['id' => 'FIND', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_003'), 'type' => 'string', 'default' => true],
            ['id' => 'FORM_ID', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_004'), 'type' => 'number'],
            ['id' => 'NAME', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_005'), 'type' => 'string'],
            ['id' => 'CODE', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_006'), 'type' => 'string'],
            ['id' => 'TYPE', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_007'), 'type' => 'list', 'items' => [
                'save' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_008'),
                'publish' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_009'),
                'restore' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_010'),
            ]],
            ['id' => 'STATUS', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_011'), 'type' => 'list', 'items' => [
                'published' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_012'),
                'draft' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_013'),
            ]],
            ['id' => 'CREATED_BY', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_014'), 'type' => 'number'],
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
            'latest' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_015'),
                'default' => true,
                'fields' => [],
            ],
            'published' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_016'),
                'fields' => ['STATUS' => 'published'],
            ],
            'draft' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_017'),
                'fields' => ['STATUS' => 'draft'],
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
            ['id' => 'NAME', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_018'), 'default' => true, 'sort' => 'NAME'],
            ['id' => 'FORM', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_019'), 'default' => $this->getCurrentFormIdFromRequest() <= 0],
            ['id' => 'TYPE', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_020'), 'default' => true, 'sort' => 'TYPE'],
            ['id' => 'STATUS', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_021'), 'default' => true, 'sort' => 'STATUS'],
            ['id' => 'CREATED_AT', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_022'), 'default' => true, 'sort' => 'CREATED_AT'],
            ['id' => 'ACTIONS', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_023'), 'default' => true],
        ];
    }

    /**
     * Применяет фильтр к списку элементов.
     *
     * @param array $items
     * @param string $filterId
     *
     * @return array
     */
    private function applyFilter(array $items, string $filterId): array
    {
        $filterData = (new FilterOptions($filterId))->getFilter($this->getFilterFields());

        return array_values(array_filter($items, static function(array $item) use ($filterData): bool {
            $find = mb_strtolower(trim((string)($filterData['FIND'] ?? '')));
            if ($find !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    (string)($item['name'] ?? ''),
                    (string)($item['code'] ?? ''),
                    (string)($item['type'] ?? ''),
                    (string)($item['status'] ?? ''),
                ]));
                if (!str_contains($haystack, $find)) {
                    return false;
                }
            }

            $filterFormId = (int)($filterData['FORM_ID'] ?? 0);
            if ($filterFormId > 0 && (int)($item['formId'] ?? 0) !== $filterFormId) {
                return false;
            }

            $name = mb_strtolower(trim((string)($filterData['NAME'] ?? '')));
            if ($name !== '' && !str_contains(mb_strtolower((string)($item['name'] ?? '')), $name)) {
                return false;
            }

            $code = mb_strtolower(trim((string)($filterData['CODE'] ?? '')));
            if ($code !== '' && !str_contains(mb_strtolower((string)($item['code'] ?? '')), $code)) {
                return false;
            }

            $type = trim((string)($filterData['TYPE'] ?? ''));
            if ($type !== '' && (string)($item['type'] ?? '') !== $type) {
                return false;
            }

            $status = trim((string)($filterData['STATUS'] ?? ''));
            if ($status !== '' && (string)($item['status'] ?? '') !== $status) {
                return false;
            }

            $createdBy = (int)($filterData['CREATED_BY'] ?? 0);
            if ($createdBy > 0 && (int)($item['createdBy'] ?? 0) !== $createdBy) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Сортирует список элементов.
     *
     * @param array $items
     * @param array $sortBy
     *
     * @return array
     */
    private function sortItems(array $items, array $sortBy): array
    {
        foreach (array_reverse($sortBy, true) as $by => $direction) {
            usort($items, static function(array $left, array $right) use ($by, $direction): int {
                $leftValue = (string)($left[$by] ?? '');
                $rightValue = (string)($right[$by] ?? '');
                $result = $leftValue <=> $rightValue;
                return strtoupper((string)$direction) === 'DESC' ? -$result : $result;
            });
        }

        return $items;
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
            $versionId = (int)($item['id'] ?? 0);
            $rows[] = [
                'id' => $versionId,
                'columns' => [
                    'NAME' => $this->renderNameCell($item),
                    'FORM' => $this->renderFormCell($item),
                    'TYPE' => $this->renderTypeCell($item),
                    'STATUS' => $this->renderStatusCell($item),
                    'CREATED_AT' => htmlspecialcharsbx((string)($item['createdAt'] ?? '—')),
                    'ACTIONS' => $this->renderActionsCell($item),
                ],
                'actions' => $this->buildRowActions($item),
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
        $formId = (int)($item['formId'] ?? 0);
        $editorUrl = $this->buildEditorUrl($formId);

        return '<div class="rf-grid-cell">'
            . '<a class="rf-grid-cell__name" href="' . htmlspecialcharsbx($editorUrl) . '" onclick="window.RexpFormVersionsGrid.openSidePanel(\'' . CUtil::JSEscape($editorUrl) . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_024') . $formId . '</a>'
            . '</div>';
    }

    /**
     * Формирует HTML-ячейку названия.
     *
     * @param array $item
     *
     * @return string
     */
    private function renderNameCell(array $item): string
    {
        $name = htmlspecialcharsbx((string)($item['name'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_025')));
        $code = htmlspecialcharsbx((string)($item['code'] ?? ''));
        $id = (int)($item['id'] ?? 0);
        return '<div class="rf-grid-cell">'
            . '<div class="rf-grid-cell__name">' . $name . ' <span class="rf-grid-cell__meta">#' . $id . '</span></div>'
            . '<div class="rf-grid-cell__meta">' . $code . '</div>'
            . '</div>';
    }

    /**
     * Формирует HTML-представление типа ячейки.
     *
     * @param array $item
     *
     * @return string
     */
    private function renderTypeCell(array $item): string
    {
        $type = (string)($item['type'] ?? 'save');
        $map = [
            'save' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_026'),
            'publish' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_027'),
            'restore' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_028'),
        ];
        $label = $map[$type] ?? $type;
        return '<span class="rf-badge rf-badge--info">' . htmlspecialcharsbx($label) . '</span>';
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
        $status = (string)($item['status'] ?? 'draft');
        $label = $status === 'published' ? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_029') : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_030');
        $class = $status === 'published' ? 'rf-badge--success' : 'rf-badge--warning';
        $author = (int)($item['createdBy'] ?? 0);
        return '<div class="rf-grid-cell">'
            . '<span class="rf-badge ' . $class . '">' . htmlspecialcharsbx($label) . '</span>'
            . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_031') . $author . '</div>'
            . '</div>';
    }

    /**
     * Формирует HTML-ячейку действий.
     *
     * @param array $item
     *
     * @return string
     */
    private function renderActionsCell(array $item): string
    {
        $permissions = $this->readService()->getPermissions();
        $buttons = [];
        $versionId = (int)($item['id'] ?? 0);
        $formId = (int)($item['formId'] ?? 0);
        $buttons[] = '<button class="ui-btn ui-btn-xs ui-btn-light-border" onclick="window.RexpFormVersionsGrid.compareVersion(' . $versionId . ', ' . $formId . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_032');

        if (!empty($permissions['canManage'])) {
            $buttons[] = '<button class="ui-btn ui-btn-xs ui-btn-light-border" onclick="window.RexpFormVersionsGrid.restoreVersion(' . $versionId . ', ' . $formId . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_033');
        }

        return '<div class="rf-grid-actions">' . implode('', $buttons) . '</div>';
    }

    /**
     * Формирует действия строки грида.
     *
     * @param array $item
     *
     * @return array
     */
    private function buildRowActions(array $item): array
    {
        $permissions = $this->readService()->getPermissions();
        $versionId = (int)($item['id'] ?? 0);
        $formId = (int)($item['formId'] ?? 0);
        $actions = [
            [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_034'),
                'onclick' => 'window.RexpFormVersionsGrid.compareVersion(' . $versionId . ', ' . $formId . ');',
            ],
        ];

        if (!empty($permissions['canManage'])) {
            $actions[] = [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_VERSIONS_CLASS_035'),
                'onclick' => 'window.RexpFormVersionsGrid.restoreVersion(' . $versionId . ', ' . $formId . ');',
            ];
        }

        return $actions;
    }

    /**
     * Формирует URL редактора формы.
     *
     * @param int $formId
     *
     * @return string
     */
    private function buildEditorUrl(int $formId): string
    {
        $url = $this->arParams['EDITOR_URL'];
        return $formId > 0 ? $url . '?FORM_ID=' . $formId : $url;
    }

    /**
     * Формирует URL страницы черновиков формы.
     *
     * @param int $formId
     *
     * @return string
     */
    private function buildDraftsUrl(int $formId): string
    {
        $url = '/forms/designer/drafts.php';
        return $formId > 0 ? $url . '?FORM_ID=' . $formId : $url;
    }

    /**
     * Возвращает current формы ID from request.
     *
     * @return int
     */
    private function getCurrentFormIdFromRequest(): int
    {
        return (int)($_REQUEST['FORM_ID'] ?? $_REQUEST['id'] ?? $this->arParams['FORM_ID']);
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
     * Возвращает сервис версий форм.
     *
     * @return FormVersionService
     */
    private function versionService(): FormVersionService
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.version');
    }


    /**
     * Возвращает сервис проверки прав.
     *
     * @return mixed
     */
    private function permissionService()
    {
        return \Bitrix\Main\DI\ServiceLocator::getInstance()->get('rexp.form.service.permission');
    }
}
