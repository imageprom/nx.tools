<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;

class CNX_tails_creator {

    private $table_name;
	private $status_table_name;
	private $status_id;
	
	private $ib;
	private $ib_type;
	
	private $errors;
	private $warnings;
	private $db;
	
	private $property_code;
	private $tails_id;
	
	private $region;
	private $fc;
	private $prefix;

	function __construct($db, $ib, $ib_type, $table_name, CNX_file_controler $fc, $prefix=false) {
	   
	   //Data Base
	   $this->status_table_name = "nx_update_status";
	   $this->status_id=intval($ib);
	   $this->db=$db;
	   $this->table_name=$table_name;	    
	   $this->property_code="ost_";
	   
	   //Iblock
	   $this->ib=intval($ib);
	   $this->ib_type=$ib_type;

	   $this->fc = $fc;
	   
	   $reg = new NX_RegionList();
	   $this->region = $reg->GetList();
     
	   if(!$prefix) $this->$prefix = $table_name;
	   else $this->prefix = $prefix;
 	   
	}
	
	function CheckStatus() {
		 
		$strSql="select STATUS, MODIFIED from ".$this->status_table_name." WHERE ID=".$this->status_id.";";
		$res = $this->db->Query($strSql, false);
		if($status=$res->Fetch())  { 
	 
			if(AddToTimeStamp(array("MI"=>160), strtotime($status["MODIFIED"])) < time()) { 
				$this->SetStatus(0); 
				return false;
			}
			return $status["STATUS"];				  
		}
        else return false;
	}
	
	function SetStatus ($status) {
		$strSql="update ".$this->status_table_name." set STATUS = ".intval($status)." WHERE ID=".$this->status_id.";";
		$res = $this->db->Query($strSql, false);
	}
	
	function GetTailsId() {
		$strSql = "select ID, CODE from b_iblock_property where IBLOCK_ID=".$this->ib." and CODE like 'ost_%';";
		$res = $this->db->Query($strSql, false);
		while($property=$res->Fetch()) $this->tails_id[$property["CODE"]] = $property["ID"];
	
	}
	
	function GetErrors() {
		if (count($this->errors)==0) return false;
		else return $this->errors;
	}
	
	function GetWarnings() {
		if (count($this->warnings)==0) return false;
		else return $this->warnings;
	}
	
	function ClearTable() {
		$strSql = "delete from ".$this->table_name.";";
		$res = $this->db->Query($strSql, false); 
	}
	
	
	private function setCollValue($coll, $val, $id=false) {
		if ($val<0) $val=0; // отрицательные числа попадаются у сережи в выгрузке
		if($id) $strSql = "update ".$this->table_name." set ".$coll."=".$val." where XML_ID=".$id.";";
		else $strSql = "update ".$this->table_name." set ".$coll."=".$val.";";
		$res = $this->db->Query($strSql, false); 
	}
	
	
	function GetCurrentTails($not_clear = false) {

			$this->ClearTable();
			$this->GetTailsId();
			$join="";
			
			$tbl_head="ID, XML_ID";
			foreach ($this->tails_id as $code=>$id) {
				$tbl_head.=", ".$code;
				$join.="left join 
						(SELECT IBLOCK_PROPERTY_ID, IBLOCK_ELEMENT_ID, VALUE as ".$code." FROM   b_iblock_element_property where  IBLOCK_PROPERTY_ID=".$id.") as t_".$code."
						on tbl_ids.ID=t_".$code.".IBLOCK_ELEMENT_ID 
					   ";
			}

			$strSql = "insert into ".$this->table_name." (".$tbl_head.")
						   (select ".$tbl_head." from (select XML_ID, ID, IBLOCK_ID from b_iblock_element where IBLOCK_ID=".$this->ib.") as tbl_ids
							".$join." ORDER BY ID);";
							
			$res = $this->db->Query($strSql, false);
			return true;
		
	}
	
	function UpdateTailsList() {
	
	    $strSql = "delete from ".$this->table_name." where ID NOT IN (select ID from  b_iblock_element where IBLOCK_ID=".$this->ib.");";
	    $res = $this->db->Query($strSql, false);
	
		$strSql= "insert into ".$this->table_name." (ID, XML_ID)
						   (select ID, XML_ID from b_iblock_element where IBLOCK_ID=".$this->ib." and ID NOT IN (select ID from  ".$this->table_name."))
							;";
		$res = $this->db->Query($strSql, false);
	}
	
	
	function CreateEmptyStructure() {
		
			$this->ClearTable();
			$strSql = "insert into ".$this->table_name." (ID, XML_ID) (select ID, XML_ID from b_iblock_element where IBLOCK_ID=".$this->ib.");";
			$res = $this->db->Query($strSql, false);
			return true;
			
	}
	
	function GetFullTailsFromXML() {

				foreach($this->region as $town=>$id)  {
					$this->updateTails ($town, $id);
					if($this->fc->Archive($town, $this->prefix.'_'.$town)) $this->fc->Delete($town);
			        else $this->warning[] = "Не удалось скопировать в архив файлы";
				}
			
				return true;

	}
    
	private function updateTails ($town, $id) {
		if($tails=$this->fc->GetFile($town)) {
			foreach ($tails["Product"] as $Product) {
						$this->setCollValue($this->property_code.$id, intval($Product["Ostatok"]), intval($Product["ID"]));
					}					
			return true;
		}	
		return false;
	}
	
	
	function SingleUpdate($town, $no_stuct=false) {
	CModule::IncludeModule("iblock");
	
		if($id=$this->region[$town]) {		    
			
			if(!$no_stuct) $this->CreateEmptyStructure();
			 
				if($this->updateTails ($town, $id)){
				    echo "-";
					$strSql = "select ID, ".$this->property_code.$id." as VALUE from ".$this->table_name.";";
					$res = $this->db->Query($strSql, false);
					while($ob = $res->Fetch()) {
						CIBlockElement::SetPropertyValuesEx($ob["ID"], false, array("ost_".$id => $ob["VALUE"]));		
					}
				
				    if($this->fc->Archive($town,  $this->prefix.'_'.$town)) $this->fc->Delete($town);
			        else $this->warning[] = "Не удалось скопировать в архив файлы";
				
					$this->ClearCache();
					return true;
				
				}

				return false;			
		}
	}
	
    function FullLocalUpdate() {
	CModule::IncludeModule("iblock");
							
				$strSql = "select * from ".$this->table_name.";";
				$res = $this->db->Query($strSql, false);
				while($ob = $res->Fetch()) {
					$update=array();
					foreach ($ob as $code=>$val) if($code!="ID" && $code!="XML_ID") $update[$code]=$val;
					CIBlockElement::SetPropertyValuesEx($ob["ID"], false, $update);		
				}
				
				$this->ClearCache();
				
				return true;
			
		
	}
	
	function ClearCache() {
	
		BXClearCache(true, "/bitrix/cache/s1/bitrix/catalog.section.list/");
		BXClearCache(true, "/bitrix/cache/s1/bitrix/catalog.section");
		BXClearCache(true, "/bitrix/cache/s1/bitrix/catalog.element");
		
		BXClearCache(true, "/bitrix/cache/s2/bitrix/catalog.section.list/");
		BXClearCache(true, "/bitrix/cache/s2/bitrix/catalog.section");
		BXClearCache(true, "/bitrix/cache/s2/bitrix/catalog.element");
	
	}
	
}

?>