<?php

namespace Rexp\Form\Infrastructure\Install;

use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\Connection;
use Rexp\Form\Access\Permission\FormPermissionTable;
use Rexp\Form\Access\Permission\PermissionDictionary;
use Rexp\Form\Access\Role\FormRoleRelationTable;
use Rexp\Form\Access\Role\FormRoleTable;
use Rexp\Form\Access\Role\RoleDictionary;
use Rexp\Form\Infrastructure\Persistence\Orm\FormDraftTable;
use Rexp\Form\Infrastructure\Persistence\Orm\FormSubmissionTable;
use Rexp\Form\Infrastructure\Persistence\Orm\FormTable;
use Rexp\Form\Infrastructure\Persistence\Orm\FormVersionTable;

/**
 * Помощник установки, обновления и удаления таблиц модуля.
 */
final class SchemaMigrationHelper
{
    public const MODULE_ID = 'rexp.form';
    public const FORM_TABLE = 'rexp_form_form';
    public const VERSION_TABLE = 'rexp_form_version';
    public const DRAFT_TABLE = 'rexp_form_draft';
    public const SUBMISSION_TABLE = 'rexp_form_submission';
    public const ACCESS_ROLE_TABLE = 'rexp_form_access_role';
    public const ACCESS_ROLE_RELATION_TABLE = 'rexp_form_access_role_relation';
    public const ACCESS_PERMISSION_TABLE = 'rexp_form_access_permission';

    /**
     * Создаёт базовые таблицы модуля.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function createBaseTables(Connection $connection): void
    {
        if (!$connection->isTableExists(self::FORM_TABLE)) {
            FormTable::getEntity()->createDbTable();
        }

        if (!$connection->isTableExists(self::VERSION_TABLE)) {
            FormVersionTable::getEntity()->createDbTable();
        }

        if (!$connection->isTableExists(self::DRAFT_TABLE)) {
            FormDraftTable::getEntity()->createDbTable();
        }
    }

    /**
     * Создаёт таблицу отправок форм.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function createSubmissionTable(Connection $connection): void
    {
        if (!$connection->isTableExists(self::SUBMISSION_TABLE)) {
            FormSubmissionTable::getEntity()->createDbTable();
        }
    }

    /**
     * Создаёт таблицы прав доступа.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function createAccessTables(Connection $connection): void
    {
        if (!$connection->isTableExists(self::ACCESS_ROLE_TABLE)) {
            FormRoleTable::getEntity()->createDbTable();
        }

        if (!$connection->isTableExists(self::ACCESS_ROLE_RELATION_TABLE)) {
            FormRoleRelationTable::getEntity()->createDbTable();
        }

        if (!$connection->isTableExists(self::ACCESS_PERMISSION_TABLE)) {
            FormPermissionTable::getEntity()->createDbTable();
        }

        $this->seedDefaultAccessRoles();
    }

    /**
     * Проверяет и создаёт индексы базовых таблиц.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function ensureIndexes(Connection $connection): void
    {
        $this->ensureIndex($connection, self::FORM_TABLE, 'UX_REXP_FORM_FORM_CODE', 'UNIQUE', ['CODE']);
        $this->ensureIndex($connection, self::VERSION_TABLE, 'IX_REXP_FORM_VERSION_FORM_ID', 'INDEX', ['FORM_ID']);
        $this->ensureIndex($connection, self::DRAFT_TABLE, 'UX_REXP_FORM_DRAFT_FORM_USER', 'UNIQUE', ['FORM_ID', 'USER_ID']);
        $this->ensureIndex($connection, self::DRAFT_TABLE, 'IX_REXP_FORM_DRAFT_USER_ID', 'INDEX', ['USER_ID']);
    }

    /**
     * Проверяет и создаёт индексы таблицы отправок.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function ensureSubmissionIndexes(Connection $connection): void
    {
        $this->ensureIndex($connection, self::SUBMISSION_TABLE, 'IX_REXP_FORM_SUBMISSION_FORM_ID', 'INDEX', ['FORM_ID']);
        $this->ensureIndex($connection, self::SUBMISSION_TABLE, 'IX_REXP_FORM_SUBMISSION_STATUS', 'INDEX', ['STATUS']);
        $this->ensureIndex($connection, self::SUBMISSION_TABLE, 'IX_REXP_FORM_SUBMISSION_CREATED_AT', 'INDEX', ['CREATED_AT']);
    }

    /**
     * Проверяет и создаёт индексы таблиц прав.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function ensureAccessIndexes(Connection $connection): void
    {
        $this->ensureIndex($connection, self::ACCESS_ROLE_TABLE, 'UX_REXP_FORM_ACCESS_ROLE_CODE', 'UNIQUE', ['CODE']);
        $this->ensureIndex($connection, self::ACCESS_ROLE_RELATION_TABLE, 'IX_REXP_FORM_ACCESS_REL_ROLE', 'INDEX', ['ROLE_ID']);
        $this->ensureIndex($connection, self::ACCESS_ROLE_RELATION_TABLE, 'IX_REXP_FORM_ACCESS_REL_CODE', 'INDEX', ['ACCESS_CODE']);
        $this->ensureIndex($connection, self::ACCESS_PERMISSION_TABLE, 'IX_REXP_FORM_ACCESS_PERM_ROLE', 'INDEX', ['ROLE_ID']);
        $this->ensureIndex($connection, self::ACCESS_PERMISSION_TABLE, 'IX_REXP_FORM_ACCESS_PERM_ID', 'INDEX', ['PERMISSION_ID']);
    }

    /**
     * Создаёт базовые роли доступа модуля.
     *
     * @return void
     */
    private function seedDefaultAccessRoles(): void
    {
        if ((int)FormRoleTable::getCount() > 0) {
            return;
        }

        $rolePermissions = [
            RoleDictionary::ADMIN => PermissionDictionary::getAll(),
            RoleDictionary::EDITOR => [
                PermissionDictionary::SECTION_VIEW,
                PermissionDictionary::FORM_READ,
                PermissionDictionary::FORM_EDIT,
                PermissionDictionary::FORM_PUBLISH,
                PermissionDictionary::SUBMISSION_READ,
                PermissionDictionary::BIZPROC_MANAGE,
                PermissionDictionary::PUBLIC_USE,
            ],
            RoleDictionary::USER => [
                PermissionDictionary::SECTION_VIEW,
                PermissionDictionary::FORM_READ,
                PermissionDictionary::PUBLIC_USE,
            ],
        ];

        foreach (RoleDictionary::getLabels() as $code => $name) {
            $result = FormRoleTable::add([
                'CODE' => $code,
                'NAME' => $name,
                'IS_SYSTEM' => 'Y',
                'SORT' => $code === RoleDictionary::ADMIN ? 10 : ($code === RoleDictionary::EDITOR ? 20 : 30),
            ]);
            if (!$result->isSuccess()) {
                continue;
            }

            $roleId = (int)$result->getId();
            foreach ($rolePermissions[$code] ?? [] as $permissionId) {
                FormPermissionTable::add([
                    'ROLE_ID' => $roleId,
                    'PERMISSION_ID' => $permissionId,
                    'VALUE' => 'Y',
                ]);
            }

            if ($code === RoleDictionary::USER) {
                FormRoleRelationTable::add([
                    'ROLE_ID' => $roleId,
                    'ACCESS_CODE' => 'AU',
                ]);
            }
        }
    }

    /**
     * Создаёт индекс таблицы, если он отсутствует.
     *
     * @param Connection $connection
     * @param string $tableName
     * @param string $indexName
     * @param string $type
     * @param array $columns
     *
     * @return void
     */
    public function ensureIndex(Connection $connection, string $tableName, string $indexName, string $type, array $columns): void
    {
        if (!$connection->isTableExists($tableName) || $this->indexExists($connection, $tableName, $indexName)) {
            return;
        }

        $cols = '`' . implode('`,`', $columns) . '`';
        $prefix = $type === 'UNIQUE' ? 'UNIQUE ' : '';
        $connection->queryExecute('ALTER TABLE `' . $tableName . '` ADD ' . $prefix . 'INDEX `' . $indexName . '` (' . $cols . ')');
    }

    /**
     * Проверяет существование индекса таблицы.
     *
     * @param Connection $connection
     * @param string $tableName
     * @param string $indexName
     *
     * @return bool
     */
    public function indexExists(Connection $connection, string $tableName, string $indexName): bool
    {
        $result = $connection->query('SHOW INDEX FROM `' . $tableName . '`');
        while ($row = $result->fetch()) {
            if ((string)($row['Key_name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Возвращает текущую версию схемы модуля.
     *
     * @return string
     */
    public function getCurrentSchemaVersion(): string
    {
        return (string)Option::get(self::MODULE_ID, 'schema_version', '0.0.0');
    }

    /**
     * Сохраняет текущую версию схемы модуля.
     *
     * @param string $version
     *
     * @return void
     */
    public function setCurrentSchemaVersion(string $version): void
    {
        Option::set(self::MODULE_ID, 'schema_version', $version);
    }

    /**
     * Удаляет таблицы модуля.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function dropAllTables(Connection $connection): void
    {
        foreach ([self::ACCESS_PERMISSION_TABLE, self::ACCESS_ROLE_RELATION_TABLE, self::ACCESS_ROLE_TABLE, self::SUBMISSION_TABLE, self::DRAFT_TABLE, self::VERSION_TABLE, self::FORM_TABLE] as $tableName) {
            if ($connection->isTableExists($tableName)) {
                $connection->queryExecute('DROP TABLE IF EXISTS `'. $tableName .'`');
            }
        }
    }

    /**
     * Очищает настройки модуля.
     *
     * @return void
     */
    public function clearModuleOptions(): void
    {
        Option::delete(self::MODULE_ID);
    }
}

