<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис получения справочных данных для публичной формы.
 */
final class RuntimeDirectoryService
{
    /**
     * Возвращает сотрудника элементов.
     *
     * @return array
     */
    public function getEmployeeItems(): array
    {
        if (!Loader::includeModule('intranet')) {
            return [];
        }

        $departmentMap = [];
        foreach ($this->getDepartmentItems() as $departmentItem) {
            $departmentId = (string)($departmentItem['id'] ?? '');
            if ($departmentId !== '') {
                $departmentMap[$departmentId] = [
                    'label' => (string)($departmentItem['label'] ?? ''),
                    'path' => (string)($departmentItem['pathLabel'] ?? ''),
                ];
            }
        }

        $user = new \CUser();
        $dbUsers = $user->GetList(
            $by = 'last_name',
            $order = 'asc',
            ['ACTIVE' => 'Y'],
            ['SELECT' => ['UF_DEPARTMENT', 'WORK_POSITION', 'PERSONAL_PHOTO']]
        );

        $result = [];
        while ($row = $dbUsers->Fetch()) {
            $id = (string)((int)($row['ID'] ?? 0));
            if ($id === '0') {
                continue;
            }

            $parts = array_filter([
                trim((string)($row['LAST_NAME'] ?? '')),
                trim((string)($row['NAME'] ?? '')),
                trim((string)($row['SECOND_NAME'] ?? '')),
            ]);
            $label = trim(implode(' ', $parts));
            if ($label === '') {
                $label = trim((string)($row['LOGIN'] ?? '')) ?: (\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEDIRECTORYSERVICE_001') . $id);
            }

            $departmentIds = [];
            $rawDepartments = $row['UF_DEPARTMENT'] ?? [];
            if (is_array($rawDepartments)) {
                foreach ($rawDepartments as $departmentId) {
                    $normalizedDepartmentId = (string)((int)$departmentId);
                    if ($normalizedDepartmentId !== '0') {
                        $departmentIds[] = $normalizedDepartmentId;
                    }
                }
            } elseif ($rawDepartments !== null && $rawDepartments !== '') {
                $normalizedDepartmentId = (string)((int)$rawDepartments);
                if ($normalizedDepartmentId !== '0') {
                    $departmentIds[] = $normalizedDepartmentId;
                }
            }
            $departmentIds = array_values(array_unique($departmentIds));

            $subtitleParts = [];
            $workPosition = trim((string)($row['WORK_POSITION'] ?? ''));
            if ($workPosition !== '') {
                $subtitleParts[] = $workPosition;
            }
            if (!empty($departmentIds)) {
                $firstDepartment = $departmentMap[$departmentIds[0]] ?? null;
                if (is_array($firstDepartment) && trim((string)($firstDepartment['label'] ?? '')) !== '') {
                    $subtitleParts[] = trim((string)$firstDepartment['label']);
                }
            }

            $avatar = '';
            $personalPhotoId = (int)($row['PERSONAL_PHOTO'] ?? 0);
            if ($personalPhotoId > 0) {
                $avatar = (string)\CFile::GetPath($personalPhotoId);
            }

            $result[] = [
                'value' => $id,
                'id' => $id,
                'entityId' => 'user',
                'label' => $label,
                'title' => $label,
                'subtitle' => trim(implode(' · ', $subtitleParts)),
                'avatar' => $avatar,
                'departments' => $departmentIds,
            ];
        }

        return $result;
    }

    /**
     * Возвращает отдела элементов.
     *
     * @return array
     */
    public function getDepartmentItems(): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $iblockId = (int)Option::get('intranet', 'iblock_structure', 0);
        if ($iblockId <= 0) {
            return [];
        }

        $rows = [];
        $namePath = [];
        $dbResult = \CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC', 'SORT' => 'ASC', 'ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
            false,
            ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID']
        );
        while ($row = $dbResult->GetNext()) {
            $id = (string)((int)($row['ID'] ?? 0));
            if ($id === '0') {
                continue;
            }

            $parentId = (string)((int)($row['IBLOCK_SECTION_ID'] ?? 0));
            $label = trim((string)($row['NAME'] ?? '')) ?: (\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEDIRECTORYSERVICE_002') . $id);
            $depthLevel = max(1, (int)($row['DEPTH_LEVEL'] ?? 1));
            $pathParts = [];
            if ($parentId !== '0' && isset($namePath[$parentId]) && is_array($namePath[$parentId])) {
                $pathParts = $namePath[$parentId];
            }
            $pathParts[] = $label;
            $namePath[$id] = $pathParts;

            $rows[] = [
                'value' => $id,
                'id' => $id,
                'entityId' => 'department',
                'label' => $label,
                'title' => $label,
                'subtitle' => $depthLevel > 1 ? trim(implode(' → ', array_slice($pathParts, 0, -1))) : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEDIRECTORYSERVICE_003'),
                'depth' => $depthLevel,
                'parentId' => $parentId !== '0' ? $parentId : '',
                'pathLabel' => trim(implode(' → ', $pathParts)),
            ];
        }

        return $rows;
    }
}
