<?php
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */


IncludeModuleLangFile(__FILE__);
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);


if (class_exists('nx_tools'))
    return;

class nx_tools extends CModule
{
    var $MODULE_ID = 'nx_tools';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = 'Y';
    var $errors;

    const PATH = '/modules/nx_tools';

    var string $path = '/local'.self::PATH;

    function __construct()
    {
        //$PathInstall = str_replace("\\", "/", __FILE__);
        //$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));
        //IncludeModuleLangFile($PathInstall."/install.php");

        $arModuleVersion = [];
        include(__DIR__.'/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('NX_TOOLS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('NX_TOOLS_MODULE_DESCRIPTION');
    }

    /**
     * @param array $arParams
     * @return bool
     */
    function InstallDB($arParams = [])
    {
        global $DB, $APPLICATION;
        $this->errors = false;

        \Bitrix\Main\ModuleManager::registerModule('nx_tools');
        \Bitrix\Main\Loader::IncludeModule('nx_tools');

        return true;

    }

    /**
     * @param array $arParams
     * @return bool
     */
    function UnInstallDB($arParams = []) : bool
    {
        global $DB, $APPLICATION;
        $this->errors = false;

        //delete agents
        CAgent::RemoveModuleAgents('nx_tools');

        $db_res = $DB->Query("SELECT ID FROM b_file WHERE MODULE_ID = 'nx_tools'");
        while($arRes = $db_res->Fetch()) {
            CFile::Delete($arRes['ID']);
        }

        UnRegisterModule('nx_tools');

        return true;
    }

    /**
     * @return bool
     */
    function InstallEvents(): bool
    {
        // TODO протестировать синтаксис D7 или вернуться к старому
        // RegisterModuleDependences('main', 'OnBeforeProlog', 'main', '', '', 100, '/modules/nx_tools/before.php');
        // RegisterModuleDependences('iblock', 'OnIBlockPropertyBuildList', 'nx_tools', 'NXTools\CNXUserTypeDirectory', 'GetUserTypeDescription');
        // RegisterModuleDependences('main', 'OnUserTypeBuildList', 'nx_tools', 'NXTools\CNXUserTypeHlblock', 'getDescription');
        // RegisterModuleDependences('main', 'OnUserTypeBuildList', 'nx_tools', 'NXTools\CNXUserTypeUser', 'getDescription');
        // RegisterModuleDependences('main', 'OnUserTypeBuildList', 'nx_tools', 'NXTools\CNXUserTypeOrder', 'getDescription');

        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->registerEventHandler(
            'main',
            'OnBeforeProlog',
            'nx_tools',
            '',
            '',
            100,
            '/modules/nx_tools/before.php'
        );

        $eventManager->registerEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeDirectory',
            'GetUserTypeDescription'
        );

        $eventManager->registerEventHandler(
            'main',
            'OnUserTypeBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeHlblock',
            'getDescription'
        );

        $eventManager->registerEventHandler(
            'main',
            'OnUserTypeBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeUser',
            'getDescription'
        );

        $eventManager->registerEventHandler(
            'main',
            'OnUserTypeBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeOrder',
            'getDescription'
        );

        return true;
    }

    /**
     * @return bool
     */
    function UnInstallEvents(): bool
    {
        UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'nx_tools', 'NXTools\CNXUserTypeOrder', 'GetUserTypeDescription');

        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnBeforeProlog',
            'nx_tools',
            '',
            '',
            '/modules/nx_tools/before.php'
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeDirectory',
            'GetUserTypeDescription'
        );

        $eventManager->unRegisterEventHandler(
            'main',
            'OnUserTypeBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeHlblock',
            'getDescription'
        );

        $eventManager->unRegisterEventHandler(
            'main',
            'OnUserTypeBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeUser',
            'getDescription'
        );

        $eventManager->unRegisterEventHandler(
            'main',
            'OnUserTypeBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeOrder',
            'getDescription'
        );

        $eventManager->unRegisterEventHandler(
            'main',
            'OnUserTypeBuildList',
            'nx_tools',
            'NXTools\CNXUserTypeOrder',
            'GetUserTypeDescription'
        );
        
        
        return true;
    }

    /**
     * @param array $arParams
     * @return bool
     */
    function InstallFiles(array $arParams = []) : bool
    {
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/images', $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/nx_tools', true, true);
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/components', $_SERVER['DOCUMENT_ROOT'].'/local/components', true, true);
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/js', $_SERVER['DOCUMENT_ROOT'].'/local/js', true, true);
        return true;
    }

    /**
     * @return bool
     */
    function UnInstallFiles() : bool
    {
        DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
        DeleteDirFilesEx('/bitrix/images/nx_tools/'); //images
        DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/components', $_SERVER['DOCUMENT_ROOT'].'/local/components', true, true);
        DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/js', $_SERVER['DOCUMENT_ROOT'].'/local/js', true, true);
        return true;
    }

    /**
     * @return void
     */
    function DoInstall(): void
    {
        global $USER, $APPLICATION, $step;
        if ($USER->IsAdmin())
        {
            $step = intval($step);
            if ($step < 2)
            {
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_TOOLS_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].$this->path.'/install/step.php');
            }
            elseif ($step == 2)
            {
                if ($this->InstallDB())
                {
                    $this->InstallFiles();
                    $this->InstallEvents();
                }
                $GLOBALS['errors'] = $this->errors;
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_TOOLS_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].$this->path.'/install/step.php');
            }
        }
    }

    /**
     * @return void
     */
    public function DoUninstall() : void
    {
        global $USER, $APPLICATION, $step;
        if ($USER->IsAdmin())
        {
            $step = intval($step);
            if ($step < 2)
            {
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_TOOLS_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].$this->path.'/install/unstep.php');
            }
            elseif ($step == 2)
            {
                $this->UnInstallDB([
                    'save_tables' => $_REQUEST['save_tables'],
                ]);
                $this->UnInstallFiles();
                $this->UnInstallEvents();
                $GLOBALS['errors'] = $this->errors;
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_TOOLS_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].$this->path.'/install/unstep.php');
            }
        }
    }
}
