<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Otus\Main\Model\CrmEntityDataTable;

Loc::loadMessages(__FILE__);

/**
 * Класс установщика модуля.
 */
class otus_main extends CModule
{
    /**
     * Конструктор модуля.
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_ID = 'otus.main';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('OTUS_MAIN_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('OTUS_MAIN_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('OTUS_MAIN_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('OTUS_MAIN_PARTNER_URI');
    }

    /**
     * Выполняет установку модуля.
     *
     * @return void
     */
    public function DoInstall()
    {
        RegisterModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();

        global $APPLICATION;
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('OTUS_MAIN_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );
    }

    /**
     * Выполняет удаление модуля.
     *
     * @return void
     */
    public function DoUninstall()
    {
        Loader::includeModule($this->MODULE_ID);

        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallDB();

        UnRegisterModule($this->MODULE_ID);

        global $APPLICATION;
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('OTUS_MAIN_UNINSTALL_TITLE'),
            __DIR__ . '/unstep.php'
        );
    }

    /**
     * Создает таблицу модуля средствами ORM.
     *
     * @return void
     */
    public function InstallDB()
    {
        $connection = Application::getConnection();
        $tableName = CrmEntityDataTable::getTableName();

        if (!$connection->isTableExists($tableName)) {
            CrmEntityDataTable::getEntity()->createDbTable();
        }
    }

    /**
     * Удаляет таблицу модуля.
     *
     * @return void
     */
    public function UnInstallDB()
    {
        $connection = Application::getConnection();
        $tableName = CrmEntityDataTable::getTableName();

        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }
    }

    /**
     * Регистрирует обработчики событий.
     *
     * @return void
     */
    public function InstallEvents()
    {
        EventManager::getInstance()->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            'Otus\Main\Crm\EntityDetailsTabs',
            'onEntityDetailsTabsInitialized'
        );
    }

    /**
     * Снимает регистрацию обработчиков событий.
     *
     * @return void
     */
    public function UnInstallEvents()
    {
        EventManager::getInstance()->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            'Otus\Main\Crm\EntityDetailsTabs',
            'onEntityDetailsTabsInitialized'
        );
    }

    /**
     * Копирует файлы компонента модуля.
     *
     * @return void
     */
    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );
    }

    /**
     * Удаляет файлы компонента модуля.
     *
     * @return void
     */
    public function UnInstallFiles()
    {
        DeleteDirFilesEx('/local/components/otus/crm.entity.data.grid');
    }
}
