#!/usr/bin/php
<? // ВЫГРУЗКА В МАРКЕТ
ignore_user_abort(1);
set_time_limit(0);
error_reporting (E_ERROR | E_PARSE );
ini_set('display_errors', 'off');
//error_reporting (E_ALL);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/www/electra/reluce";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');


define("IBLOCK", 36); //  ID Инфоблока
define("URL", 'http://shop.electra.ru'); //  URL сайта
define("FILENAME", 'ya_'); //  URL сайта

CModule::IncludeModule("iblock");
	
$xml_params =  array(
'nn'=>array('delivery' => 300, 'delivery_max' => 500, 'limit' => 3000, 'weight' => 3, 'code'=>'136',  'url' => URL), 
'msk'=>array('delivery' => 300, 'delivery_max' => 500, 'limit' => 5000, 'weight' => 20, 'code'=>'142', 'url' => 'http://msk-shop.electra.ru')
);

$town = $_GET['town'];
if(!$town) $town = $argv[1];
if(!$town) $town = 'nn'; 


$xml  = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE yml_catalog SYSTEM "shops.dtd">';
$xml .= '<yml_catalog date="'.date("Y-m-d H:i").'">';
$xml .= '<shop>';

$xml .= '<name>Электра</name>
		 <company>Фирма Электра</company>
		 <url>'.$xml_params[$town]['url'].'</url>
		 <platform>Bitrix</platform>
		 <version>12.5</version>
		 <agency>Имиджпром</agency>
		 <email>raven@imageprom.com</email>
		 <currencies>
			<currency id="RUR" rate="1" plus="0" />
		 </currencies>';
		
/* Категории */

$dbSectList = CIBlockSection::GetList(Array('depth_level'=>'asc'), Array('IBLOCK_ID'=>'36','ACTIVE'=>'Y') , false);
$xml .= '<categories>';
while($arSect = $dbSectList->GetNext()) {
	$xml .= '<category id="'.$arSect['ID'].'"';
	if ($arSect['IBLOCK_SECTION_ID']!='') {
		$xml .= ' parentId="'.$arSect['IBLOCK_SECTION_ID'].'" ';
	}
    $xml .='>'.$arSect['NAME'].'</category>'; 
}
$xml .= '</categories>';

$xml .= '<local_delivery_cost>'.$xml_params[$town]['delivery'].'</local_delivery_cost>';


$xml .= '<offers>';
 
$arSelect = Array("IBLOCK_ID", "ID", "NAME", "DETAIL_PAGE_URL", "XML_ID", "DETAIL_PICTURE", "DETAIL_TEXT");
$arFilter = Array("IBLOCK_ID"=>"36", "ACTIVE"=>"Y", '>PROPERTY_ost_'.$xml_params[$town]['code'] => '0' );
$dmElem = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
$cnt = 0;
while($ob = $dmElem->GetNextElement()){ 
    $cnt++;
	$arFields = $ob->GetFields();  
	$arProps = $ob->GetProperties();
	
	$xml .= '<offer id="'.$arFields['ID'].'" type="vendor.model" bid="13" available="';
		if(intval($arProps['ost_'.$xml_params[$town]['code']]['VALUE'])>0) $xml .= 'true'; else  $xml .= 'false';
	$xml .='">';
	
	
	$xml .= '<url>'.$xml_params[$town]['url'].$arFields['DETAIL_PAGE_URL'].'</url>
			<price>'.$arProps['PRICE']['VALUE'].'</price>
			<currencyId>RUR</currencyId>
			<categoryId>'.$arFields['IBLOCK_SECTION_ID'].'</categoryId>';
			if($arFields['DETAIL_PICTURE']) {
			
				$rsFile = CFile::GetPath($arFields["DETAIL_PICTURE"]);
				$xml .='<picture>'.$xml_params[$town]['url'].$rsFile.'</picture>';
			
			}
	$xml .=	'<store>true</store>
			<pickup>false</pickup>
			<delivery>true</delivery>';
			
	if($arProps['PRICE']['VALUE']>$xml_params[$town]['limit']) $xml .= '<local_delivery_cost>0</local_delivery_cost>';	
    elseif(floatval($arProps['Upakovka__ves__kg']['VALUE'])>=$xml_params[$town]['weight']) $xml .= '<local_delivery_cost>'.$xml_params[$town]['delivery_max'].'</local_delivery_cost>';
	else $xml .= '<local_delivery_cost>'.$xml_params[$town]['delivery'].'</local_delivery_cost>';
	
	$xml .= '<vendor>'.$arProps['BRAND']['VALUE'].'</vendor>
			 <model>'.$arFields['NAME'].'</model>';
	
	if($arFields['DETAIL_TEXT']) $xml .= '<description>'.strip_tags($arFields['DETAIL_TEXT']).'</description>';
			 
	foreach($arProps as $property)	{
		if($property['SORT'] < 400 && $property['VALUE']) $xml .='<param name="'.$property['NAME'].'">'.$property['VALUE'].'</param>';
    }

	$xml .= '</offer>';
}

echo $cnt.' товаров<br />';

$xml .= '</offers></shop></yml_catalog>';
if($town !='nn') $name = '_'.$town; else  $name = ''; 
$file = fopen($_SERVER["DOCUMENT_ROOT"].'/update/'.FILENAME.$name.".xml","w+");
$test = fwrite($file, $xml);
if ($test) echo 'Данные успешно занесены в файл '.FILENAME.$name.'.xml';
else echo 'Ошибка при записи в файл '.FILENAME.$name.'.xml';
fclose($fp); //Закрытие файла
?>	