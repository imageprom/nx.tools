<?
global $DB, $MESS, $APPLICATION;

if (!defined("NX_MARKET_CACHE_TIME")) define("NX_MARKET_CACHE_TIME", 3600);
if (!defined("CACHED_b_nx_market")) define("CACHED_b_nx_market", NX_MARKET_CACHE_TIME);

$GLOBALS["NX_MARKET_CACHE"] = array();

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin_tools.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/filter_tools.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/local/modules/nx_market/nx_market_tools.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/local/modules/nx_market/nx_market_order_tools.php");

if(!class_exists('PHPMailer')) require_once ($_SERVER["DOCUMENT_ROOT"]."/local/modules/nx_market/class.phpmailer.php"); 

CModule::AddAutoloadClasses("nx_market", array(

	'NXMarket\CNXConfig' => 'nx_market_config.php',
	'NXMarket\Exception' => 'nx_market_config.php',	
	
	'NXMarket\NXMessages' => 'classes/CNXMessages.php',

	'NXMarket\INXFileManager' => 'classes/СNXFileControler.php',
	'NXMarket\CNXFileManager' => 'classes/СNXFileControler.php',
	'NXMarket\CNXTailControler' => 'classes/СNXFileControler.php',

	'NXMarket\INXUpdaterXML' => 'classes/INXUpdaterXML.php',
	'NXMarket\CNXUpdaterXML' => 'classes/CNXUpdaterXML.php',
	'NXMarket\CNXUpdaterReezo' => 'classes/CNXUpdaterReezo.php',

	'NXMarket\COrderElement' => 'classes/CNXOrder.php',
	'NXMarket\IOrder' => 'classes/CNXOrder.php',
	'NXMarket\COrder' => 'classes/CNXOrder.php',

	'NXMarket\INXApiFormat' => 'classes/CNXApiView.php',
	'NXMarket\CNXmlView' => 'classes/CNXApiView.php',
	'NXMarket\CNXJsonView' => 'classes/CNXApiView.php',
	'NXMarket\CNXCsvView' => 'classes/CNXApiView.php',

	'NXMarket\INXApiGet' => 'classes/CNXApiOrder.php',
	'NXMarket\CNXOrderApi' => 'classes/CNXApiOrder.php',

	'NXMarket\CNXUserTypeOrder' => 'classes/CNXUserTypeOrder.php',
));

IncludeModuleLangFile(__FILE__);

