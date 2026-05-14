<?php


use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Rexp\Form\Application\Service\FormDraftService;
use Rexp\Form\Application\Service\FormReadService;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Компонент журнала черновиков форм.
 */
class RexpFormDesignerDraftsComponent extends CBitrixComponent
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
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_001'));
            return;
        }

        $permissions = $this->permissionService()->getPermissions();
        if (empty($permissions['canManage'])) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_002'));
            return;
        }

        $formId = (int)($_REQUEST['FORM_ID'] ?? $_REQUEST['id'] ?? $this->arParams['FORM_ID']);
        $this->currentFormId = $formId;
        $form = $formId > 0 ? $this->readService()->get($formId, $this->getCurrentUserId()) : null;

        $gridId = 'rexp_form_drafts_grid_' . ($formId > 0 ? $formId : '0');
        $filterId = 'rexp_form_drafts_filter_' . ($formId > 0 ? $formId : '0');

        $gridOptions = new GridOptions($gridId);
        $sort = $gridOptions->GetSorting([
            'sort' => ['UPDATED_AT' => 'DESC', 'ID' => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);
        $sortBy = (array)($sort['sort'] ?? ['UPDATED_AT' => 'DESC', 'ID' => 'DESC']);

        $navigation = new PageNavigation($gridId);
        $navigation->allowAllRecords(true)
            ->setPageSize($gridOptions->GetNavParams()['nPageSize'] ?? 20)
            ->initFromUri();

        $items = $this->normalizeItems($formId > 0 ? $this->draftService()->list($formId) : $this->draftService()->listAll());
        $items = $this->applyFilter($items, $filterId);
        $items = $this->sortItems($items, $sortBy);

        $navigation->setRecordCount(count($items));
        $pageItems = array_slice($items, $navigation->getOffset(), $navigation->getLimit());

        $this->arResult = [
            'CONTROLLER' => $this->arParams['CONTROLLER'],
            'EDITOR_URL' => $this->buildEditorUrl($formId),
            'VERSIONS_URL' => $this->buildVersionsUrl($formId),
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
            'CURRENT_USER_ID' => $this->getCurrentUserId(),
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
            $item['AUTHOR_SORT'] = mb_strtolower((string)($item['authorName'] ?? ''));
            $item['FIELDS_COUNT'] = (int)($item['fieldsCount'] ?? 0);
            $item['UPDATED_AT'] = (string)($item['updatedAt'] ?? '');
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
            ['id' => 'FIND', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_003'), 'type' => 'string', 'default' => true],
            ['id' => 'FORM_ID', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_004'), 'type' => 'number'],
            ['id' => 'AUTHOR', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_005'), 'type' => 'string'],
            ['id' => 'AUTHOR_ID', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_006'), 'type' => 'number'],
            ['id' => 'OWNERSHIP', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_007'), 'type' => 'list', 'items' => [
                'mine' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_008'),
                'others' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_009'),
            ]],
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
            'mine' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_010'),
                'default' => true,
                'fields' => ['OWNERSHIP' => 'mine'],
            ],
            'others' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_011'),
                'fields' => ['OWNERSHIP' => 'others'],
            ],
            'all' => [
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_012'),
                'fields' => [],
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
            ['id' => 'AUTHOR', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_013'), 'default' => true, 'sort' => 'AUTHOR_SORT'],
            ['id' => 'FORM', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_014'), 'default' => $this->currentFormId <= 0],
            ['id' => 'CONTENT', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_015'), 'default' => true, 'sort' => 'FIELDS_COUNT'],
            ['id' => 'UPDATED_AT', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_016'), 'default' => true, 'sort' => 'UPDATED_AT'],
            ['id' => 'ACTIONS', 'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_017'), 'default' => true],
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
        $currentUserId = $this->getCurrentUserId();

        return array_values(array_filter($items, static function(array $item) use ($filterData, $currentUserId): bool {
            $find = mb_strtolower(trim((string)($filterData['FIND'] ?? '')));
            if ($find !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    (string)($item['authorName'] ?? ''),
                    (string)($item['authorId'] ?? ''),
                    (string)($item['updatedAt'] ?? ''),
                ]));
                if (!str_contains($haystack, $find)) {
                    return false;
                }
            }

            $author = mb_strtolower(trim((string)($filterData['AUTHOR'] ?? '')));
            if ($author !== '' && !str_contains(mb_strtolower((string)($item['authorName'] ?? '')), $author)) {
                return false;
            }

            $filterFormId = (int)($filterData['FORM_ID'] ?? 0);
            if ($filterFormId > 0 && (int)($item['formId'] ?? 0) !== $filterFormId) {
                return false;
            }

            $authorId = (int)($filterData['AUTHOR_ID'] ?? 0);
            if ($authorId > 0 && (int)($item['authorId'] ?? 0) !== $authorId) {
                return false;
            }

            $ownership = trim((string)($filterData['OWNERSHIP'] ?? ''));
            if ($ownership === 'mine' && (int)($item['authorId'] ?? 0) !== $currentUserId) {
                return false;
            }
            if ($ownership === 'others' && (int)($item['authorId'] ?? 0) === $currentUserId) {
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
            $draftId = (int)($item['id'] ?? 0);
            $rows[] = [
                'id' => $draftId,
                'columns' => [
                    'AUTHOR' => $this->renderAuthorCell($item),
                    'FORM' => $this->renderFormCell($item),
                    'CONTENT' => $this->renderContentCell($item),
                    'UPDATED_AT' => htmlspecialcharsbx((string)($item['updatedAt'] ?? '—')),
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
        $form = is_array($item['form'] ?? null) ? $item['form'] : [];
        $formId = (int)($item['formId'] ?? 0);
        $name = htmlspecialcharsbx((string)($form['name'] ?? (\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_018') . $formId)));
        $code = htmlspecialcharsbx((string)($form['code'] ?? ''));
        $editorUrl = $this->buildEditorUrl($formId);

        return '<div class="rf-grid-cell">'
            . '<a class="rf-grid-cell__name" href="' . htmlspecialcharsbx($editorUrl) . '" onclick="window.RexpFormDraftsGrid.openSidePanel(\'' . CUtil::JSEscape($editorUrl) . '\'); return false;">' . $name . '</a>'
            . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_019') . $formId . ($code !== '' ? ' · ' . $code : '') . '</div>'
            . '</div>';
    }

    /**
     * Формирует HTML-ячейку автора.
     *
     * @param array $item
     *
     * @return string
     */
    private function renderAuthorCell(array $item): string
    {
        $name = htmlspecialcharsbx((string)($item['authorName'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_020')));
        $authorId = (int)($item['authorId'] ?? 0);
        return '<div class="rf-grid-cell">'
            . '<div class="rf-grid-cell__name">' . $name . '</div>'
            . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_021') . $authorId . '</div>'
            . '</div>';
    }

    /**
     * Формирует HTML-ячейку содержимого.
     *
     * @param array $item
     *
     * @return string
     */
    private function renderContentCell(array $item): string
    {
        $fieldsCount = (int)($item['fieldsCount'] ?? 0);
        $sectionsCount = (int)($item['sectionsCount'] ?? 0);
        $isMine = (int)($item['authorId'] ?? 0) === $this->getCurrentUserId();
        $badge = $isMine ? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_022') : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_023');

        return '<div class="rf-grid-cell">'
            . $badge
            . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_024') . $fieldsCount . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_025') . $sectionsCount . '</div>'
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
        $draftId = (int)($item['id'] ?? 0);
        $authorId = (int)($item['authorId'] ?? 0);
        $formId = (int)($item['formId'] ?? 0);

        if (!$this->isEmbed()) {
            $buttons[] = '<button class="ui-btn ui-btn-xs ui-btn-light-border" onclick="window.RexpFormDraftsGrid.openSidePanel(\'' . CUtil::JSEscape($this->buildEditorUrl($formId)) . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_026');
        }

        if (!empty($permissions['canManage']) || $authorId === $this->getCurrentUserId()) {
            $buttons[] = '<button class="ui-btn ui-btn-xs ui-btn-danger" onclick="window.RexpFormDraftsGrid.deleteDraft(' . $draftId . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_027');
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
        $actions = [];
        $draftId = (int)($item['id'] ?? 0);
        $authorId = (int)($item['authorId'] ?? 0);
        $formId = (int)($item['formId'] ?? 0);

        if (!$this->isEmbed()) {
            $actions[] = [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_028'),
                'onclick' => "window.RexpFormDraftsGrid.openSidePanel('" . CUtil::JSEscape($this->buildEditorUrl($formId)) . "');",
            ];
        }

        if (!empty($permissions['canManage']) || $authorId === $this->getCurrentUserId()) {
            $actions[] = [
                'text' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_DRAFTS_CLASS_029'),
                'onclick' => 'window.RexpFormDraftsGrid.deleteDraft(' . $draftId . ');',
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
     * Формирует URL страницы версий формы.
     *
     * @param int $formId
     *
     * @return string
     */
    private function buildVersionsUrl(int $formId): string
    {
        $url = '/forms/designer/versions.php';
        return $formId > 0 ? $url . '?FORM_ID=' . $formId : $url;
    }

    /**
     * Проверяет, открыта ли страница во встроенном режиме.
     *
     * @return bool
     */
    private function isEmbed(): bool
    {
        return (string)$this->arParams['EMBED'] === 'Y';
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
