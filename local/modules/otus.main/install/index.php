<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Otus\Main\Iblock\DoctorBookingProperty;
use Otus\Main\Iblock\DoctorBookingSync;
use Otus\Main\Iblock\RequestDealSync;
use Otus\Main\Model\CrmEntityDataTable;
use Otus\Main\Ui\BeginDateButton;

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

        EventManager::getInstance()->registerEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            $this->MODULE_ID,
            DoctorBookingProperty::class,
            'getUserTypeDescription'
        );

        EventManager::getInstance()->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            DoctorBookingSync::class,
            'onAfterIBlockElementAdd'
        );

        EventManager::getInstance()->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            DoctorBookingSync::class,
            'onAfterIBlockElementUpdate'
        );


        EventManager::getInstance()->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterIBlockElementAdd'
        );

        EventManager::getInstance()->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterIBlockElementUpdate'
        );

        EventManager::getInstance()->registerEventHandler(
            'iblock',
            'OnBeforeIBlockElementDelete',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onBeforeIBlockElementDelete'
        );


        EventManager::getInstance()->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementSetPropertyValuesEx',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterIBlockElementSetPropertyValuesEx'
        );

        EventManager::getInstance()->registerEventHandler(
            'crm',
            'OnAfterCrmDealAdd',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterCrmDealAdd'
        );

        EventManager::getInstance()->registerEventHandler(
            'crm',
            'OnAfterCrmDealUpdate',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterCrmDealUpdate'
        );

        EventManager::getInstance()->registerEventHandler(
            'crm',
            'OnBeforeCrmDealDelete',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onBeforeCrmDealDelete'
        );

        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnProlog',
            $this->MODULE_ID,
            BeginDateButton::class,
            'onProlog'
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

        EventManager::getInstance()->unRegisterEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            $this->MODULE_ID,
            DoctorBookingProperty::class,
            'getUserTypeDescription'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            DoctorBookingSync::class,
            'onAfterIBlockElementAdd'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            DoctorBookingSync::class,
            'onAfterIBlockElementUpdate'
        );


        EventManager::getInstance()->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterIBlockElementAdd'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterIBlockElementUpdate'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'iblock',
            'OnBeforeIBlockElementDelete',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onBeforeIBlockElementDelete'
        );


        EventManager::getInstance()->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementSetPropertyValuesEx',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterIBlockElementSetPropertyValuesEx'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'crm',
            'OnAfterCrmDealAdd',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterCrmDealAdd'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'crm',
            'OnAfterCrmDealUpdate',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onAfterCrmDealUpdate'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'crm',
            'OnBeforeCrmDealDelete',
            $this->MODULE_ID,
            RequestDealSync::class,
            'onBeforeCrmDealDelete'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnProlog',
            $this->MODULE_ID,
            BeginDateButton::class,
            'onProlog'
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

        CopyDirFiles(
            __DIR__ . '/js',
            $_SERVER['DOCUMENT_ROOT'] . '/local/js',
            true,
            true
        );

        CopyDirFiles(
            __DIR__ . '/tools',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools',
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
        DeleteDirFilesEx('/local/js/otus/main/doctorbooking');
        DeleteDirFilesEx('/local/js/otus/main/begin_date_button');
        DeleteDirFilesEx('/bitrix/tools/otus.main/doctor_booking.php');
    }
}
