<?
global $DB, $MESS, $APPLICATION;

if (!defined('NX_TOOLS_CACHE_TIME')) define('NX_TOOLS_CACHE_TIME', 3600);
if (!defined('CACHED_b_nx_tools')) define('CACHED_b_nx_tools', NX_TOOLS_CACHE_TIME);

$GLOBALS['NX_TOOLS_CACHE'] = [];

require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin_tools.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/filter_tools.php');

CModule::AddAutoloadClasses('nx_tools', [
    'NXTools\CNXUserTypeDirectory' => 'classes/CNXUserTypeDirectory.php',
	'NXTools\CNXPropertyHlblock' => 'classes/CNXPropertyHlblock.php',
	'NXTools\CNXUserTypeHlblock' => 'classes/CNXUserTypeHlblock.php',
	'NXTools\CNXUserTypeUser' => 'classes/CNXUserTypeUser.php',
]);

IncludeModuleLangFile(__FILE__);

