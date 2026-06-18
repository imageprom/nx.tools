<?
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

if (class_exists('nx_tools'))
    return;

class nx_tools extends CModule
{
    public $MODULE_ID = 'nx_tools';
    public $MODULE_GROUP_RIGHTS = 'Y';
    private ?array $errors = [];

    const string PATH = '/modules/nx_tools';

    protected static string $path = '/local'.self::PATH;
    protected string $instPath;

    function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__.'/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('NX_TOOLS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('NX_TOOLS_MODULE_DESCRIPTION');
        $this->instPath = $_SERVER['DOCUMENT_ROOT'].self::$path.'/install';
    }

    /**
     * @param array $arParams
     * @return bool
     */
    function InstallDB(array $arParams = []) : bool
    {
        global $DB, $APPLICATION;
        $this->errors = null;

        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        \Bitrix\Main\Loader::IncludeModule($this->MODULE_ID);

        return true;
    }

    /**
     * @param array $arParams
     * @return bool
     */
    function UnInstallDB(array $arParams = []) : bool
    {
        global $DB;
        $this->errors = null;

        //delete agents
        CAgent::RemoveModuleAgents($this->MODULE_ID);

        $db_res = $DB->Query("SELECT ID FROM b_file WHERE MODULE_ID = '{$this->MODULE_ID}'");
        while($arRes = $db_res->Fetch()) {
            CFile::Delete($arRes['ID']);
        }

        UnRegisterModule($this->MODULE_ID);

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
       //. UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'nx_tools', 'NXTools\CNXUserTypeOrder', 'GetUserTypeDescription');

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

        CopyDirFiles($this->instPath.'/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
        CopyDirFiles($this->instPath.'/images', $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/'.$this->MODULE_ID, true, true);
        CopyDirFiles($this->instPath.'/components', $_SERVER['DOCUMENT_ROOT'].'/local/components', true, true);
        CopyDirFiles($this->instPath.'/install/js', $_SERVER['DOCUMENT_ROOT'].'/local/js', true, true);

        return true;
    }

    /**
     * @return bool
     */
    function UnInstallFiles() : bool
    {
        $instPath = $_SERVER['DOCUMENT_ROOT'].$this->path.'/install';

        DeleteDirFiles($this->instPath.'/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
        DeleteDirFilesEx('/bitrix/images/nx_tools/'); //images
        DeleteDirFiles($this->instPath.'/components', $_SERVER['DOCUMENT_ROOT'].'/local/components', true, true);
        DeleteDirFiles($this->instPath.'/js', $_SERVER['DOCUMENT_ROOT'].'/local/js', true, true);
        return true;
    }

    /**
     * @return void
     */
    function DoInstall(): void
    {
        global $USER, $APPLICATION, $step, $errors;

        if ($USER->IsAdmin())
        {
            $step = (int) $step;

            if ($step < 2)
            {

                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_TOOLS_INSTALL_TITLE'), $this->instPath.'/step.php');
            }
            elseif ($step == 2)
            {
                if ($this->InstallDB())
                {
                    //$this->InstallFiles();
                    $this->InstallEvents();
                }

                $errors = $this->errors;
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_TOOLS_INSTALL_TITLE'), $this->instPath.'/step.php');
            }
        }
    }

    /**
     * @return void
     */
    public function DoUninstall() : void
    {
        global $USER, $APPLICATION, $step, $errors;

        if ($USER->IsAdmin())
        {
            $step = (int) $step;

            if ($step < 2)
            {
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_TOOLS_INSTALL_TITLE'), $this->instPath.'/unstep.php');
            }
            elseif ($step == 2)
            {
                $this->UnInstallDB([
                    'save_tables' => $_REQUEST['save_tables'],
                ]);
//                $this->UnInstallFiles();
                $this->UnInstallEvents();

                $errors = $this->errors;
                $APPLICATION->IncludeAdminFile(Loc::getMessage('NX_TOOLS_INSTALL_TITLE'), $this->instPath.'/unstep.php');
            }
        }
    }
}
