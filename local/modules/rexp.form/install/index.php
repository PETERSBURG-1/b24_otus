<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Rexp\Form\Infrastructure\Install\SchemaMigrationHelper;

Loc::loadMessages(__FILE__);

/**
 * Класс установки и удаления модуля конструктора форм.
 */
class rexp_form extends CModule
{
    public $MODULE_ID = 'rexp.form';
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $PARTNER_NAME;
    public $PARTNER_URI = '';

    /**
     * Инициализирует объект и его зависимости.
     */
    public function __construct()
    {
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->PARTNER_NAME = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_INSTALL_INDEX_001');
        $this->MODULE_NAME = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_INSTALL_INDEX_002');
        $this->MODULE_DESCRIPTION = \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_INSTALL_INDEX_003');
    }

    /**
     * Устанавливает модуль и регистрирует его ресурсы.
     *
     * @return void
     */
    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->includeModuleClasses();
        $this->installDb();
        $this->installEvents();
        $this->installFiles();
    }

    /**
     * Удаляет модуль и снимает регистрацию его ресурсов.
     *
     * @return void
     */
    public function DoUninstall(): void
    {
        $this->unInstallEvents();
        $this->unInstallFiles();
        $this->unInstallDb();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Подключает классы модуля, необходимые при установке.
     *
     * @return void
     */
    private function includeModuleClasses(): void
    {
        if (!defined('REXP_FORM_SKIP_AUTO_UPDATE')) {
            define('REXP_FORM_SKIP_AUTO_UPDATE', true);
        }

        if (!defined('REXP_FORM_SKIP_BOOTSTRAP')) {
            define('REXP_FORM_SKIP_BOOTSTRAP', true);
        }

        if (!Loader::includeModule($this->MODULE_ID)) {
            throw new \Bitrix\Main\SystemException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_INSTALL_INDEX_004') . $this->MODULE_ID . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_INSTALL_INDEX_005'));
        }
    }

    /**
     * Создаёт таблицы и служебные данные модуля.
     *
     * @return void
     */
    public function installDb(): void
    {
        $helper = new SchemaMigrationHelper();
        $connection = Application::getConnection();

        $helper->createBaseTables($connection);
        $helper->ensureIndexes($connection);
        $helper->createSubmissionTable($connection);
        $helper->ensureSubmissionIndexes($connection);
        $helper->createAccessTables($connection);
        $helper->ensureAccessIndexes($connection);
        $helper->setCurrentSchemaVersion($this->MODULE_VERSION);
    }

    /**
     * Регистрирует обработчики событий модуля.
     *
     * @return void
     */
    public function installEvents(): void
    {
        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnPageStart',
            $this->MODULE_ID,
            '\\Rexp\\Form\\Infrastructure\\Bitrix\\ServiceLocator\\Bootstrap',
            'register'
        );

        // Старый вариант модуля добавлял отдельный пункт/раздел в CRM.
        // Больше его не регистрируем и при установке обновления снимаем старый обработчик.
        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            '\\Rexp\\Form\\Infrastructure\\Bitrix\\CrmMenuExtension',
            'inject'
        );
    }

    /**
     * Удаляет регистрацию обработчиков событий модуля.
     *
     * @return void
     */
    public function unInstallEvents(): void
    {
        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnPageStart',
            $this->MODULE_ID,
            '\\Rexp\\Form\\Infrastructure\\Bitrix\\ServiceLocator\\Bootstrap',
            'register'
        );

        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            '\\Rexp\\Form\\Infrastructure\\Bitrix\\CrmMenuExtension',
            'inject'
        );
    }

    /**
     * Копирует компоненты, публичные файлы и JS-расширения модуля.
     *
     * @return void
     */
    public function installFiles(): void
    {
        $documentRoot = Application::getDocumentRoot();

        CopyDirFiles(__DIR__ . '/components', $documentRoot . '/local/components', true, true);
        CopyDirFiles(__DIR__ . '/js', $documentRoot . '/local/js', true, true);
        CopyDirFiles(__DIR__ . '/public/forms', $documentRoot . '/forms', true, true);

        $this->removeLegacyCrmEntrypoint();
        $this->installAdminEntrypoints();
    }

    /**
     * Удаляет таблицы и настройки модуля.
     *
     * @return void
     */
    public function unInstallDb(): void
    {
        if ((string)($_REQUEST['savedata'] ?? 'N') === 'Y') {
            return;
        }

        $helper = new SchemaMigrationHelper();
        $helper->dropAllTables(Application::getConnection());
        $helper->clearModuleOptions();
    }

    /**
     * Удаляет установленные файлы модуля.
     *
     * @return void
     */
    public function unInstallFiles(): void
    {
        foreach ($this->getComponentNames() as $component) {
            DeleteDirFilesEx('/local/components/rexp.form/' . $component);
        }

        foreach ($this->getCompatComponentNames() as $component) {
            DeleteDirFilesEx('/local/components/rexp/' . $component);
        }

        foreach ($this->getPublicFiles() as $publicFile) {
            DeleteDirFilesEx($publicFile);
        }

        foreach ($this->getJsExtensionDirs() as $jsDir) {
            DeleteDirFilesEx($jsDir);
        }

        $this->removeLegacyCrmEntrypoint();
        $this->uninstallAdminEntrypoints();
    }

    /**
     * Удаляет устаревший публичный вход конструктора из раздела CRM.
     *
     * @return void
     */
    private function removeLegacyCrmEntrypoint(): void
    {
        DeleteDirFilesEx('/crm/form-constructor');
    }

    /**
     * Создаёт admin-proxy файлы для интеграции с дизайнером бизнес-процессов.
     *
     * @return void
     */
    private function installAdminEntrypoints(): void
    {
        $documentRoot = rtrim(Application::getDocumentRoot(), '/');
        if ($documentRoot === '') {
            return;
        }

        $targetDir = $documentRoot . '/bitrix/admin';
        if (!is_dir($targetDir)) {
            return;
        }

        foreach ($this->getAdminEntrypointMap() as $targetName => $moduleRelativePath) {
            file_put_contents($targetDir . '/' . $targetName, $this->buildAdminProxyContent($moduleRelativePath));
        }
    }

    /**
     * Удаляет admin-proxy файлы модуля.
     *
     * @return void
     */
    private function uninstallAdminEntrypoints(): void
    {
        $documentRoot = rtrim(Application::getDocumentRoot(), '/');
        if ($documentRoot === '') {
            return;
        }

        foreach ($this->getAdminEntrypointMap() as $targetName => $moduleRelativePath) {
            $targetPath = $documentRoot . '/bitrix/admin/' . $targetName;
            if (!is_file($targetPath)) {
                continue;
            }

            $expected = $this->buildAdminProxyContent($moduleRelativePath);
            $actual = (string)file_get_contents($targetPath);
            if ($actual === $expected) {
                @unlink($targetPath);
            }
        }
    }

    /**
     * Возвращает список компонентов модуля для установки.
     *
     * @return array
     */
    private function getComponentNames(): array
    {
        return [
            'designer',
            'designer.editor',
            'designer.settings',
            'designer.module.settings',
            'designer.versions',
            'designer.drafts',
            'designer.submissions',
            'designer.bizproc',
            'runtime',
        ];
    }

    /**
     * Возвращает список совместимых имён компонентов для удаления.
     *
     * @return array
     */
    private function getCompatComponentNames(): array
    {
        return [
            'form.builder',
            'form.runtime',
            'form.designer',
            'form.designer.editor',
            'form.designer.settings',
            'form.designer.module.settings',
            'form.designer.versions',
            'form.designer.drafts',
            'form.designer.submissions',
            'form.designer.bizproc',
        ];
    }

    /**
     * Возвращает карту публичных файлов модуля.
     *
     * @return array
     */
    private function getPublicFiles(): array
    {
        return [
            '/forms/designer/index.php',
            '/forms/designer/editor.php',
            '/forms/designer/settings.php',
            '/forms/designer/module-settings.php',
            '/forms/designer/versions.php',
            '/forms/designer/drafts.php',
            '/forms/designer/submissions.php',
            '/forms/designer/bizproc.php',
            '/forms/runtime/index.php',
        ];
    }

    /**
     * Возвращает список JS-расширений модуля.
     *
     * @return array
     */
    private function getJsExtensionDirs(): array
    {
        return [
            '/local/js/rexp/form/editor',
            '/local/js/rexp/form/settings',
            '/local/js/rexp/form/ui',
            '/local/js/rexp/form_runtime',
        ];
    }

    /**
     * Возвращает карту admin-proxy файлов модуля.
     *
     * @return array
     */
    private function getAdminEntrypointMap(): array
    {
        return [
            'rexp.form_bizproc_activity_settings.php' => '/local/modules/rexp.form/admin/rexp.form_bizproc_activity_settings.php',
            'rexp.form_bizproc_selector.php' => '/local/modules/rexp.form/admin/rexp.form_bizproc_selector.php',
            'rexp.form_bizproc_wf_settings.php' => '/local/modules/rexp.form/admin/rexp.form_bizproc_wf_settings.php',
        ];
    }

    /**
     * Формирует содержимое admin-proxy файла.
     *
     * @param string $moduleRelativePath
     *
     * @return string
     */
    private function buildAdminProxyContent(string $moduleRelativePath): string
    {
        return sprintf("<?php\nrequire \$_SERVER['DOCUMENT_ROOT'] . '%s';\n", $moduleRelativePath);
    }
}
