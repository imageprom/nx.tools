#!/usr/bin/php
<? // ВЫГРУЗКА ГУГЛ ДИНАМИЧЕСКИЙ РЕМАРКЕТИНГ
ignore_user_abort(1);
set_time_limit(0);
error_reporting (E_ERROR | E_PARSE );
ini_set('display_errors', 'off');
//error_reporting (E_ALL);
//echo '<pre>';
if(!$_SERVER["DOCUMENT_ROOT"]) $_SERVER["DOCUMENT_ROOT"] = "/mnt/data/www/electra/reluce";

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

define("IBLOCK", 36); //  ID Инфоблока
define("URL", 'http://shop.electra.ru'); //  URL сайта
define("FILENAME", 'google_'); //  URL сайта
 
require($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');

CModule::IncludeModule("iblock");
	
$xml_params =  array(
	'nn'=>array('delivery' => 300, 'delivery_max' => 500, 'limit' => 3000, 'weight' => 3, 'code'=>'136'), 
	'msk'=>array('delivery' => 300, 'delivery_max' => 500, 'limit' => 5000, 'weight' => 20, 'code'=>'142')
);

$town = $_GET['town'];
if(!$town) $town = $argv[1];
if(!$town) $town = 'nn'; 


$xml  = '<?xml version="1.0"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
		<channel>
		<title>Электра - интернет магазин</title>
		<link>'.URL.'</link>
		<description>Фирма «Электра» — интернет-магазин люстр, бра, светильников и спотов</description>
		';
		
/* Категории */

$dbSectList = CIBlockSection::GetList(Array('depth_level'=>'asc'), Array('IBLOCK_ID'=> IBLOCK ,'ACTIVE'=>'Y') , false, array('UF_GOOGLE_CL'));
$sectionList = array();
while($arSect = $dbSectList->GetNext()) {
	$sectionList[$arSect['ID']] = $arSect['UF_GOOGLE_CL'];
}
 
$arSelect = Array("IBLOCK_ID", "ID", "NAME", "DETAIL_PAGE_URL", "XML_ID", "DETAIL_PICTURE");
$arFilter = Array("IBLOCK_ID"=> IBLOCK, "ACTIVE"=>"Y");
$dmElem = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
$cnt = 0;
while($ob = $dmElem->GetNextElement()){ 
    $cnt++;
	$arFields = $ob->GetFields();  
	$arProps = $ob->GetProperties();
	
	$xml .= '
		<item>
			<title>'.$arFields['NAME'].'</title>
			<link>'.URL.$arFields['DETAIL_PAGE_URL'].'</link>
			';
	
	$prop = ' ';
	foreach($arProps as $property)	{
		if($property['SORT'] < 400 && $property['VALUE'] && $property['CODE'] != 'BRAND' ) $prop .= $property['NAME'].': '.$property['VALUE'].'; ';
    }
	$xml .= '<description>'. strip_tags($arFields['DETAIL_TEXT']).$prop.'</description>
			 <g:id>'.$arFields['ID'].'</g:id>
			 <g:condition>new</g:condition>
			 <g:price>'.$arProps['PRICE']['VALUE'].' RUB</g:price>
			 ';
			 
	$xml .= '<g:availability>';
		if(intval($arProps['ost_'.$xml_params[$town]['code']]['VALUE'])>0) {
			if($arProps['TO_ORDER']['VALUE'] == 1) $xml .= 'available for order';
			else $xml .= 'in stock';
		}
		else $xml .= 'out of stock';
	$xml .= '</g:availability>
			';
	
	if($arFields['DETAIL_PICTURE']) {		
		$rsFile = CFile::GetPath($arFields["DETAIL_PICTURE"]);
		$xml .='<g:image_link>'.URL.$rsFile.'</g:image_link>
				';
	}
	
	// Изменить для расчета цены по весу

	if($arProps['PRICE']['VALUE'] > $xml_params[$town]['limit']) $delivery_cost = 0;	
    elseif(floatval($arProps['Upakovka__ves__kg']['VALUE']) >= $xml_params[$town]['weight']) $delivery_cost = $xml_params[$town]['delivery_max'];
	else $delivery_cost = $xml_params[$town]['delivery'];
	
	$xml .='<g:shipping>
				<g:country>RU</g:country>
				<g:service>Standard</g:service>
				<g:price>'.$delivery_cost.' RUB</g:price>
			</g:shipping>
			<g:brand>'.$arProps['BRAND']['VALUE'].'</g:brand>
			<g:mpn>'.$arFields['XML_ID'].'</g:mpn>
			<g:google_product_category>'.$sectionList[$arFields['IBLOCK_SECTION_ID']].'</g:google_product_category>
			<g:product_type>'.$sectionList[$arFields['IBLOCK_SECTION_ID']].'</g:product_type>
		</item>';
}

echo $cnt.' товаров<br />';

$xml .= '</channel>
		 </rss>';
		 
if($town !='nn') $name = '_'.$town; else  $name = ''; 
$file = fopen($_SERVER["DOCUMENT_ROOT"].'/update/'.FILENAME.$name.".xml","w+");
$test = fwrite($file, $xml);
if ($test) echo 'Данные успешно занесены в файл '.FILENAME.$name.'.xml';
else echo 'Ошибка при записи в файл '.FILENAME.$name.'.xml';
fclose($fp); //Закрытие файла
?>	