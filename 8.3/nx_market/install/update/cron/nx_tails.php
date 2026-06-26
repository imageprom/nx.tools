#!/usr/bin/php
<? // СКРИПТ УПРАВЛЯЮЩИЙ ВЫЗОВОМ ЗАГРУЗОК
error_reporting (E_ERROR | E_PARSE );

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/www/electra/reluce";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include.php');
//require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/xml.creator.php");
//require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/tails.creator.php");
//require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/region.list.php"); //Список регионов
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/iblock.php");

// Скрипт - загрузчик
ignore_user_abort(1);
set_time_limit(0);

CModule::IncludeModule("iblock"); CModule::IncludeModule("NX_MARKET");

global $DB;
$region=new NX_RegionList();
echo '<pre>';

echo "RELUCE: ";
$user_xml_ftp = new CNX_ftp_connector($_SERVER["DOCUMENT_ROOT"], "reluce_ost.xml", "/update/ostatki/", "/update/archive/region/");	
$user_xml_tiles = new CNX_tails_creator ($DB, 22, "catalog", "nx_ostatki_reluce", $user_xml_ftp, "reluce");
if(!$user_xml_tiles->CheckStatus()) {
	$user_xml_tiles->CreateEmptyStructure();
	$cnt=0;
		foreach ($region->GetList() as $town=>$id) { 
			$user_xml_tiles->SetStatus(1); 
			if ($user_xml_tiles->SingleUpdate($town, true)) {echo $town." update -";  $cnt++;}
			$user_xml_tiles->SetStatus(0);
		}
		
	if ($cnt==0) echo "Остатков нет";
	else {
		$user_xml_tiles->ClearCache('s1'); 
		echo "update ".$cnt." regions";	 
	}	
}


$user_xml_ftp = new CNX_ftp_connector($_SERVER["DOCUMENT_ROOT"], "rozn_ost.xml", "/update/ostatki/", "/update/archive/region/");	
$user_xml_tiles = new CNX_tails_creator ($DB, 36, "catalog", "nx_ostatki_rozn", $user_xml_ftp, "rozn");

echo "ELECTRA: ";
if(!$user_xml_tiles->CheckStatus()) {
	$user_xml_tiles->CreateEmptyStructure();
	$cnt=0;
		foreach ($region->GetList() as $town=>$id) { 
			$user_xml_tiles->SetStatus(1); 
			if ($user_xml_tiles->SingleUpdate($town, true) ) {echo ($town.' update -');  $cnt++;}
			$user_xml_tiles->SetStatus(0);
		}
		
	if ($cnt==0) echo "Остатков нет";
	else {
		$user_xml_tiles->ClearCache('s3'); 
		echo "update ".$cnt." regions";	 
	}	
}

echo "SNEHA: ";

$user_xml_ftp = new CNX_ftp_connector($_SERVER["DOCUMENT_ROOT"], "sneha_ost.xml", "/update/ostatki/", "/update/archive/region/");	
$user_xml_tiles = new CNX_tails_creator ($DB, 29, "catalog", "nx_ostatki_sneha", $user_xml_ftp, "sneha");

if(!$user_xml_tiles->CheckStatus()) {
		$user_xml_tiles->CreateEmptyStructure();
		$cnt=0;
			foreach ($region->GetList() as $town=>$id) { 
				$user_xml_tiles->SetStatus(1); 
				if ($user_xml_tiles->SingleUpdate($town, true) ) {echo ($town.' update -');  $cnt++;}
				$user_xml_tiles->SetStatus(0);
			}
			
		if ($cnt==0) echo "Остатков нет";
        else {
			$user_xml_tiles->ClearCache('s2'); 
			echo "update ".$cnt." regions";	 
		}		
}

echo "TEST: ";

$user_xml_ftp = new CNX_ftp_connector($_SERVER["DOCUMENT_ROOT"], "test_ost.xml", "/update/ostatki/", "/update/archive/region/");	
$user_xml_tiles = new CNX_tails_creator ($DB, 50, "catalog", "nx_ostatki_test", $user_xml_ftp, "test");

if(!$user_xml_tiles->CheckStatus()) {
		$user_xml_tiles->CreateEmptyStructure();
		$cnt=0;
			foreach ($region->GetList() as $town=>$id) { 
				$user_xml_tiles->SetStatus(1); 
				if ($user_xml_tiles->SingleUpdate($town, true) ) {echo ($town.' update -');  $cnt++;}
				$user_xml_tiles->SetStatus(0);
			}
			
		if ($cnt==0) echo "Остатков нет";
        else {
			$user_xml_tiles->ClearCache('s2'); 
			echo "update ".$cnt." regions";	 
		}		
}
?>











