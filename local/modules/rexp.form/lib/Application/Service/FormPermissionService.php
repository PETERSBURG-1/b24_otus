<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Engine\CurrentUser;
use RuntimeException;
use Rexp\Form\Access\Permission\FormPermissionTable;
use Rexp\Form\Access\Permission\PermissionDictionary;
use Rexp\Form\Access\Role\FormRoleRelationTable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис проверки прав текущего пользователя.
 */
final class FormPermissionService
{
    /** @var array<string,bool>|null */
    private ?array $permissionCache = null;

    /**
     * Проверяет доступ на операцию view.
     *
     * @return bool
     */
    public function canView(): bool
    {
        return $this->can(PermissionDictionary::SECTION_VIEW);
    }

    /**
     * Проверяет доступ на операцию read форм.
     *
     * @return bool
     */
    public function canReadForms(): bool
    {
        return $this->can(PermissionDictionary::FORM_READ) || $this->canManage();
    }

    /**
     * Проверяет доступ на операцию manage.
     *
     * @return bool
     */
    public function canManage(): bool
    {
        return $this->can(PermissionDictionary::FORM_EDIT);
    }

    /**
     * Проверяет доступ на операцию publish.
     *
     * @return bool
     */
    public function canPublish(): bool
    {
        return $this->can(PermissionDictionary::FORM_PUBLISH);
    }

    /**
     * Проверяет доступ на операцию delete.
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        return $this->can(PermissionDictionary::FORM_DELETE);
    }

    /**
     * Проверяет доступ на операцию view отправок.
     *
     * @return bool
     */
    public function canViewSubmissions(): bool
    {
        return $this->can(PermissionDictionary::SUBMISSION_READ);
    }

    /**
     * Проверяет доступ на операцию manage бизнес-процессов.
     *
     * @return bool
     */
    public function canManageBizproc(): bool
    {
        return $this->can(PermissionDictionary::BIZPROC_MANAGE);
    }

    /**
     * Проверяет доступ на операцию use public форм.
     *
     * @return bool
     */
    public function canUsePublicForms(): bool
    {
        return $this->can(PermissionDictionary::PUBLIC_USE);
    }

    /**
     * Проверяет доступ на операцию manage настроек.
     *
     * @return bool
     */
    public function canManageSettings(): bool
    {
        return $this->can(PermissionDictionary::SETTINGS_MANAGE);
    }

    /**
     * Проверяет право доступа по коду действия.
     *
     * @param string $permissionId
     *
     * @return bool
     */
    public function can(string $permissionId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $permissions = $this->loadUserPermissions();
        return !empty($permissions[$permissionId]);
    }

    /**
     * Возвращает набор доступных разрешений текущего пользователя.
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return [
            'canView' => $this->canView(),
            'canReadForms' => $this->canReadForms(),
            'canManage' => $this->canManage(),
            'canPublish' => $this->canPublish(),
            'canDelete' => $this->canDelete(),
            'canViewSubmissions' => $this->canViewSubmissions(),
            'canManageBizproc' => $this->canManageBizproc(),
            'canUsePublicForms' => $this->canUsePublicForms(),
            'canManageSettings' => $this->canManageSettings(),
            'permissionIds' => array_keys(array_filter($this->loadUserPermissions())),
        ];
    }

    /**
     * Проверяет право просмотра и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanView(): void
    {
        if (!$this->canView()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_001'));
        }
    }

    /**
     * Проверяет право чтения форм и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanReadForms(): void
    {
        if (!$this->canReadForms()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_002'));
        }
    }

    /**
     * Проверяет право управления формами и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanManage(): void
    {
        if (!$this->canManage()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_003'));
        }
    }

    /**
     * Проверяет право публикации форм и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanPublish(): void
    {
        if (!$this->canPublish()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_004'));
        }
    }

    /**
     * Проверяет право удаления форм и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanDelete(): void
    {
        if (!$this->canDelete()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_005'));
        }
    }

    /**
     * Проверяет право просмотра журнала отправок и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanViewSubmissions(): void
    {
        if (!$this->canViewSubmissions()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_006'));
        }
    }

    /**
     * Проверяет право управления БП и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanManageBizproc(): void
    {
        if (!$this->canManageBizproc()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_007'));
        }
    }

    /**
     * Проверяет право использования публичных форм и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanUsePublicForms(): void
    {
        if (!$this->canUsePublicForms()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_008'));
        }
    }

    /**
     * Проверяет право управления настройками и выбрасывает исключение при запрете.
     *
     * @return void
     */
    public function assertCanManageSettings(): void
    {
        if (!$this->canManageSettings()) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMPERMISSIONSERVICE_009'));
        }
    }

    /**
     * Загружает пользователя прав.
     *
     * @return array
     */
    private function loadUserPermissions(): array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        $this->permissionCache = [];
        $accessCodes = $this->getCurrentUserAccessCodes();
        if ($accessCodes === []) {
            return $this->permissionCache;
        }

        $roleIds = [];
        $relations = FormRoleRelationTable::getList([
            'select' => ['ROLE_ID'],
            'filter' => ['@ACCESS_CODE' => $accessCodes],
        ]);
        while ($relation = $relations->fetch()) {
            $roleIds[(int)$relation['ROLE_ID']] = (int)$relation['ROLE_ID'];
        }

        if ($roleIds === []) {
            return $this->permissionCache;
        }

        $rows = FormPermissionTable::getList([
            'select' => ['PERMISSION_ID', 'VALUE'],
            'filter' => ['@ROLE_ID' => array_values($roleIds), '=VALUE' => 'Y'],
        ]);
        while ($row = $rows->fetch()) {
            $permissionId = trim((string)($row['PERMISSION_ID'] ?? ''));
            if ($permissionId !== '') {
                $this->permissionCache[$permissionId] = true;
            }
        }

        return $this->permissionCache;
    }

    /**
     * Возвращает current пользователя access codes.
     *
     * @return array
     */
    private function getCurrentUserAccessCodes(): array
    {
        global $USER;
        if (!($USER instanceof \CUser) || !$USER->IsAuthorized()) {
            return [];
        }

        $userId = (int)$USER->GetID();
        $codes = ['U' . $userId, 'AU'];

        $groups = $USER->GetUserGroupArray();
        foreach (is_array($groups) ? $groups : [] as $groupId) {
            $groupId = (int)$groupId;
            if ($groupId > 0) {
                $codes[] = 'G' . $groupId;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Проверяет признак admin.
     *
     * @return bool
     */
    private function isAdmin(): bool
    {
        global $USER;
        return ($USER instanceof \CUser) && $USER->IsAuthorized() && $USER->IsAdmin();
    }
}
