<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;

class COrderElement {
	private $id = 0;
	private $price = 0;
	public  $count = 0;
	public  $name = 'untitled';
	public  $note = false;

	function __construct ($ID, $Price = 0, $Count = 0, $Name = 'untitled', $Note = '') { 
		if ($ID) {
			$this->id = $ID;
			if ($Price > 0) $this->price = $Price; else $this->price = 0;	
			if ($Count > 0) $this->count = $Count; else $this->count = 0;
			if (strlen($Name)>0) $this->name = $Name; else $this->name="untitled";
			if ($Note) $this->note = $Note; else $this->note="";
		}
	}

	function GetPrice() {return $this->price;}
	function GetSum()   {return ($this->price*$this->count);}
	function GetId()    {return $this->id;}
	function GetArray() {
		return array(
			'ID' => $this->GetId(), 
			'PRICE'=> $this->GetPrice(), 
			'COUNT'=> $this->count, 
			'NAME'=> $this->name, 
			'NOTE'=> $this->note
		);
	}
}

interface IOrder {
	public function Add($id, $count, $price, $note, $name);
	public function Replace($id, $count, $price, $note, $name);
	public function Change($id, $count, $price, $note, $name);
	public function Delete($id);
	public function GetById($id);
	public function InBasket($id);
	public function SomeInBasket($ids);
	public function GetSum();
	public function GetCount();
	public function GetListing();
	public function Clear();
	public function IsEmpty();
}
 
class COrder implements IOrder {
	
	private $OrderItems = array();

	function __construct ($Order) {
		if(is_array($Order)) {
			$this->OrderItems=array();
			foreach ($Order as $Item) {
				if ($Item["ID"]=intval($Item["ID"])) {
					$this->OrderItems[$Item["ID"]] = new COrderElement($Item["ID"], $Item["PRICE"], $Item["COUNT"], $Item["NAME"], $Item["NOTE"]); 
				}
			}
			return true;
		}
		if(!$Order) return true;
		return false;  
	}

	public function Add($id = false, $count=1, $price = 0, $note = false,  $name = false) { 
		if(is_int($id) && $count) { 
			if (array_key_exists($id, $this->OrderItems)){
				$this->OrderItems[$id]->count += intval($count);
				if($this->OrderItems[$id]->note != $note && $note) $this->OrderItems[$id]->note = $note;
				if($this->OrderItems[$id]->name != $note && $name) $this->OrderItems[$id]->name = $name;
			}
			else {
				$this->OrderItems[$id] = new COrderElement($id, floatval($price), floatval($count), $name, $note);
			}
			return true;
		}
		else return false;
	}

	public function Delete($id) { 
		unset ($this->OrderItems[intval($id)]);
		return true;
	}

	public function Replace($id=false, $count=1, $price=0, $note = false, $name = false) { 
		if($id=intval($id)) { 
			$this->Delete($id);
			return $this->Add($id, $count, $price, $note, $name);
		}  
		else return false;
	}
	
	public function Change($id = false, $count = false, $price = false, $note = false,  $name = false) { 
		$id = intval($id);
		if($this->InBasket($id)) { 
		    if($count = intval($count))   $this->OrderItems[$id]->count=$count;
			if($price = floatval($count)) $this->OrderItems[$id]->count=$price;
			if($note) $this->OrderItems[$id]->$note = $note;
			if($name) $this->OrderItems[$id]->$name = $name;	
			return true;
		}  
		else return false;
	}

	public function GetById($id) {
		if($res = $this->OrderItems[intval($id)])
			 return $res->GetArray();
		else return false;
	}
	 
	public function InBasket($id){
		if($res = $this->OrderItems[intval($id)])
			 return true;
		else return false;
	}
	
	public function SomeInBasket($ids){
		if(is_array($ids)){
			$result = array();
			foreach($ids as $id) {
				if($res = $this->OrderItems[intval($id)]) $result[] = $id;
			}
			if(count($result) > 0) return $result;
            else return false;			
		}
		else return false; 
	}

	public function GetSum() {
		$result = 0; 
		foreach ($this->OrderItems as $Item) { 
			$result += $Item->GetSum();
		} 
		return $result;
	}
	
	public function GetCount(){
		return count($this->OrderItems);
	}

	public function GetListing(){ 
		$result = array();
		foreach ($this->OrderItems as $Item) {	
			$result[$Item->GetId()] = $Item->GetArray();}
		return $result;
	}
	
	public function Clear(){ 
		$this->OrderItems = array();
	}

	public function IsEmpty(){
		if (count($this->OrderItems) > 0) return false; 
		else return true;
	}
}
?>