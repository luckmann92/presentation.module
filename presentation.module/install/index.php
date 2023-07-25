<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;
use PresentModule\App\Handlers\OnPrologHandler;
use Bitrix\Main\Loader;
use RN\DB\Install;

class presentation_module extends CModule
{
    /**
     * @var \Bitrix\Main\EventManager
     */
    protected $eventManager;

    public function __construct()
    {
        $arModuleVersion = [];

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        $this->PATH = $path;
        include($path."/version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        $this->MODULE_ID = 'presentation.module';
        $this->MODULE_NAME = 'Модуль для презентации';
        $this->PARTNER_NAME = 'presentation';
        $this->PARTNER_URI = '';
        $this->eventManager = EventManager::getInstance();
    }

    public function DoInstall(): bool
    {
        $this->installDB();
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installEvents();

        return true;
    }

    public function installEvents()
    {
		$this->eventManager->registerEventHandler(
			'main',
			'OnProlog',
			$this->MODULE_ID,
			OnPrologHandler::class,
			'onProlog'
		);
    }

    public function installDB(): bool
    {
		Loader::includeModule($this->MODULE_ID);
		Install::execute();
    }

    public function DoUninstall(): bool
    {
        $this->uninstallEvents();
        $this->uninstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

	public function uninstallEvents()
	{
		$this->eventManager->unRegisterEventHandler(
			'main',
			'OnProlog',
			$this->MODULE_ID,
			OnPrologHandler::class,
			'onProlog'
		);
	}

	public function uninstallDB()
	{
		Loader::includeModule($this->MODULE_ID);
		Install::unInstall();
	}
}
