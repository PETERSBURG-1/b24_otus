<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\AccessRights\DataProvider;
use Rexp\Form\Access\Permission\FormPermissionTable;
use Rexp\Form\Access\Permission\PermissionDictionary;
use Rexp\Form\Access\Role\FormRoleRelationTable;
use Rexp\Form\Access\Role\FormRoleTable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис подготовки и сохранения настроек прав конструктора.
 */
final class FormSettingsService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormPermissionService $permissionService
     */
    public function __construct(
        private readonly FormPermissionService $permissionService,
    ) {
    }

    /**
     * Возвращает настроек.
     *
     * @return array
     */
    public function getSettings(): array
    {
        return [
            'roles' => $this->getRoles(),
            'permissions' => $this->getPermissionItems(),
            'availableGroups' => $this->getAvailableGroups(),
            'USER_GROUPS' => $this->getUserGroups(),
            'ACCESS_RIGHTS' => $this->getAccessRights(),
        ];
    }

    /**
     * Возвращает access rights настроек.
     *
     * @return array
     */
    public function getAccessRightsSettings(): array
    {
        $this->permissionService->assertCanManageSettings();

        return [
            'USER_GROUPS' => $this->getUserGroups(),
            'ACCESS_RIGHTS' => $this->getAccessRights(),
        ];
    }

    /**
     * Сохраняет настроек.
     *
     * @param array $settings
     *
     * @return array
     */
    public function saveSettings(array $settings): array
    {
        $this->permissionService->assertCanManageSettings();

        $roles = is_array($settings['roles'] ?? null) ? $settings['roles'] : [];
        foreach ($roles as $role) {
            if (!is_array($role)) {
                continue;
            }

            $roleId = (int)($role['id'] ?? 0);
            if ($roleId <= 0 || !FormRoleTable::getById($roleId)->fetch()) {
                continue;
            }

            $name = trim((string)($role['name'] ?? ''));
            if ($name !== '') {
                FormRoleTable::update($roleId, [
                    'NAME' => $name,
                    'UPDATED_AT' => new DateTime(),
                ]);
            }

            $this->saveRoleRelations($roleId, is_array($role['accessCodes'] ?? null) ? $role['accessCodes'] : []);
            $this->saveRolePermissions($roleId, is_array($role['permissions'] ?? null) ? $role['permissions'] : []);
        }

        return $this->getSettings();
    }

    /**
     * Сохраняет access rights.
     *
     * @param array $userGroups
     *
     * @return void
     */
    public function saveAccessRights(array $userGroups): void
    {
        $this->permissionService->assertCanManageSettings();

        foreach ($userGroups as $roleSettings) {
            if (!is_array($roleSettings)) {
                continue;
            }

            $roleId = $this->extractRoleId($roleSettings);
            $title = trim((string)($roleSettings['title'] ?? $roleSettings['name'] ?? ''));

            if ($roleId <= 0) {
                $roleId = $this->createRole($title !== '' ? $title : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSETTINGSSERVICE_001'));
            }

            $row = FormRoleTable::getById($roleId)->fetch();
            if (!$row) {
                continue;
            }

            if ($title !== '') {
                FormRoleTable::update($roleId, [
                    'NAME' => $title,
                    'UPDATED_AT' => new DateTime(),
                ]);
            }

            $this->saveRoleRelations($roleId, $this->extractAccessCodesFromRoleSettings($roleSettings));
            $this->saveRolePermissions($roleId, $this->extractPermissionIdsFromRoleSettings($roleSettings));
        }
    }

    /**
     * Удаляет role.
     *
     * @param int $roleId
     *
     * @return void
     */
    public function deleteRole(int $roleId): void
    {
        $this->permissionService->assertCanManageSettings();

        $role = FormRoleTable::getById($roleId)->fetch();
        if (!$role || (string)$role['IS_SYSTEM'] === 'Y') {
            return;
        }

        $this->deleteByRole(FormRoleRelationTable::class, $roleId);
        $this->deleteByRole(FormPermissionTable::class, $roleId);
        FormRoleTable::delete($roleId);
    }

    /**
     * Возвращает access rights.
     *
     * @return array
     */
    public function getAccessRights(): array
    {
        $sections = [
            \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSETTINGSSERVICE_002') => [
                PermissionDictionary::SECTION_VIEW,
            ],
            \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSETTINGSSERVICE_003') => [
                PermissionDictionary::FORM_READ,
                PermissionDictionary::FORM_EDIT,
                PermissionDictionary::FORM_PUBLISH,
                PermissionDictionary::FORM_DELETE,
                PermissionDictionary::PUBLIC_USE,
            ],
            \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSETTINGSSERVICE_004') => [
                PermissionDictionary::SUBMISSION_READ,
                PermissionDictionary::BIZPROC_MANAGE,
            ],
            \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSETTINGSSERVICE_005') => [
                PermissionDictionary::SETTINGS_MANAGE,
            ],
        ];

        $labels = PermissionDictionary::getLabels();
        $result = [];
        foreach ($sections as $sectionTitle => $permissions) {
            $rights = [];
            foreach ($permissions as $permissionId) {
                $rights[] = [
                    'id' => $permissionId,
                    'type' => 'toggler',
                    'title' => $labels[$permissionId] ?? $permissionId,
                    'hint' => null,
                ];
            }

            $result[] = [
                'sectionTitle' => $sectionTitle,
                'rights' => $rights,
            ];
        }

        return $result;
    }

    /**
     * Возвращает пользователя groups.
     *
     * @return array
     */
    public function getUserGroups(): array
    {
        $roles = [];
        foreach ($this->getRoles() as $role) {
            $accessRights = [];
            foreach ((array)($role['permissions'] ?? []) as $permissionId) {
                $accessRights[] = [
                    'id' => (string)$permissionId,
                    'value' => 1,
                ];
            }

            $members = [];
            foreach ((array)($role['accessCodes'] ?? []) as $accessCode) {
                $members[$accessCode] = $this->getMemberInfo($accessCode);
            }

            $roles[] = [
                'id' => (int)$role['id'],
                'title' => (string)$role['name'],
                'accessRights' => $accessRights,
                'members' => $members,
                // Дублируем список кодов: BX.UI.AccessRights работает с members,
                // а старый/резервный формат настроек использует accessCodes.
                'accessCodes' => array_values((array)($role['accessCodes'] ?? [])),
            ];
        }

        return $roles;
    }

    /**
     * Возвращает roles.
     *
     * @return array
     */
    private function getRoles(): array
    {
        $roles = [];
        $rows = FormRoleTable::getList([
            'select' => ['ID', 'CODE', 'NAME', 'IS_SYSTEM', 'SORT'],
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ]);
        while ($row = $rows->fetch()) {
            $roleId = (int)$row['ID'];
            $roles[$roleId] = [
                'id' => $roleId,
                'code' => (string)$row['CODE'],
                'name' => (string)$row['NAME'],
                'isSystem' => (string)$row['IS_SYSTEM'] === 'Y',
                'sort' => (int)$row['SORT'],
                'accessCodes' => [],
                'permissions' => [],
            ];
        }

        if ($roles === []) {
            return [];
        }

        $relations = FormRoleRelationTable::getList([
            'select' => ['ROLE_ID', 'ACCESS_CODE'],
            'filter' => ['@ROLE_ID' => array_keys($roles)],
            'order' => ['ID' => 'ASC'],
        ]);
        while ($relation = $relations->fetch()) {
            $roleId = (int)$relation['ROLE_ID'];
            if (isset($roles[$roleId])) {
                $roles[$roleId]['accessCodes'][] = (string)$relation['ACCESS_CODE'];
            }
        }

        $permissions = FormPermissionTable::getList([
            'select' => ['ROLE_ID', 'PERMISSION_ID', 'VALUE'],
            'filter' => ['@ROLE_ID' => array_keys($roles), '=VALUE' => 'Y'],
            'order' => ['ID' => 'ASC'],
        ]);
        while ($permission = $permissions->fetch()) {
            $roleId = (int)$permission['ROLE_ID'];
            if (isset($roles[$roleId])) {
                $roles[$roleId]['permissions'][] = (string)$permission['PERMISSION_ID'];
            }
        }

        return array_values($roles);
    }

    /**
     * Возвращает права элементов.
     *
     * @return array
     */
    private function getPermissionItems(): array
    {
        $items = [];
        foreach (PermissionDictionary::getLabels() as $id => $name) {
            $items[] = ['id' => $id, 'name' => $name];
        }

        return $items;
    }

    /**
     * Создаёт role.
     *
     * @param string $title
     *
     * @return int
     */
    private function createRole(string $title): int
    {
        $code = 'custom_' . uniqid('', false);
        $result = FormRoleTable::add([
            'CODE' => $code,
            'NAME' => $title,
            'IS_SYSTEM' => 'N',
            'SORT' => 100,
        ]);

        return $result->isSuccess() ? (int)$result->getId() : 0;
    }

    /**
     * Сохраняет role relations.
     *
     * @param int $roleId
     * @param array $accessCodes
     *
     * @return void
     */
    private function saveRoleRelations(int $roleId, array $accessCodes): void
    {
        $this->deleteByRole(FormRoleRelationTable::class, $roleId);

        foreach ($this->normalizeAccessCodes($accessCodes) as $accessCode) {
            FormRoleRelationTable::add([
                'ROLE_ID' => $roleId,
                'ACCESS_CODE' => $accessCode,
            ]);
        }
    }

    /**
     * Сохраняет role прав.
     *
     * @param int $roleId
     * @param array $permissions
     *
     * @return void
     */
    private function saveRolePermissions(int $roleId, array $permissions): void
    {
        $this->deleteByRole(FormPermissionTable::class, $roleId);
        $allowed = array_flip(PermissionDictionary::getAll());

        foreach ($permissions as $permissionId) {
            $permissionId = trim((string)$permissionId);
            if ($permissionId === '' || !isset($allowed[$permissionId])) {
                continue;
            }

            FormPermissionTable::add([
                'ROLE_ID' => $roleId,
                'PERMISSION_ID' => $permissionId,
                'VALUE' => 'Y',
            ]);
        }
    }

    /**
     * Извлекает access codes from role настроек.
     *
     * @param array $roleSettings
     *
     * @return array
     */
    private function extractAccessCodesFromRoleSettings(array $roleSettings): array
    {
        $accessCodes = [];

        $this->collectAccessCodes($roleSettings['accessCodes'] ?? null, $accessCodes);
        $this->collectAccessCodes($roleSettings['access_codes'] ?? null, $accessCodes);
        $this->collectAccessCodes($roleSettings['accessCode'] ?? null, $accessCodes);
        $this->collectAccessCodes($roleSettings['access_code'] ?? null, $accessCodes);

        if (is_array($roleSettings['members'] ?? null)) {
            foreach ($roleSettings['members'] as $memberKey => $member) {
                if (is_string($memberKey) && $this->isValidAccessCode($memberKey)) {
                    $accessCodes[] = $memberKey;
                }

                $this->collectAccessCodes($member, $accessCodes);
            }
        }

        return $this->normalizeAccessCodes($accessCodes);
    }

    /**
     * Извлекает role ID.
     *
     * @param array $roleSettings
     *
     * @return int
     */
    private function extractRoleId(array $roleSettings): int
    {
        foreach (['id', 'roleId', 'role_id', 'userGroupId', 'user_group_id'] as $fieldName) {
            $value = $roleSettings[$fieldName] ?? null;
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                return (int)$value;
            }
        }

        return 0;
    }

    /**
     * Собирает access-коды пользователей и групп из данных роли.
     *
     * @param mixed $value
     * @param array $accessCodes
     *
     * @return void
     */
    private function collectAccessCodes(mixed $value, array &$accessCodes): void
    {
        if (is_string($value) || is_int($value)) {
            $accessCode = $this->normalizeAccessCodeValue((string)$value);
            if ($accessCode !== '') {
                $accessCodes[] = $accessCode;
            }

            return;
        }

        if (!is_array($value)) {
            return;
        }

        $memberAccessCode = $this->getAccessCodeFromMember($value);
        if ($memberAccessCode !== '') {
            $accessCodes[] = $memberAccessCode;
            return;
        }

        foreach ($value as $itemKey => $item) {
            if (is_string($itemKey)) {
                $accessCode = $this->normalizeAccessCodeValue($itemKey);
                if ($accessCode !== '') {
                    $accessCodes[] = $accessCode;
                }
            }

            $this->collectAccessCodes($item, $accessCodes);
        }
    }

    /**
     * Извлекает права ids from role настроек.
     *
     * @param array $roleSettings
     *
     * @return array
     */
    private function extractPermissionIdsFromRoleSettings(array $roleSettings): array
    {
        if (is_array($roleSettings['permissions'] ?? null)) {
            return array_values(array_map('strval', $roleSettings['permissions']));
        }

        $permissions = [];
        foreach ((array)($roleSettings['accessRights'] ?? []) as $rightKey => $right) {
            if (is_array($right)) {
                $permissionId = trim((string)($right['id'] ?? $right['permissionId'] ?? $rightKey));
                if ($permissionId !== '' && $this->isPermissionEnabled($right['value'] ?? $right['selected'] ?? null)) {
                    $permissions[] = $permissionId;
                }
                continue;
            }

            $permissionId = is_string($rightKey) ? trim($rightKey) : trim((string)$right);
            if ($permissionId === '') {
                continue;
            }

            if (is_string($rightKey) && !$this->isPermissionEnabled($right)) {
                continue;
            }

            $permissions[] = $permissionId;
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Возвращает access кода from member.
     *
     * @param mixed $member
     *
     * @return string
     */
    private function getAccessCodeFromMember(mixed $member): string
    {
        if (is_string($member) || is_int($member)) {
            return $this->normalizeAccessCodeValue((string)$member);
        }

        if (!is_array($member)) {
            return '';
        }

        foreach (['accessCode', 'access_code', 'code', 'entityCode', 'entity_code'] as $fieldName) {
            $value = trim((string)($member[$fieldName] ?? ''));
            $accessCode = $this->normalizeAccessCodeValue($value);
            if ($accessCode !== '') {
                return $accessCode;
            }
        }

        $id = trim((string)($member['id'] ?? ''));
        $accessCode = $this->normalizeAccessCodeValue($id);
        if ($accessCode !== '') {
            return $accessCode;
        }

        if ($id === '' || !ctype_digit($id)) {
            return '';
        }

        $type = mb_strtolower(trim((string)(
            $member['type']
            ?? $member['entityType']
            ?? $member['entity_type']
            ?? $member['entityId']
            ?? $member['entity_id']
            ?? ''
        )));

        return match ($type) {
            'users', 'user', 'employee', 'employees' => 'U' . $id,
            'departments', 'department', 'department_head', 'departmenthead' => 'D' . $id,
            'sonetgroups', 'sonetgroup', 'socialnetwork', 'project', 'projects' => 'SG' . $id,
            'groups', 'group', 'usergroups', 'usergroup', 'user_group', 'user-groups' => 'G' . $id,
            default => '',
        };
    }

    /**
     * Удаляет по role.
     *
     * @param string $tableClass
     * @param int $roleId
     *
     * @return void
     */
    private function deleteByRole(string $tableClass, int $roleId): void
    {
        $rows = $tableClass::getList([
            'select' => ['ID'],
            'filter' => ['=ROLE_ID' => $roleId],
        ]);
        while ($row = $rows->fetch()) {
            $tableClass::delete((int)$row['ID']);
        }
    }

    /**
     * Нормализует access codes.
     *
     * @param array $accessCodes
     *
     * @return array
     */
    private function normalizeAccessCodes(array $accessCodes): array
    {
        $result = [];
        foreach ($accessCodes as $accessCode) {
            $accessCode = $this->normalizeAccessCodeValue((string)$accessCode);
            if ($accessCode !== '') {
                $result[$accessCode] = $accessCode;
            }
        }

        return array_values($result);
    }

    /**
     * Нормализует access кода значения.
     *
     * @param string $accessCode
     *
     * @return string
     */
    private function normalizeAccessCodeValue(string $accessCode): string
    {
        $accessCode = trim($accessCode);
        if ($accessCode === '') {
            return '';
        }

        if (preg_match('~^([UGD]|DR|SG)[_-]?(\d+)$~i', $accessCode, $matches) === 1) {
            $prefix = mb_strtoupper($matches[1]);
            return $prefix . (int)$matches[2];
        }

        if (mb_strtoupper($accessCode) === 'AU') {
            return 'AU';
        }

        return $this->isValidAccessCode($accessCode) ? $accessCode : '';
    }

    /**
     * Проверяет признак valid access кода.
     *
     * @param string $accessCode
     *
     * @return bool
     */
    private function isValidAccessCode(string $accessCode): bool
    {
        return $accessCode === 'AU'
            || preg_match('~^(U|G|D|DR|SG)\\d+$~', $accessCode) === 1;
    }

    /**
     * Проверяет признак права enabled.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function isPermissionEnabled(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value > 0;
        }

        return in_array(mb_strtolower(trim((string)$value)), ['1', 'y', 'yes', 'true', 'on'], true);
    }

    /**
     * Возвращает member info.
     *
     * @param string $accessCode
     *
     * @return array
     */
    private function getMemberInfo(string $accessCode): array
    {
        if ($accessCode === 'AU') {
            return [
                'type' => 'groups',
                'id' => 'AU',
                'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSETTINGSSERVICE_006'),
                'url' => '',
                'avatar' => null,
            ];
        }

        if (preg_match('/^G(\d+)$/', $accessCode, $matches)) {
            $group = \CGroup::GetByID((int)$matches[1])->Fetch();
            if ($group) {
                return [
                    'type' => 'groups',
                    'id' => $accessCode,
                    'name' => (string)$group['NAME'],
                    'url' => '',
                    'avatar' => null,
                ];
            }
        }

        try {
            $code = new AccessCode($accessCode);
            $entity = (new DataProvider())->getEntity($code->getEntityType(), $code->getEntityId());

            return $entity->getMetaData();
        } catch (\Throwable) {
            return [
                'type' => 'other',
                'id' => $accessCode,
                'name' => $accessCode,
                'url' => '',
                'avatar' => null,
            ];
        }
    }

    /**
     * Возвращает available groups.
     *
     * @return array
     */
    private function getAvailableGroups(): array
    {
        $items = [[
            'id' => 0,
            'accessCode' => 'AU',
            'name' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSETTINGSSERVICE_007'),
            'stringId' => 'AU',
            'active' => true,
        ]];
        $by = 'c_sort';
        $order = 'asc';
        $groups = \CGroup::GetList($by, $order, ['ACTIVE' => 'Y']);
        while ($group = $groups->Fetch()) {
            $items[] = [
                'id' => (int)$group['ID'],
                'accessCode' => 'G' . (int)$group['ID'],
                'name' => (string)$group['NAME'],
                'stringId' => (string)($group['STRING_ID'] ?? ''),
                'active' => (string)($group['ACTIVE'] ?? 'Y') === 'Y',
            ];
        }

        return $items;
    }
}
