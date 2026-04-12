<?php

use Bitrix\Main\Grid\Options;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Otus\Main\Model\CrmEntityDataTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

/**
 * Компонент вывода данных внешней таблицы в GRID.
 */
class OtusMainCrmEntityDataGridComponent extends CBitrixComponent
{
    /**
     * Идентификатор модуля.
     */
    protected const MODULE_ID = 'otus.main';

    /**
     * Подготавливает параметры компонента.
     *
     * @param array $arParams Параметры компонента.
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams)
    {
        $arParams['ENTITY_TYPE_ID'] = (int)($arParams['ENTITY_TYPE_ID'] ?? 0);
        $arParams['ENTITY_ID'] = (int)($arParams['ENTITY_ID'] ?? 0);

        return $arParams;
    }

    /**
     * Выполняет компонент.
     *
     * @return void
     */
    public function executeComponent()
    {
        try {
            $this->loadModules();
            $this->prepareResult();
            $this->includeComponentTemplate();
        } catch (Throwable $exception) {
            ShowError($exception->getMessage());
        }
    }

    /**
     * Подключает необходимые модули.
     *
     * @return void
     * @throws SystemException
     */
    protected function loadModules()
    {
        if (!Loader::includeModule(self::MODULE_ID)) {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_MODULE_NOT_INSTALLED'));
        }
    }

    /**
     * Готовит данные для шаблона.
     *
     * @return void
     */
    protected function prepareResult()
    {
        $gridId = $this->getGridId();
        $gridOptions = new Options($gridId);
        $sorting = $gridOptions->GetSorting([
            'sort' => ['ID' => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);
        $navParams = $gridOptions->GetNavParams(['nPageSize' => 10]);

        $nav = new PageNavigation($gridId);
        $nav->allowAllRecords(true)
            ->setPageSize((int)$navParams['nPageSize'])
            ->initFromUri();

        $filter = [
            '=ENTITY_TYPE_ID' => $this->arParams['ENTITY_TYPE_ID'],
            '=ENTITY_ID' => $this->arParams['ENTITY_ID'],
        ];

        $totalRowsCount = CrmEntityDataTable::getCount($filter);
        $nav->setRecordCount($totalRowsCount);

        $result = CrmEntityDataTable::getList([
            'filter' => $filter,
            'select' => ['ID', 'TITLE', 'VALUE', 'CREATED_AT'],
            'order' => $sorting['sort'],
            'offset' => $nav->getOffset(),
            'limit' => $nav->getLimit(),
        ]);

        $rows = [];
        while ($item = $result->fetch()) {
            $rows[] = [
                'id' => $item['ID'],
                'data' => [
                    'ID' => $item['ID'],
                    'TITLE' => $item['TITLE'],
                    'VALUE' => $item['VALUE'],
                    'CREATED_AT' => $item['CREATED_AT'] instanceof \Bitrix\Main\Type\DateTime
                        ? $item['CREATED_AT']->toString()
                        : '',
                ],
            ];
        }

        $this->arResult['GRID_ID'] = $gridId;
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['ROWS'] = $rows;
        $this->arResult['NAV_OBJECT'] = $nav;
        $this->arResult['TOTAL_ROWS_COUNT'] = $totalRowsCount;
        $this->arResult['PAGE_SIZES'] = [
            ['NAME' => '10', 'VALUE' => '10'],
            ['NAME' => '20', 'VALUE' => '20'],
            ['NAME' => '50', 'VALUE' => '50'],
        ];
    }

    /**
     * Возвращает идентификатор грида.
     *
     * @return string
     */
    protected function getGridId()
    {
        return 'OTUS_CRM_ENTITY_DATA_' . $this->arParams['ENTITY_TYPE_ID'] . '_' . $this->arParams['ENTITY_ID'];
    }

    /**
     * Возвращает описание колонок грида.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            ['id' => 'ID', 'name' => Loc::getMessage('OTUS_MAIN_GRID_COLUMN_ID'), 'sort' => 'ID', 'default' => true],
            ['id' => 'TITLE', 'name' => Loc::getMessage('OTUS_MAIN_GRID_COLUMN_TITLE'), 'sort' => 'TITLE', 'default' => true],
            ['id' => 'VALUE', 'name' => Loc::getMessage('OTUS_MAIN_GRID_COLUMN_VALUE'), 'sort' => 'VALUE', 'default' => true],
            ['id' => 'CREATED_AT', 'name' => Loc::getMessage('OTUS_MAIN_GRID_COLUMN_CREATED_AT'), 'sort' => 'CREATED_AT', 'default' => true],
        ];
    }
}
