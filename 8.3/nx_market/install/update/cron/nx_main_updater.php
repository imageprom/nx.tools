#!/usr/bin/php
<? // СКРИПТ УПРАВЛЯЮЩИЙ ВЫЗОВОМ ЗАГРУЗОК
ignore_user_abort(1);
set_time_limit(0);
error_reporting (E_ERROR | E_PARSE );
ini_set('display_errors', 'off');

//error_reporting (E_ALL);
//echo '<pre>';


$_SERVER["DOCUMENT_ROOT"] = '/mnt/data/www/ostrov';


define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("HELP_FILE", "settings/cache.php");
require($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/cache_files_cleaner.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_html.php");

// Скрипт - загрузчик

CModule::IncludeModule("iblock"); CModule::IncludeModule("nx_market");

$LOCK_TIME = NXMarket\CNXConfig::$LockTime;

$memcache = new Memcache; 
$memcache->connect('localhost', 11211) or die ("Не могу подключиться");

$memcahed_flag = 'nx_main_update'.'_'.NXMarket\CNXConfig::$Project;

if($argv[1] == 'unlock' || $_GET['mode'] == 'unlock') $memcache->set($memcahed_flag, false, false, $LOCK_TIME) or die ("Ошибка при сохранении данных на сервере");
$update_lock = $memcache->get($memcahed_flag);

if($update_lock) {echo 'update lock'; return false;}

$time_start = NXMarket\microtime_float();

$update_path = NXMarket\CNXConfig::GetPath('update');

$files = glob(NXMarket\CNXConfig::GetSource('full'));
$files_inc = glob(NXMarket\CNXConfig::GetSource('inc'));

if(is_array($files) && is_array($files_inc)) $files = array_merge($files, $files_inc);
elseif(is_array($files_inc)) $files = $files_inc;
 
if(count($files) > 0 && !$update_lock) {
		
	NXMarket\WriteLog('
	
+++++++++++++++++
'.date(DATE_ATOM));
	
	//echo  $memcahed_flag;
	$memcache->set($memcahed_flag, true, false, $LOCK_TIME) or die ("Ошибка при сохранении данных на сервере");

	$nx_images = new NXMarket\CNXImageControler($update_path, NXMarket\CNXConfig::$path['photos'], NXMarket\CNXConfig::$path['photosBig'], NXMarket\CNXConfig::$path['photosPrw'], false,'*g');
	$nx_images->SetModifier(new NXMarket\CNXWatermark());
    
	echo ' update '.$nx_images->ResizePrw(false, array('WIDTH'=>300, 'HEIGHT'=> 300, 'MODE' => 'square')).' pictures;   || ';
    
    $fileControler = new NXMarket\CNXFileManager(NXMarket\CNXConfig::$source['full'], $_SERVER['DOCUMENT_ROOT'], NXMarket\CNXConfig::$path['update'], NXMarket\CNXConfig::$path['tmp'],  NXMarket\CNXConfig::$path['bad'], NXMarket\CNXConfig::$path['archive']);

	foreach ($files as $cnt => $filename) {

		$result = array();
		$filename = str_replace(NXMarket\CNXConfig::GetPath('xml'), '', $filename);

		$incrimental = false;
		if(strpos($filename, '_inc_') !== false) {$incrimental = true;}
		
		echo NXMarket\CNXConfig::$destination['iblockId'].'<br />';

		if(NXMarket\CNXConfig::$destination['iblockId']) {
					
			$fileControler->SetSourceFile($filename);

			$user_xml_file = new NXMarket\CNXUpdaterXML(NXMarket\CNXConfig::$destination['iblockId'], NXMarket\CNXConfig::$destination['iblockName'], NXMarket\CNXConfig::$destination['iblockFlag']);
			$user_xml_file->WriteLog();

			if ($user_xml_file->ParseXML($fileControler)) {

				$line_props = array(
						'PRICE' => array('NAME' => 'Цена', 'PROPERTY_TYPE'=>'N', 'SRC' => 'Price', 'IS_REQUIRED' => 'Y'),					
						//'PRICE_OLD' => array('NAME' => 'Старая цена', 'PROPERTY_TYPE'=>'N', 'SRC' => 'PriceOld'),
						'PRICE_OLD' => array('NAME' => 'Старая цена', 'PROPERTY_TYPE'=>'N', 'SRC' => 'PriceOpt'),
						'ost_52' => array('NAME' => 'Розничная цена', 'PROPERTY_TYPE'=>'N', 'SRC' => 'Ostatok'),
						//'VIDEO' => array('NAME' => 'Видео', 'PROPERTY_TYPE'=>'S', 'USER_TYPE'=>'HTML', 'SRC' => 'Video', 'SORT' => 1100),
					);
				
				
				
				// $reg_props = array(
				// 	'ost' => array('NAME' => 'Остатки', 'PROPERTY_TYPE'=>'N', 'SRC' => 'ost')
				// );
				
				// $STATUS = array(
				// 	array('VALUE' => 'new', 'XML_ID' => 'new'),	
				// 	array('VALUE' => 'sale', 'XML_ID' => 'sale'),
				// 	array('VALUE' => 'hit', 'XML_ID' => 'hit'),
				// );
				
				//$user_xml_file->GetIBProperties();
				//$user_xml_file->UpdateLineProperties($line_props);
				//$user_xml_file->UpdateRegionProperties($reg_props);

				//$user_xml_file->UpdateSpecialProperty('SERIES', 'Series', 'Серия',  'Series');
				//$user_xml_file->UpdateSpecialProperty('STATUS', 'Status', 'Статус', $STATUS);
				//$user_xml_file->UpdateSpecialProperty('UNIT', 'Unit', 'Единица измерения', 'Units');
				
				//$user_xml_file->UpdateSpecialProperty('BRAND', 'Brand', 'Производитель', 'Brands', 200);
				
				//$user_xml_file->UpdateProperties();
				
				//$user_xml_file->GetIBSections();
				//$user_xml_file->UpdateSections($nx_images);
				
				$user_xml_file->GetIBElements();
				$user_xml_file->GetIBPictures();
				
				$user_xml_file->UpdateElements($nx_images);
			 
			    
				if(!$incrimental) $user_xml_file->DeactivateOldElements();		
				
 			    $user_xml_file->Archive($fileControler);
			
				echo NXMarket\SendMail($filename, $user_xml_file->GetStat());
			}

		}
	}
	
	BXClearCache(true, "/bitrix/cache/".$sid."/bitrix/catalog.section.list/");
	BXClearCache(true, "/bitrix/cache/".$sid."/bitrix/catalog.section");
	BXClearCache(true, "/bitrix/cache/".$sid."/bitrix/catalog.element");
	BXClearCache(true, "/bitrix/cache/".$sid."/ip/catalog.filter");
	

	foreach (NXMarket\CNXConfig::$task['exec'] as $key => $task) {
		exec(NXMarket\CNXConfig::$exec.$_SERVER["DOCUMENT_ROOT"].$task);
	}
	$memcache->set($memcahed_flag, false, false, $LOCK_TIME) or die ("Ошибка при сохранении данных на сервере");
}

if(!$update_lock) {
    $memcache->set($memcahed_flag, true, false, $LOCK_TIME) or die ("Ошибка при сохранении данных на сервере");
	exec(NXMarket\CNXConfig::$exec.$_SERVER["DOCUMENT_ROOT"].NXMarket\CNXConfig::$task['tail']);
	echo 'update tails';
	$memcache->set($memcahed_flag, false, false, $LOCK_TIME) or die ("Ошибка при сохранении данных на сервере");
}

$time_end = NXMarket\microtime_float();
$time = $time_end - $time_start;
echo '<br />'.$time/60;

?>