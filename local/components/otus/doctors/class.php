<?php

use App\Models\DoctorsPropertyValuesTable;
use App\Models\ProceduresPropertyValuesTable;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * Компонент otus:doctors.
 *
 * Отображает список врачей и детальную страницу врача с процедурами,
 */
class OtusDoctorsComponent extends CBitrixComponent implements Controllerable
{
    protected $request;

    /**
     * Подготавливает параметры компонента.
     *
     * @param array $arParams
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['IBLOCK_DOCTORS_ID'] = (int)($arParams['IBLOCK_DOCTORS_ID'] ?? DoctorsPropertyValuesTable::IBLOCK_ID);
        $arParams['IBLOCK_PROCEDURES_ID'] = (int)($arParams['IBLOCK_PROCEDURES_ID'] ?? ProceduresPropertyValuesTable::IBLOCK_ID);
        $arParams['PROP_PROCEDURES_CODE'] = (string)($arParams['PROP_PROCEDURES_CODE'] ?? 'PROCEDURES');

        $arParams['SEF_MODE'] = (($arParams['SEF_MODE'] ?? 'Y') === 'Y') ? 'Y' : 'N';
        $arParams['SEF_FOLDER'] = (string)($arParams['SEF_FOLDER'] ?? '/doctors/');
        $arParams['SEF_URL_TEMPLATES'] = is_array($arParams['SEF_URL_TEMPLATES'] ?? null)
            ? $arParams['SEF_URL_TEMPLATES']
            : [];

        return $arParams;
    }

    /**
     * Возвращает конфигурацию ajax-действий компонента.
     *
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'addDoctor' => ['prefilters' => [new ActionFilter\Csrf()]],
            'addProcedure' => ['prefilters' => [new ActionFilter\Csrf()]],
            'updateDoctor' => ['prefilters' => [new ActionFilter\Csrf()]],
        ];
    }

    /**
     * Выполняет компонент.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        $this->request = Context::getCurrent()->getRequest();
        Loc::loadMessages(__FILE__);

        if (!Loader::includeModule('iblock')) {
            ShowError(Loc::getMessage('OTUS_DOCTORS_ERR_NO_IBLOCK'));
            return;
        }

        $this->arResult = [
            'ERRORS' => [],
            'COMPONENT_PAGE' => 'list',
            'VARIABLES' => [],
            'FOLDER' => $this->arParams['SEF_FOLDER'],
            'URL_TEMPLATES' => ['list' => '', 'detail' => '#ID#/'],
        ];

        $this->initSef();
        $this->arResult['SIGNED_PARAMS'] = $this->getSignedParameters();
        $this->arResult['PROCEDURES_ALL'] = $this->getProceduresList();

        if ($this->arResult['COMPONENT_PAGE'] === 'detail') {
            $id = (int)($this->arResult['VARIABLES']['ID'] ?? 0);
            $doctor = $this->getDoctorById($id);

            if ($doctor === null) {
                $this->arResult['ERRORS'][] = Loc::getMessage('OTUS_DOCTORS_ERR_NOT_FOUND');
            } else {
                $procedures = $this->getDoctorProcedures($doctor['ID']);
                $this->arResult['DOCTOR'] = $doctor;
                $this->arResult['DOCTOR_PROCEDURES'] = $procedures;
                $this->arResult['DOCTOR_PROC_IDS'] = array_values(array_map(static fn(array $procedure): int => (int)$procedure['ID'], $procedures));
            }
        } else {
            $this->arResult['DOCTORS'] = $this->getDoctorsList();
        }

        $this->includeComponentTemplate();
    }

    /**
     * Инициализирует ЧПУ-режим компонента.
     *
     * @return void
     */
    protected function initSef(): void
    {
        $defaultTemplates = ['list' => '', 'detail' => '#ID#/'];

        if ($this->arParams['SEF_MODE'] === 'Y') {
            $urlTemplates = CComponentEngine::makeComponentUrlTemplates($defaultTemplates, $this->arParams['SEF_URL_TEMPLATES']);
            $vars = [];

            $page = CComponentEngine::parseComponentPath($this->arParams['SEF_FOLDER'], $urlTemplates, $vars);
            if ($page === '' || $page === false) {
                $page = 'list';
            }

            CComponentEngine::initComponentVariables($page, ['ID'], [], $vars);

            $this->arResult['COMPONENT_PAGE'] = $page;
            $this->arResult['VARIABLES'] = $vars;
            $this->arResult['URL_TEMPLATES'] = $urlTemplates;
        } else {
            $id = (int)$this->request->get('id');
            if ($id > 0) {
                $this->arResult['COMPONENT_PAGE'] = 'detail';
                $this->arResult['VARIABLES'] = ['ID' => $id];
            }
        }
    }

    /**
     * Возвращает активных врачей.
     *
     * @return array<int, array{ID:int, NAME:string}>
     */
    protected function getDoctorsList(): array
    {
        return DoctorsPropertyValuesTable::getActiveElementsList();
    }

    /**
     * Возвращает врача по идентификатору.
     *
     * @param int $doctorId
     *
     * @return array{ID:int, NAME:string}|null
     */
    protected function getDoctorById(int $doctorId): ?array
    {
        return DoctorsPropertyValuesTable::getElementById($doctorId);
    }

    /**
     * Возвращает активные процедуры.
     *
     * @return array<int, array{ID:int, NAME:string}>
     */
    protected function getProceduresList(): array
    {
        return ProceduresPropertyValuesTable::getActiveElementsList();
    }

    /**
     * Возвращает процедуры врача.
     *
     * @param int $doctorId
     *
     * @return array<int, array{ID:int, NAME:string}>
     */
    protected function getDoctorProcedures(int $doctorId): array
    {
        if ($doctorId <= 0) {
            return [];
        }

        $row = DoctorsPropertyValuesTable::getList([
            'select' => [
                'IBLOCK_ELEMENT_ID',
                $this->arParams['PROP_PROCEDURES_CODE'],
            ],
            'filter' => [
                '=IBLOCK_ELEMENT_ID' => $doctorId,
                '=ELEMENT.ACTIVE' => 'Y',
            ],
            'limit' => 1,
        ])->fetch();

        $procedureIds = array_values(array_unique(array_map('intval', (array)($row[$this->arParams['PROP_PROCEDURES_CODE']] ?? []))));

        return $this->getProceduresByIds($procedureIds);
    }

    /**
     * Возвращает активные процедуры по списку идентификаторов.
     *
     * @param array $procedureIds
     *
     * @return array<int, array{ID:int, NAME:string}>
     */
    protected function getProceduresByIds(array $procedureIds): array
    {
        $procedureIds = array_values(array_unique(array_filter(array_map('intval', $procedureIds))));
        if (empty($procedureIds)) {
            return [];
        }

        $rows = ProceduresPropertyValuesTable::getList([
            'select' => [
                'ID' => 'IBLOCK_ELEMENT_ID',
                'NAME' => 'ELEMENT.NAME',
            ],
            'filter' => [
                '=IBLOCK_ELEMENT_ID' => $procedureIds,
                '=ELEMENT.ACTIVE' => 'Y',
            ],
            'order' => [
                'ELEMENT.NAME' => 'ASC',
            ],
        ])->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'ID' => (int)$row['ID'],
                'NAME' => (string)$row['NAME'],
            ];
        }

        return $result;
    }

    /**
     * Добавляет врача и сохраняет выбранные процедуры.
     *
     * @param string $name
     * @param array $procedureIds
     *
     * @return array{id:int, url:string}
     */
    public function addDoctorAction(string $name, array $procedureIds = []): array
    {
        Loc::loadMessages(__FILE__);
        Loader::includeModule('iblock');

        $name = trim($name);
        if ($name === '') {
            throw new \Bitrix\Main\SystemException(Loc::getMessage('OTUS_DOCTORS_ERR_EMPTY_NAME'));
        }

        $id = DoctorsPropertyValuesTable::add(['NAME' => $name]);
        if ($id <= 0) {
            throw new \Bitrix\Main\SystemException(Loc::getMessage('OTUS_DOCTORS_ERR_SAVE'));
        }

        $this->setDoctorProcedures($id, $procedureIds);

        return ['id' => $id, 'url' => $this->makeDetailUrl($id)];
    }

    /**
     * Добавляет процедуру.
     *
     * @param string $name
     *
     * @return array{id:int}
     */
    public function addProcedureAction(string $name): array
    {
        Loc::loadMessages(__FILE__);
        Loader::includeModule('iblock');

        $name = trim($name);
        if ($name === '') {
            throw new \Bitrix\Main\SystemException(Loc::getMessage('OTUS_DOCTORS_ERR_EMPTY_NAME'));
        }

        $id = ProceduresPropertyValuesTable::add(['NAME' => $name]);
        if ($id <= 0) {
            throw new \Bitrix\Main\SystemException(Loc::getMessage('OTUS_DOCTORS_ERR_SAVE'));
        }

        return ['id' => $id];
    }

    /**
     * Обновляет врача и его процедуры.
     *
     * @param int $id
     * @param string $name
     * @param array $procedureIds
     *
     * @return array{id:int}
     */
    public function updateDoctorAction(int $id, string $name, array $procedureIds = []): array
    {
        Loc::loadMessages(__FILE__);
        Loader::includeModule('iblock');

        $id = (int)$id;
        $name = trim($name);

        if ($id <= 0) {
            throw new \Bitrix\Main\SystemException(Loc::getMessage('OTUS_DOCTORS_ERR_BAD_ID'));
        }

        if ($name === '') {
            throw new \Bitrix\Main\SystemException(Loc::getMessage('OTUS_DOCTORS_ERR_EMPTY_NAME'));
        }

        if (!DoctorsPropertyValuesTable::updateElementName($id, $name)) {
            throw new \Bitrix\Main\SystemException(Loc::getMessage('OTUS_DOCTORS_ERR_SAVE'));
        }

        $this->setDoctorProcedures($id, $procedureIds);

        return ['id' => $id];
    }

    /**
     * Сохраняет множественное свойство процедур у врача.
     *
     * @param int $doctorId
     * @param array $procedureIds
     *
     * @return void
     */
    protected function setDoctorProcedures(int $doctorId, array $procedureIds): void
    {
        $code = (string)$this->arParams['PROP_PROCEDURES_CODE'];
        $procedureIds = $this->filterExistingProcedureIds($procedureIds);

        CIBlockElement::SetPropertyValuesEx(
            $doctorId,
            $this->arParams['IBLOCK_DOCTORS_ID'],
            [$code => $procedureIds]
        );
    }

    /**
     * Оставляет только существующие активные процедуры.
     *
     * @param array $procedureIds
     *
     * @return array<int, int>
     */
    protected function filterExistingProcedureIds(array $procedureIds): array
    {
        $procedures = $this->getProceduresByIds($procedureIds);

        return array_values(array_map(
            static fn(array $procedure): int => (int)$procedure['ID'],
            $procedures
        ));
    }

    /**
     * Формирует URL детальной страницы врача.
     *
     * @param int $id
     *
     * @return string
     */
    protected function makeDetailUrl(int $id): string
    {
        $folder = rtrim((string)$this->arParams['SEF_FOLDER'], '/') . '/';
        $tpl = (string)($this->arResult['URL_TEMPLATES']['detail'] ?? '#ID#/');

        return $folder . CComponentEngine::makePathFromTemplate($tpl, ['ID' => $id]);
    }
}
