<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2017 Imageprom
 */

namespace NXMarket;

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

\CModule::IncludeModule("highloadblock");


class CNXOrderApi {

	private static $orderVar = array( 
		'order' => 'UF_ORDER',
		'id' => 'UF_ORDERS_ID',
		'date' => 'UF_DATE',
		'user' => 'UF_USER',
		'title' => 'UF_TITLE',
		'sum' => 'UF_SUM',
		'full_sum' => 'UF_FULL_SUM',
		'promo' => 'UF_PROMO',
		'discount' => 'UF_DISCOUNT',
		'payment' => 'UF_PAYMENT',
		'delivery' => 'UF_DELIVERY',
		'html' => 'UF_ORDER_HTML',
		'system_id' => 'ID',
		'status' => 'UF_STATUS',
		'site' => 'UF_SITE',
		'region' => 'UF_FILIAL',
		'comment' => 'UF_COMMENT',
		'kassa' => 'UF_KASSA',
	);

	private $HIB;
	private $status;
	private $entity;
	private $fields;

	public function __construct($HIB) {
		try {
			
			if(!$HIB) throw new \Exception('Empty HID');
			$this->HIB = intval($HIB);
			$hlblock = HL\HighloadBlockTable::getList(array('select'=>array('*'), 'filter'=>array('=ID' => $this->HIB)))->fetch();
			
			if(!$hlblock) throw new \Exception('Highloadblock not foud');
			$this->entity =  HL\HighloadBlockTable::compileEntity($hlblock);

			$rsData = \CUserTypeEntity::GetList(array($by=>$order), array("ENTITY_ID" => 'HLBLOCK_'.$this->HIB));
			while($arRes = $rsData->Fetch()) {
				$this->fields[$arRes['FIELD_NAME']] = $arRes['XML_ID'];

				if($arRes['FIELD_NAME'] == 'UF_STATUS') {

					$obEnum = new \CUserFieldEnum;
        			$rsEnum = $obEnum->GetList(array(), array('USER_FIELD_ID' => $arRes['ID']));
        			
        			while($arEnum = $rsEnum->Fetch()) {
        				$this->status[$arEnum['ID']] = $arEnum['XML_ID'];
        			}
				}
			}
		}

		catch (\Exception $e) {
	 		var_dump($e->getMessage());
	    }
	}

	public function GetLastOrder() {
		
		$main_query = new Entity\Query($this->entity);
		
		$main_query->setSelect(array('ID', self::$orderVar['id'], self::$orderVar['date']));
		$main_query->setOrder(array("ID" => "DESC")); 
		$main_query->setLimit(1);

		$result = $main_query->exec();
		$result = new \CDBResult($result);

		if($orderData = $result->Fetch()) {
			$orders[] = array(
				'id' => $orderData[self::$orderVar['id']],
				'date_create' => $orderData[self::$orderVar['date']]->format('d.m.Y H:i:s')
			);
		}

		return $orders;
	}

	public function GetOrderData($id) {

		$order_id = '='.self::$orderVar['id'];

		$main_query = new Entity\Query($this->entity);
		$main_query->setSelect(array('*'));
		$main_query->setFilter(array($order_id => $id));

		$result = $main_query->exec();

		$result = new \CDBResult($result);

		if($orderData = $result->Fetch()) {
			$result = array(
				'order_id' => $orderData[self::$orderVar['id']],
				'comment' =>  htmlspecialchars ($orderData[self::$orderVar['comment']]),
				'payment' =>  htmlspecialchars ($orderData[self::$orderVar['payment']]),
				'delivery' =>  htmlspecialchars ($orderData[self::$orderVar['delivery']]),
				'status' => $this->status[$orderData[self::$orderVar['status']]],
				'client' => array(), 
				'products' =>  array(), 
			);

			$result['comment'] = preg_replace("/[^a-zA-Zа-яА-ЯёЁ0-9@\*!?:\.,\-\+\(\)\ ]/u", "", $result['comment']);

			if($var = $orderData[self::$orderVar['promo']]) $result['promo'] = $var;
			if($var = $orderData[self::$orderVar['full_sum']]) $result['full_sum'] = $var;
			if($var = $orderData[self::$orderVar['discount']]) $result['discount'] = $var;

			$result['itog'] = $orderData[self::$orderVar['sum']];

			foreach ($orderData as $key => $value) {
				if(!in_array($key, self::$orderVar)) {
					$result['client'][$this->fields[$key]] = $value;
				}

				elseif($key == self::$orderVar['order'] && $value) {
					$result[$this->fields[$key]] = $this->ParseProducts($value);
				}

				elseif($key == self::$orderVar['date']) {
					$result[$this->fields[$key]] = $value->format('d.m.Y H:i:s');
				}

				elseif($key == self::$orderVar['user']) {
					$result['client'] = array_merge($this->GetUserData($value), $result['client']);
				}	
			}

			return $result;
		}

		return false;
	}

	public function ParseProducts($products) {
		try {
			$products = json_decode($products, true);
			if(!is_array($products )) throw new \Exception('Products can\'t parse');
			
			foreach ($products as $key => $value) {

				$value['NAME'] = html_entity_decode($value['NAME']);
				$value['NAME'] = str_replace('&', 'and', $value['NAME']);
				$value['NAME'] = preg_replace("/[^a-zA-Zа-яА-ЯёЁ0-9@\*!?:\.,\-\+\(\ ]/u", "", $value['NAME']);

				$orderItem = array(
					'id' => $value['NOTE']['ART'],
					'name' => $value['NAME'],
					'price' => $value['PRICE'],
					'count' => $value['COUNT'],
					'sum' => $value['PRICE']*$value['COUNT'],
					
				);

				if($value['NOTE']['WAREHOUSES']) {

					$warehouses = array();

					foreach ($value['NOTE']['WAREHOUSES'] as $war) {
						$warehouses[] = array(
							'id' => $war['ID'],
							'count' => intval($war['COUNT'])
						); 
					}

					$orderItem['warehouses'] = $warehouses;
				}

				$result[] = $orderItem;
			}

			return $result;
		}

		catch (\Exception $e) {
	 		var_dump($e->getMessage());
	    }
	}

	public function FindOrders($last_order = false, $region = false, $user_id = false, $date_from = false, $date_to = false, $nomanager = false) {

		try { 

			$main_query = new Entity\Query($this->entity);
			$main_query->setSelect(array('ID', self::$orderVar['id'], self::$orderVar['date'],  self::$orderVar['sum']));
			$main_query->setOrder(array("ID" => "DESC")); 
			$filter = array();

			$last_order = intval($last_order);
			$user_id = intval($user_id);

			if($last_order > 0) {
				$lastOrderId = '>'.self::$orderVar['id'];
				$filter[$lastOrderId] = $last_order;
			}

			if($region) {
				$regionList = new CNXRegionList();
				$regionId = $regionList->GetTailByCode($region);

				if(isset($this->fields[self::$orderVar['region']])) {

					$regionId = $regionList::GetRoznRegionId($regionId);
					$currentRegion = '='.self::$orderVar['region'];
					$filter[$currentRegion] = $regionId;
				}

				elseif(isset($this->fields[self::$orderVar['user']]) && !$user_id) {
					$users = self::GetUsersByRegion($regionId);
					$currentUsers = '='.self::$orderVar['user'];
					$filter[$currentUsers] = $users;
				}
			}

			if($user_id > 0) {
				if(isset($this->fields[self::$orderVar['user']])){
					$currentUsers = '='.self::$orderVar['user'];
					$filter[$currentUsers] = $user_id;	
				}

				else return array('not_found' => 1);
			}
			
			if($nomanager) {

				$optManager = array(1, 7, 10, 18, 6);
				$rsUser = \CUser::GetList(($by='ID'), ($order = 'desc'), array('GROUPS_ID' => $optManager), array('SELECT'=>array('ID')));
				while($User = $rsUser->GetNext()) {
					$arNoUser[] =  $User['ID'];
				}

				$currentUsers = '!'.self::$orderVar['user'];
				$filter[$currentUsers] = $arNoUser;	
			}

			if($date_from || $date_to) {

			

				if($date_from && $date_to) {
					if(!self::CheckDate($date_from)) throw new \Exception('Wrong format of data_from');
					if(!self::CheckDate($date_to))   throw new \Exception('Wrong format of data_to');

					$dateFrom = '>'.self::$orderVar['date'];
					$dateTo = '<'.self::$orderVar['date'];

					$filter[$dateFrom] = $date_from.' 00:00';
					$filter[$dateTo] = $date_to.' 23:59:59';
				}

				elseif($date_from) {
					if(!self::CheckDate($date_from)) throw new \Exception('Wrong format of data_from'); 
					$date = '>'.self::$orderVar['date'];
					$filter[$date] = $date_from.' 00:00';
				}
				else {
					if(!self::CheckDate($date_to)) throw new \Exception('Wrong format of data_to'); 
					$date = '<'.self::$orderVar['date'];
					$filter[$date] = $date_to.' 23:59:59';
				}
			}

			$main_query->setFilter($filter);
			$result = $main_query->exec();

			$result = new \CDBResult($result);

			while($orderData = $result->Fetch()) {
				$orders[] = array(
					'id' => $orderData[self::$orderVar['id']],
					'date_create' => $orderData[self::$orderVar['date']]->format('d.m.Y H:i:s'),
					'sum' => $orderData[self::$orderVar['sum']],
				);
			}

			if($result->SelectedRowsCount() == 0) return array('not_found' => 1);
			return $orders;
		}

		catch (\Exception $e) {
	 		var_dump($e->getMessage());
	    }
	}

	public static function CheckDate($date) {
		$date_arr= explode('.', $date);
		if (checkdate($date_arr[1], $date_arr[0], $date_arr[2])) {
		    return true;
		}
		return false;
	}

	public static function GetUsersByRegion($region) {
		try {
			if(!$region) throw new \Exception('Empty region');
			$rsUser = \CUser::GetList(($by="ID"), ($order="desc"), array( "UF_REGION" => $region),array("SELECT"=>array("ID")));

			while ($arUser = $rsUser->Fetch()) {
				$result[] = $arUser['ID'];
			}
			return $result;
		}

		catch (\Exception $e) {
	 		var_dump($e->getMessage());
	    }
	}

	public function GetUserData($user) {
		try {
			
			if(!$user) throw new \Exception('Empty user');
			
			$rsUser = \CUser::GetList(($by="ID"), ($order="desc"), array("ID"=>$user),array("SELECT"=>array("UF_*")));
			if ($arUser = $rsUser->Fetch()) {

				if($arUser['UF_REGION'][0]) {
					$region = new CNXRegion($arUser['UF_REGION'][0]);
					$region_data = $region->GetData();

				}

				$result = array(
					'user_id' => $arUser['ID'],
					'last_name' => $arUser['LAST_NAME'],
					'name' => $arUser['NAME'],
					'second_name' => $arUser['SECOND_NAME'],
					'company' => $arUser['WORK_COMPANY'],
					'phone' => $arUser['WORK_PHONE'],
					'email' => $arUser['EMAIL'],
					'region_id' => $arUser['UF_REGION'][0],
					'region' => $region_data['title'],
					'city' => $arUser['WORK_CITY'],
					'inn' => $arUser['UF_INN'],
				);

				foreach ($result as &$field) {
					$field = preg_replace("/[^a-zA-Zа-яА-ЯёЁ0-9@\*!?:\.,\-\+\(\ ]/u", "", $field);
				}

			}
			
			return $result;
		}

		catch (\Exception $e) {
	 		var_dump($e->getMessage());
	    }
	}
}
?>