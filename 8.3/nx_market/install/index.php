<?php
IncludeModuleLangFile(__FILE__);
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/** @var CMain $APPLICATION */
/** @var CDatabase $DB */

if (class_exists('nx_market'))
    return;

class nx_market extends CModule
{
    var $MODULE_ID = "nx_market";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";
    var $errors;

    const PATH = '/modules/nx_market';

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
        $this->MODULE_NAME = Loc::getMessage('NX_MARKET_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('NX_MARKET_MODULE_DESCRIPTION');
    }

    /**
     * @param array $arParams
     * @return bool
     */
    function InstallDB($arParams = [])
    {
        global $DB, $APPLICATION;
        $this->errors = false;

        \Bitrix\Main\ModuleManager::registerModule('nx_market');
        \Bitrix\Main\Loader::IncludeModule('nx_market');

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
        CAgent::RemoveModuleAgents('nx_market');

        $db_res = $DB->Query("SELECT ID FROM b_file WHERE MODULE_ID = 'nx_market'");
        while($arRes = $db_res->Fetch()) {
            CFile::Delete($arRes['ID']);
        }

        UnRegisterModule('nx_market');

        return true;
    }

    /**
     * @return bool
     */
    function InstallEvents(): bool
    {
        RegisterModuleDependences('main', 'OnUserTypeBuildList', 'nx_market', 'NXMarket\CNXUserTypeOrder', 'GetUserTypeDescription');
        return true;
    }

    /**
     * @return bool
     */
    function UnInstallEvents(): bool
    {
        UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'nx_market', 'NXMarket\CNXUserTypeOrder', 'GetUserTypeDescription');
        return true;
    }

    /**
     * @param array $arParams
     * @return bool
     */
    function InstallFiles($arParams = []) : bool
    {
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/images', $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/nx_market', true, true);
        //CopyDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/components', $_SERVER['DOCUMENT_ROOT'].'/local/components', true, true);
        return true;
    }

    /**
     * @return bool
     */
    function UnInstallFiles() : bool
    {
        DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
        DeleteDirFilesEx('/bitrix/images/nx_market/'); //images
        //DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].$this->path.'/install/components', $_SERVER['DOCUMENT_ROOT'].'/local/components', true, true);
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
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_MARKET_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].$this->path.'/install/step.php');
            }
            elseif ($step == 2)
            {
                if ($this->InstallDB())
                {
                    $this->InstallFiles();
                    $this->InstallEvents();
                }
                $GLOBALS['errors'] = $this->errors;
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_MARKET_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].$this->path.'/install/step.php');
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
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_MARKET_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].$this->path.'/install/unstep.php');
            }
            elseif ($step == 2)
            {
                $this->UnInstallDB([
                    'save_tables' => $_REQUEST['save_tables'],
                ]);
                $this->UnInstallFiles();
                $this->UnInstallEvents();
                $GLOBALS['errors'] = $this->errors;
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_MARKET_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].$this->path.'/install/unstep.php');
            }
        }
    }
}
