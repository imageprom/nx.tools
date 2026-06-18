<?php
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

//todo move this ajax handler to component class
//TODO - протестировать, отладить, проверить что можно переписать на D7

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(!check_bitrix_sessid() || !CModule::includeModule('iblock'))
{
	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
	die();
}

global $APPLICATION;

$elements = array();

$APPLICATION->IncludeComponent(
    'ip:nx.hlblock.element.selector',
    '',
    array()
);


switch($_REQUEST['mode'])
{
	case 'search':
	{
        $parent = new NXIblockElementSelector();

		CUtil::JSPostUnescape();
		$APPLICATION->RestartBuffer();

        $filter = array();
		$searchString = trim($_REQUEST['string']);

		if(is_numeric($searchString))
		{
			$filter['=ID'] = intval($searchString);
		}
		else
		{
            if($_REQUEST['hlblock']) {
                $filter['?UF_NAME'] = '%'.$searchString.'%';
            } elseif ($_REQUEST['user']) {
                $filter['?NAME'] = '%'.$searchString.'%';
            }
		}

        if(isset($_REQUEST['hlblock'])) {

            $hlblock = $_REQUEST['hlblock'];

            $entity_data_class = $parent->getEntityDataClass($hlblock);

            $rsData = $entity_data_class::getList(array(
                'select' => array('*'),
                "order" => array("ID" => "DESC"),
                'filter' => $filter,
                'limit' => 20,
            ));

            while ($i = $rsData->fetch()) {
                if($_REQUEST['type'] == 'iblock' && $_REQUEST['hlblock']) {
                    $elements[] = [
                        'ID' => $i['UF_XML_ID'],
                        'NAME' => '[' . $i['ID'] . '] ' . $i['UF_NAME'],
                        'URL' => '',
                    ];
                } elseif ($_REQUEST['type'] == 'hlblock' && $_REQUEST['hlblock']) {
                    $elements[] = [
                        'ID' => $i['ID'],
                        'NAME' => '['.$i['ID'].'] '.$i['UF_NAME'],
                        'URL' => '',
                    ];
                }
            }
        } elseif ($_REQUEST['user']) {
            $rsData = CUser::GetList(
                ($by="personal_country"),
                ($order="desc"),
                $filter,
            );

            while ($i = $rsData->fetch()) {
                $elements[] = [
                    'ID' => $i['ID'],
                    'NAME' => '('.$i['LOGIN'].') '.$i['NAME'].' '.$i['LAST_NAME'],
                ];
            }
        }

		break;
	}
}


header('Content-Type: application/json');
echo \Bitrix\Main\Web\Json::encode(array_values(array_filter($elements)));
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();