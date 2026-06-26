<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;

class NX_RegionList {
	private $region;
	private $title;
	private $mail;
	function NX_RegionList() {
		$this->region=array();
		$this->region["nn"] = 52;
		/*$this->region["piter"] = 47;
		$this->region["samara"] = 138;
		$this->region["ek"] = 139;
		$this->region["novosib"] = 140;
		$this->region["rostov"] = 141;
		$this->region["kazan"] = 142;
		$this->region["pyat"] = 143;
		$this->region["gus"] = 144;
		$this->region["ufa"] = 145;
		$this->region["msk_bal"] = 146;
		$this->region["msk_nahim"] = 147;
		$this->region["krasnodar"] = 225;
		$this->region["vladimir"] = 523;*/
		
		$this->title=array();
		$this->title[52] = "Нижний новгород";
		/*$this->title[137] = "Санкт-петербург";
		$this->title[138] = "Самара";
		$this->title[139] = "Екатеринбург";
		$this->title[140] = "Новосибирск";
		$this->title[141] = "Уфа";
		$this->title[142] = "Казань";
		$this->title[143] = "Пятигорск";
		$this->title[144] = "Гусь Хрустальный";
		$this->title[145] = "Уфа";
		$this->title[146] = "Москва Балашиха";
		$this->title[147] = "Москва Нахимовский";
		$this->title[225] = "Краснодар";
		$this->title[523] = "Владимир";*/
		
		$this->mail=array();
	
		/*$this->mail[136]="nn@electra.ru"; //nn
		$this->mail[137]="spb@electra.ru";   //piter
		$this->mail[138]="samara@electra.ru";  //samar
		$this->mail[139]="e-burg@electra.ru";  //ek
		$this->mail[140]="nsk@electra.ru"; //novosib
		$this->mail[141]="rostov@electra.ru";  //rostov
		$this->mail[142]="kazan@electra.ru";   //kazan
		$this->mail[143]="5gorsk@electra.ru";    //pyat
		$this->mail[144]="gus@electra.ru"; //gus
		$this->mail[145]="ufa@electra.ru";     //ufa
		$this->mail[146]="msk2@electra.ru"; //msk_bal
		$this->mail[147]="msk1@electra.ru"; //msk_nahim
		$this->mail[225]="kuban@electra.ru"; //kresnodar
		$this->mail[523]="vladimir@electra.ru"; //kresnodar	*/	
			
	}
	
	function GetList() {return $this->region;}
	function GetMailList() {return $this->mail;}
	function GetTitleList() {return $this->title;}
	function GetCode($id) {if (!$id) return false; $res = array_search($id, $this->region); if(!$res)  return 'nn'; else return $res;}
	function GetMailById($id) {if($this->mail[$id]) return $this->mail[$id]; else return "info@electra.ru";}
	function GetMailByCode($code) {if($this->region[$code] && $this->mail[$this->region[$code]]) return $this->mail[$this->region[$code]]; else return "info@electra.ru";}
	function GetTitleById($id) {if (!$id) return 'unknow';  if($this->title[$id]) return $this->title[$id]; else return "Нижний новгород";}
	function GetTitleByCode($code) {if($this->region[$code] && $this->title[$this->region[$code]]) return $this->title[$this->region[$code]]; else return "Нижний новгород";}
}

?>