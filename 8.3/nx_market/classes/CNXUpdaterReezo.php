<?/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2019 Imageprom
 */

namespace NXMarket;
define('NXIDREG', '/^[_0-9a-zA-Z]+$/u');

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Highloadblock as HL;

class CNXUpdaterReezo {

	private $source;
	private $arOffers;
	private $arExistOffers;
	private $arItems;
	private $arItemsId;
	private $arItemsOffers;

	private $HIB;
	private $IB;
	private $SR;

	private $rsHIBlock;
	private $entity;
	private $entityDataClass;

	private $stat;

	function __construct($file, $hib, $ib, $sr) {
		if(is_readable($file)) {

			$this->source =  $file;
			$this->HIB = intval($hib);
			$this->IB = intval($ib);
			$this->SR = $sr;

			$this->rsHIBlock = HL\HighloadBlockTable::getList(array('select'=>array('*'), 'filter'=>array('ID' => $this->HIB)));
			$this->entity = HL\HighloadBlockTable::compileEntity($this->rsHIBlock->Fetch());
			$this->entityDataClass = $this->entity->getDataClass();

			$this->stat = array(
				'Suscess' => false, 
				'Add' => 0,
				'Update' => 0,
			);
 		} 
	}

	protected  function fgetcsv($f, $length = 10000, $d = ',', $q ='"') {
	    $list = [];
	    $st = fgetcsv($f, 0, $d);
	    foreach ($st as $key => $field) {
	    	$field = mb_convert_encoding($field, "utf-8", "windows-1251");
	    	trim ($str, $q);
	        $list[] = $field;
	    }

	    return $list;
	}

	protected  function fgettxt($f) {
	    $q ='"';
	    $list = [];
	    $st = fgetcsv($f, 0, '|');
	    foreach ($st as $key => $field) {
	    	//$field = mb_convert_encoding($field, "utf-8", "windows-1251");
	    	trim ($str, $q);
	        if($key%2 ==0) $list[] = $field;
	    }

	    return $list;
	}

	protected function addOffer($offer) {

		$code = $offer['UF_ART'];

		$result = $this->entityDataClass::add($offer);

		if ($result->isSuccess()) {
			NXMessages::WriteLog(NXMessages::FormatLogMessage('Товарное предложение '.$code.' добавлено', '#MESSAGE#', true));
			$this->stat['Add']++;

			return true;
		}
		return false;  
	}

	protected function updateOffer($offer, $id) {

		$code = $offer['UF_ART'];	
		unset($offer['UF_XML_ID']);
		
		$result = $this->entityDataClass::update($id, array('UF_PRICE' => $offer['UF_PRICE']));

		if ($result->isSuccess()) {
			NXMessages::WriteLog(NXMessages::FormatLogMessage('Товарное предложение '.$code.' обновлено', '#MESSAGE#', true));
			$this->stat['Update']++;

			return true;

		}
		return false;  
	}

	protected function updateItem($id, $value) {
		if(!$id || !$value) return false;

		$itemId = $this->arItems[$id]['ID'];

		if($itemId) {
			$el = new \CIBlockElement; 
			$el->SetPropertyValuesEx($itemId, false, array('OFFERS' => $value));

			NXMessages::WriteLog(NXMessages::FormatLogMessage('Товар '.$itemId.' обновлен', '#MESSAGE#', true));

			return true;
		}

		return false;
	}

	protected function getItems() {
		$arSelect = Array('IBLOCK_ID', 'ID', 'XML_ID', 'NAME');
		$arFilter = Array('IBLOCK_ID'=> $this->IB, 'XML_ID' => $this->arItemsId);
		$res = \CIBlockElement::GetList(Array(), $arFilter, false,false, $arSelect);

		while($arItem = $res->GetNext()) {
		 	$this->arItems[$arItem['XML_ID']] = $arItem;
		}
	}

	protected function getExistOffers() {
		
		$main_query = new Entity\Query($this->entity);
		$main_query->setSelect(Array('ID', 'UF_XML_ID'));
		$offerRes = $main_query->exec();
		$offerRes = new \CDBResult($offerRes);

		while ($offer = $offerRes->GetNext()) {
			$this->arExistOffers[$offer['UF_XML_ID']] = $offer['ID'];
		}
	}

	protected function readFile() {

		if(!$this->source) return false;

		$f = fopen($this->source, "rt") or die("Ошибка!");

		while ($data = $this->fgetcsv($f)) {


			$price = floatval(str_replace(',', '.', $data[4]));

			$arOfferFields = array(
				'ITEM_ID' => $data[0],
				'UF_NAME' => $data[1], 
				'UF_PRICE' => $price,
				'UF_ART' => $data[2],   
 			);

 			$this->arOffers[] = $arOfferFields;
 			$this->arItemsId[] = trim($arOfferFields['ITEM_ID']);
		}

		$this->arItemsId = array_unique($this->arItemsId);

		if(count($this->arItemsId) > 0) return true;
	}


	public function Update() {
		
		if(!$this->readFile()) return false;

		NXMessages::WriteLog(NXMessages::FormatLogMessage('=== Обновление начато ===', '#MESSAGE#', true));

		$this->getItems();
		$this->getExistOffers();

		foreach ($this->arOffers as $arOffer) {
		
			$offerXmlId = array_search($arOffer['ITEM_ID'], $this->arExistOffers);
			if($offerXmlId) {
				if($this->updateOffer($arOffer, $arOffer['ITEM_ID'])) {
					$this->$arItemsOffers[$id][] = $arOffer['UF_UF_ART'];
				}
			}

			else {

				// if($this->addOffer($arOffer)) {
				// 	$this->$arItemsOffers[$id][] = $arOffer['UF_XML_ID'];
				// }
			}
			
		}

		// foreach ($this->$arItemsOffers as $id => $arOffer) {
		// 	$this->updateItem($id, $arOffer); 
		// }

		$this->stat['Suscess'] = true;
		NXMessages::WriteLog(NXMessages::FormatLogMessage('=== Обновление завершено ===', '#MESSAGE#', true));	
	}

	public function GetStat() {
		header('Content-Type: application/json');
		echo json_encode(array('Update' => $this->stat));
	}
}