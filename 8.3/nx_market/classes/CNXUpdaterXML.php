<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;
define('NXIDREG', '/^[_0-9a-zA-Z]+$/u');

class CNXUpdaterXML implements INXUpdaterXML {

	/*SOURCE*/
	private $arResult;
	private $prefix;
	private $name;
	
	private $RESULT;

	/*IB Setting*/
    private $IB;
   
	public $propEnumValues;
	private $property_list;

	private $section_list;
	private $section_list_count;
	private $section_list_el_count;
	private $current_section_list;
	private $element_list;

	private $lineProps;
	private $regProps;
	private $specialProps;
	
	private $localSpecialProperties;
	private $localRegProperties;

	private $picture_list;
	private $elements;
	private $deactiveElements;
	
	private $region_list;
	private $regionCodesList;
	private $regionPropCodesList;
	
	private $LOG;

	//private $source;
	//private $element_ost_list;

    public function __construct ($ib_id, $ib_name, $prefix) {

		$this->RESULT = array();

		$this->IB = intval($ib_id);
		$this->prefix = $prefix;
		
        $this->RESULT['DATE_START'] = microtime(true);
        $this->RESULT['FAILED'] = false;

		$this->localSpecialProperties = array();
		$this->localRegProperties = array(); 

		$this->lineProps = false;
		$this->regProps  = false;
		$this->specialProps = false;    
		
		$this->propEnumValues = array();
	    $this->property_list = array();

	    $this->section_list = array();
	    $this->section_list_count = array();
	    $this->section_list_el_count = array();
	    $this->current_section_list = array();
	  
	    $this->picture_list = array();

	    $this->elements = array();
	    $this->deactiveElements = array();

        $this->region_list = array();
        $this->regionCodesList = array();
        $this->regionPropCodesList = array();
		
		$this->LOG = false;

	}
	
	public function WriteLog() {
		$this->LOG = array(
			'LOG' =>new NXMessages('UpdateLog', 0), 
			'ERROR' =>new NXMessages('UpdateErrors', 2), 
			'WARNING' => new NXMessages('UpdateWarning', 1)
		);
	}
	
	private function repotr (\Exception $e) {
		if(!is_array($this->LOG)) return false;
		

		switch ($e->getCode()) {
			case '1':
				$this->LOG['WARNING']->AddMessage($e);
				break;
			case '2':
				$this->LOG['ERROR']->AddMessage($e);
			default:
				$this->LOG['LOG']->AddMessage($e);
				break;
		}
	} 

    public function GetIBProperties() {
    	$properties = \CIBlockProperty::GetList(Array('sort'=>'asc'), array('IBLOCK_ID'=>$this->IB));
		while ($prop_fields = $properties->GetNext()) {   
		   $this->property_list[$prop_fields['ID']] = $prop_fields['CODE'];

		}
		unset($properties);
    }

    public function GetIBSections() {
		$sections = \CIBlockSection::GetList(Array('sort'=>'asc'), Array('IBLOCK_ID'=>$this->IB), true);
		while($sect_fields = $sections->GetNext()) {   
			$this->section_list[$sect_fields['ID']] = $sect_fields['XML_ID'];
		}

		unset($sections);
    }

    public function GetIBElements() {
    	if(count($this->current_section_list)) {
			$this->getLostElement();
    	}
    }

    private function getLostElement($ID = 0) {
    	
		$arSelect = array('IBLOCK_ID', 'ID', 'NAME', 'XML_ID', 'DETAIL_PICTURE', 'ACTIVE');

		if($ID > 0) $arFilter = array('IBLOCK_ID' => $this->IB, 'XML_ID' => $ID);
		else $arFilter = array('IBLOCK_ID' => $this->IB, 'SECTION_ID' => array_keys ($this->current_section_list));
		
		$codes = implode(', ', $this->regionCodesList);
		$elements = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
		while($elementFields = $elements->GetNextElement()){
			$element_fields = $elementFields->GetFields();
			$element_properties = $elementFields->GetProperties();

			$this->element_list[$element_fields['ID']] = $element_fields['XML_ID'];
 
			if($element_fields['ID'] && $element_fields['DETAIL_PICTURE']) 
				$this->picture_list[$element_fields['ID']] = $element_fields['DETAIL_PICTURE'];	
 
			if($element_fields['ACTIVE'] == 'N') $this->deactiveElements[$element_fields['ID']] = $element_fields['XML_ID'];
            $regValues = array();
            
			foreach($this->regProps as $reg_code => $reg_prop) {		
				foreach ($this->region_list as $reg_id => $region) {
					//$regValues[$reg_code.'_'.$reg_id] = $element_fields['PROPERTY_'.strtoupper($reg_code).'_'.$reg_id.'_VALUE'];
					$regValues[$reg_code.'_'.$reg_id] = $element_properties[$reg_code.'_'.$reg_id]['VALUE'];
				}
			}
			$this->localRegProperties[$element_fields['ID']] = $regValues;
		}



		unset($element_fields);
		unset($elements);
    }

    public function GetIBPictures() {
    	if(count($this->picture_list)) {
    		$picIds = implode(",", $this->picture_list);
    		$pictures = \CFile::GetList(array('ID' => 'asc'), array('MODULE_ID' => 'iblock', '@ID' => $picIds ));
			while($picture_field = $pictures->GetNext()) {
				$id = array_search($picture_field['ID'], $this->picture_list);
				$this->picture_list[$id] = $picture_field['FILE_SIZE'];
			}
			unset($pictures);
    	}
    }

    public function ParseXML(INXFileManager $fileControler) {
    	try {
    		$source = $fileControler->GetXmlFile();

    		if (file_exists($source)) {
    			$Source = file_get_contents($source);
				$tmp = xmlstr_to_array($Source);
				$this->arResult = $tmp;
			
				if( !isset($this->arResult['Brands']) 
			  	  && isset($this->arResult['Proizvoditel'])) {
					$this->arResult['Brands'] = $this->arResult['Proizvoditel'];
					unset($this->arResult['Proizvoditel']);
				}

				if(is_array($this->arResult)) {
					return true;
				} 
				else throw new \Exception("Failed parse xml", 2);
			}	

			else throw new \Exception("Can't read ".$source, 2);
    	}	

    	catch (\Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);
			return false;
		} 

    }
   
    private function updateEnumValues($prop_id, $prop_code, $values) {

    	$property_enums = \CIBlockPropertyEnum::GetList(array('SORT' => 'ASC'), array('IBLOCK_ID'=>$this->IB, 'PROPERTY_ID'=>$prop_id));
			
		$values_list = array();

		while($enum_fields = $property_enums->GetNext()) {
		  $values_list[$enum_fields['ID']] = $enum_fields['VALUE'];
		}
    
        $ibpenum = new \CIBlockPropertyEnum;
					
        foreach ($values as $val) {
        	if(!$enum_id = array_search($val['VALUE'], $values_list)) {

        		if($PropID = $ibpenum->Add(array('PROPERTY_ID'=>$prop_id, 'VALUE'=>$val['VALUE']))) {		
					$this->propEnumValues[$prop_code][$PropID] = $val['VALUE']; 
        		}
			} 
        	else $this->propEnumValues[$prop_code][$enum_id] =$val['VALUE'];
        }

        unset($property_enums);
        unset($ibpenum);
        return true;   
    } 


     private function isPropertyExist($propertyName, $arPropertyFields) {
		try {
			$ibp = new \CIBlockProperty;
    		
			if($propertyId = array_search($propertyName, $this->property_list)) {
				if(!$ibp->Update($propertyId, $arPropertyFields)) {
					throw new \Exception("Failed update property ".$propertyName, 2);
				}
				else return $propertyId;
			}
		
			elseif(!$propertyId = $ibp->Add($arPropertyFields)) {
				throw new \Exception("Failed add property ".$propertyName, 2);
			}

			return $propertyId;
    	}	

    	catch (\Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);
			return false;
		} 
	}
	
	
	private function generatePropertyValues($source, $propertyName) {
		try {

			$VALUES = array();
			
			if (isset($source['Item'])) {$source = $source['Item'];	unset($source['Item']);}
			elseif(!is_array($source[0])) $source = array(0 => $source);
			
			$this->localSpecialProperties[$propertyName] = array();

			//print_r($source);
			foreach($source as $cnt => $item) {
				     	
					$item['ID'] = trim($item['ID']);
					
					if (!preg_match(NXIDREG, $item['ID'])) {	
						throw new \Exception("Wrong symbol in ID of [".$propertyName."]=".$item['ID'], 2);						
					} 			
					else { 
	                    $this->localSpecialProperties[$propertyName][$item['ID']] = $item['Name'];
						$VALUES[] = array('VALUE' => $item['Name'], 'XML_ID' => $item['ID']);		     
					}
			}
			return $VALUES;
    	}	

    	catch (\Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);
		} 
	}

	
	
	public function UpdateSpecialProperty($code, $source, $name, $values, $sort = 1000) {
		try {

			if((!$this->RESULT['FAILED']) && $code && $source && $values) {
				
				$arPropertyFields = array('IBLOCK_ID' => $this->IB, 'ACTIVE' => 'Y', 'PROPERTY_TYPE'=> 'L',	'NAME' => $name, 'CODE' => $code, 'SORT' => $sort);	  

				if(is_array($values)) {
					$VALUES = $values;
					foreach ($values as $key => $value) {
						$this->localSpecialProperties[$code][$value['XML_ID']] = $value['VALUE'];
					}
					
				}
				else $VALUES = $this->generatePropertyValues($this->arResult[$values]['Item'], $arPropertyFields['CODE']);
				if(!($property_id = $this->isPropertyExist($arPropertyFields['CODE'], $arPropertyFields))) { 
					throw new \Exception("Field update erorr ".$name, 2);
				}

				else {

					if($this->updateEnumValues($property_id, $arPropertyFields['CODE'], $VALUES))  {
						$this->repotr(new \Exception("Update field ".$name, 0));
						$this->specialProps[$arPropertyFields['CODE']] = $source;	
					}
				}

				unset($this->arResult[$values]);
				return true;
			}
    	}	

    	catch (\Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);
			return $e;
		} 

	}

	private function updateLineProperty($prop, $code, $defaultSort = 700) {
		try{

			$arPropertyFields = array('IBLOCK_ID' => $this->IB, 'ACTIVE' => 'Y');
			$arPropertyFields['CODE'] = $code;
			$arPropertyFields['NAME'] = $prop['NAME'];
			if(!($sort = intval($prop['SORT']))) $sort = $defaultSort;
			$arPropertyFields['SORT'] = $sort;
			if(!$prop['PROPERTY_TYPE']) $prop['PROPERTY_TYPE'] = 'S';
			$arPropertyFields['PROPERTY_TYPE'] = $prop['PROPERTY_TYPE'];
			if($prop['USER_TYPE']) $arPropertyFields['USER_TYPE'] = $prop['USER_TYPE'];

			if(!($property_id = $this->isPropertyExist($arPropertyFields['CODE'], $arPropertyFields))) { 
				throw new \Exception("Error update of property ".$arPropertyFields['NAME'], 2); 
			}

			if($arPropertyFields['PROPERTY_TYPE']=='L' && is_array($prop['VALUES']))  {
						if($this->updateEnumValues($property_id, $arPropertyFields['CODE'], $prop['VALUES']))  {	
						   $this->repotr(new \Exception("Add property  values for ".$prop['NAME'], 0));
						}
			} 

			return true;
		}
		catch (\Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);
			return false;
		} 
	}


	public function UpdateLineProperties($line_props = false) {
		if(is_array($line_props)) {
			foreach($line_props as $code => $prop) {			
				if(!$property_id = array_search($code, $this->property_list)) {
					$this->updateLineProperty($prop, $code, 500);
				}
 				$this->lineProps[$code] = $prop;
			}

			
		}
	}

	public function UpdateRegionProperties($reg_props = false) {
		$cRegion = new NX_RegionList();
	    $my_region = $cRegion->GetList();
	    foreach ($my_region as $code => $reg_id) {
	    	$this->region_list[$reg_id] = $reg_id;	
	    }

		if(is_array($reg_props)) {
			foreach($reg_props as $code => $prop) {	
				$name = $prop['NAME']; 	
				foreach ($this->region_list as $reg_id => $reg_name) {
					$reg_code = $code.'_'.$reg_id;
					$prop['NAME'] = $name.' '.array_search($reg_name, $my_region);
					if(!$property_id = array_search($code, $this->property_list)) {
						$this->updateLineProperty($prop, $reg_code);
					}
					$this->regProps[$code] = $prop;
					$this->regionCodesList[] = $reg_code;
					$this->regionPropCodesList[] = 'PROPERTY_'.$reg_code;
				}
				
			} 
		}
	}
	
    private function updateProperty($prop, $cnt) {  	
    	try {
    		$VALUES = array();

    		if (!preg_match(NXIDREG, $prop['ID'])) {
				throw new \Exception("Incorrect symbol ".$prop['ID']." in the ID of property ".$prop['ID'], 2);
			}

			else {
                $sort = intval($prop['GroupProperty'])*100;
                if(!$sort) {
                	$this->repotr(new \Exception("Property group required in ".$prop['ID'], 1));
                	$sort = 200; 
                	if($prop['ID'] == 'Torgovaya_marka') $sort = 1200; 
                }


				$arPropertyFields = array ('IBLOCK_ID' => $this->IB, 'ACTIVE' => 'Y', 'SORT' => $sort,
										   'NAME' => $prop['Name'],  'CODE' => $prop['ID']);

				if(count($prop['Values']) <= 0) {
					if($prop['Type']=='Number') $arPropertyFields['PROPERTY_TYPE'] = 'N';		
					else $arPropertyFields['PROPERTY_TYPE'] = 'S';
				}
				else {
					$arPropertyFields['PROPERTY_TYPE'] = 'L';
					if(!is_array($prop['Values']['Value'])) $prop['Values']['Value'] = array($prop['Values']['Value']);
				
					foreach ($prop['Values']['Value'] as $pcnt => $prop_value){
						if(is_array($prop_value)) $prop_value = 0;
						$val_id = $prop['ID'].'_'.$pcnt;
						$VALUES[] = array(
						  'VALUE' => $prop_value,
						  'XML_ID' => $val_id,
						  'DEF' => 'N',
						  'SORT' => ($pcnt*10),
						);
					}
				}	

				if(!($property_id = $this->isPropertyExist($arPropertyFields['CODE'], $arPropertyFields))) { 
					throw new \Exception("Error update of property ".$arPropertyFields['NAME'], 2); 
				}
				else {
					if($arPropertyFields['PROPERTY_TYPE']=='L') {
						if($this->updateEnumValues($property_id, $arPropertyFields['CODE'], $VALUES))  {	
							$this->repotr(new \Exception("Update valuef of property ".$arPropertyFields['NAME'], 0));
							return true;
						}
					} 
					else { 
						$this->repotr(new \Exception("Update property ".$arPropertyFields['NAME'], 0));
						return true;
					}
				}	
            }
    	}

    	catch (\Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);
			return false;
		} 
    }


	public function UpdateProperties () {	
			if(!$this->RESULT['FAILED']) {
		        $propertyCount = 0;
				foreach($this->arResult['Properties']['Property'] as $cnt=>$prop){
					if($this->updateProperty($prop, $cnt)) $propertyCount++;
				}
				return $propertyCount;
				unset($this->arResult['Properties']);
			}
			return false;
	}

    private function updateSection($sect, $parent_id = false, INXImageControler $imageControler) {
    	try {
    		
	    	$bs = new \CIBlockSection;

			$arSectFields = array(
				'IBLOCK_ID' =>$this->IB,
				'IBLOCK_SECTION_ID' => 0,
				'ACTIVE' => 'Y', 
				'XML_ID'=>$sect['ID'],
				'NAME' => $sect['Name'],
				'SORT' => $sect['Sort'],
				'CODE' => $sect['Code'],
				
			 );


			if(is_array($sect['Opisanie'])) $sect['Opisanie'] = false;
			if($sect['Opisanie']) {
				$arSectFields['DESCRIPTION'] = $sect['Opisanie'];
				$arSectFields['DESCRIPTION_TYPE'] = 'html';
			}



			if(is_array($sect['Filter']['Prop'])) {
	       		foreach ($sect['Filter']['Prop'] as $count => $value) {
					$arSectFields['UF_FILTER'][] = $value;	
				}
			}

			if($parent_id) $arSectFields['IBLOCK_SECTION_ID'] = $parent_id;

	        if(is_array($sect['Picture'])) $sect['Picture'] = false;
			
			if($sect['Picture'] && ($source = $imageControler->GetPreview($sect['Picture']))) {
				$arSectFields['PICTURE'] = CFile::MakeFileArray($source); 
			}
			
			else if($sect['Picture']) {
				$this->repotr(new \Exception("Can't find section picture ".$sect['Picture'], 2));
			}
		    
		    if($section_id = array_search($sect['ID'], $this->section_list)) { 
		    	$this->current_section_list[$section_id] = $sect['ID'];	

				if(!$bs->Update($section_id, $arSectFields)) throw new \Exception('Error of update section '.$sect['ID'].' '.$sect['Name'].' '.$bs->LAST_ERROR, 2);
				else {
					$this->repotr(new \Exception('Update section '.$sect['ID'].' '.$sect['Name'], 0));	
				}
				$this->RESULT['UPDATE_SECTION']++;
			}

			else  {		
 
				if(!($section_id = $bs->Add($arSectFields))) {  throw new \Exception("Error of add section ".$sect['Name'].' '.$bs->LAST_ERROR, 2);}
				else {
					$this->repotr(new \Exception("Update section ".$sect['Name'], 0));
					$this->section_list[$section_id] = $sect['ID'];
					$this->current_section_el_list[$section_id] = $sect['ID'];
					$this->RESULT['NEW_SECTION']++;
				}
			}

			unset ($bs);	
		}
		catch (\Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);
		} 

    }

    public function UpdateSections(INXImageControler $imageControler) {

    	if(!$this->RESULT['FAILED']) {
    		
	    	//$this->RESULT['NEW_SECTION'] = 0;
			//$this->RESULT['UPDATE_SECTION'] = 0;

			if(!is_array($this->arResult['Sections']['Section'][0]) ) {
				$tmps = $this->arResult['Sections']['Section']; 
				$this->arResult['Sections']['Section'] = array();   
				if($tmps) $this->arResult['Sections']['Section'][0] = $tmps; 
			}
			
			foreach($this->arResult['Sections']['Section'] as $cnt=>$sect) {
				if(!$sect['ParentSection']) {		
	                $this->updateSection($sect, false, $imageControler);
				}				
			}

			foreach($this->arResult['Sections']['Section'] as $cnt=>$sect) {
				if($sect['ParentSection']) {
				    if($parent_id = array_search($sect['ParentSection'], $this->section_list))		
	                $this->updateSection($sect, $parent_id, $imageControler);
	                else $this->updateSection($sect, false, $imageControler);
				}			
			}

			unset($this->arResult['Sections']);
		}
    }


    private function updateElement($tovar, $cnt, INXImageControler $imageControler) {
		try{   	

			$el = new \CIBlockElement;
	    	if(!array_search($tovar['ID'], $this->element_list)) { 
			    $this->getLostElement($tovar['ID']);
			}

			$arElementField = array('IBLOCK_ID' => $this->IB, 'ACTIVE' => 'Y', 'SORT' => '500',
					  				'NAME' => $tovar['Name'], 'XML_ID' => $tovar['ID'], 'CODE' => $tovar['ID'],
					  				'DATE_ACTIVE_FROM' => FormatDate('d.m.Y H:i:s', MakeTimeStamp($tovar['Sort_date'], 'DD.MM.YYYY')),       
									);

			if($tovar['Description']) {
				$arElementField['DETAIL_TEXT'] = html_entity_decode($tovar['Description']);
				$arElementField['DETAIL_TEXT_TYPE'] = 'html';
			}
									
			if($sect_id = array_search($tovar['Section'], $this->section_list)) {
				$arElementField['IBLOCK_SECTION_ID'] = $sect_id;
			}
			else { $this->repotr(new Exception('Error of parent section  in product '.$tovar['ID'] , 2)); return false;
			}

			$arElementField['PROPERTY_VALUES'] = array();
			$obligPropertyControl = true;

			
			foreach($this->lineProps as $code => $prop) {
			
			//print_r($prop);
			
				if($prop['PROPERTY_TYPE'] == 'N')  $value = floatval($tovar[$prop['SRC']]);
				else $value = $tovar[$prop['SRC']];


				if(!$value && ($prop['IS_REQUIRED'])) { 
					$ropertyControl = false;
$this->repotr(new Exception("Error in product ".$tovar['ID'].' property '.$code.' requred' , 2)); 
return false;
				}
				elseif($value) {
					//echo $prop['PROPERTY_TYPE']." = ".$prop['USER_TYPE']; 
					
					if($prop['PROPERTY_TYPE'] == 'S' && $prop['USER_TYPE'] == 'HTML') {
						$arElementField['PROPERTY_VALUES'][$code] = array('VALUE' => array ('TEXT' => $value, 'TYPE' => 'html'));
					}
					elseif($prop['PROPERTY_TYPE'] == 'L') {
						$arElementField['PROPERTY_VALUES'][$code]   = array_search($this->localSpecialProperties[$value], $this->propEnumValues[$code]);
					}
					else {
						$arElementField['PROPERTY_VALUES'][$code] = $value;
					}
				}
			}
			
			

			if($element_id = array_search($tovar['ID'], $this->element_list)) {
				$values = $this->localRegProperties[$element_id];
				foreach ($values as $code => $value) {
					if($value)
						$arElementField['PROPERTY_VALUES'][$code] = $value;
				}
			}



			foreach($this->specialProps as $code => $prop) {

				if($prop['TYPE'] == 'N')  $value = floatval($tovar[$prop]);
				else {
					$value = $tovar[$prop];
					if(is_array($value)) {
						$cnt = count($value);
						$value = $value[0];
						
						if($cnt > 1) $this->repotr(new Exception('Multiple value of property'.$code.' item '.$tovar['ID'] , 2));
					}
				}

				if($code == 'STATUS' && $value) { 
					$value = strtolower($value);
				}
				$arElementField['PROPERTY_VALUES'][$code] = array_search($this->localSpecialProperties[$code][$value], $this->propEnumValues[$code]);
			}

			foreach ($tovar['Properties'] as $key=>$prop) {  			
				if(is_array($this->propEnumValues[$key])) {		
					$VALUE = array_search($prop, $this->propEnumValues[$key]);
					$arElementField['PROPERTY_VALUES'][$key] = $VALUE;
				}
				elseif(is_array($prop)) { 
					$arElementField['PROPERTY_VALUES'][$key] = 0;
				}
				else {	
				    $arElementField['PROPERTY_VALUES'][$key] = $prop;
				}
			}

			if($tovar['Picture']) {

				$preview_picture = $imageControler->GetPreview($tovar['Picture']);
				$params = array('TITLE' => $tovar['Name'], 'BRAND' => $brand);
				$brand = $this->localSpecialProperties['BRAND'][$tovar['Brand']];

				if(!$brand) $brand = $this->prefix;

				$params = array('TITLE' => $tovar['Name'], 'BRAND' => $brand);	

				if(!(file_exists($preview_picture))) {
					$this->repotr(new Exception("Image of product ".$tovar['ID'].' not found' , 2));
				}	
				elseif($detail_picture = $imageControler->GetBig($tovar['Picture'], $params)) {
					
					$originalSize = filesize($detail_picture);
		
					if(!($originalSize == $this->picture_list[$element_id]))  {
					    $arElementField['DETAIL_PICTURE'] =  \CFile::MakeFileArray($detail_picture);
						$arElementField['PREVIEW_PICTURE'] = \CFile::MakeFileArray($preview_picture);
				    }
				}
			}

			if(is_array($tovar['AddFiles'])) {

				if(!is_array($tovar['AddFiles']['AddFile'][0]))
					$tovar['AddFiles']['AddFile'] = array($tovar['AddFiles']['AddFile']);

				$arElementField['PROPERTY_VALUES']['files'][] =  Array ("VALUE" => array("del" => "Y"));
				$filePath = CNXConfig::$path['root'].CNXConfig::$path['update'].CNXConfig::$path['files'].''.$file['File'][0];
				if(file_exists($filePath)) {
						$arElementField['PROPERTY_VALUES']['files'] = array("VALUE" => \CFile::MakeFileArray($filePath),"DESCRIPTION"=>$file['Name']);
				}
				/*foreach ($tovar['AddFiles']['AddFile'] as $cnt => $file) {
					$filePath =  '/mnt/data/www/maximum/update/files/'.$file['File'];
					if(file_exists($filePath)) {
						$arElementField['PROPERTY_VALUES']['files'][] = array("VALUE" => \CFile::MakeFileArray($filePath),"DESCRIPTION"=>$file['Name']);
					}
				}*/
				
			}

			
			//echo '<pre>';
			//print_r($arElementField['PROPERTY_VALUES']);	
			//echo '</pre>';

			//return false;
			if($obligPropertyControl && $sect_id) {

				if($element_id = array_search($tovar['ID'], $this->element_list)) { 

					if(!$el->Update($element_id, $arElementField, false, true, false)) 
						throw new \Exception("Error of update product ".$name, 2);
					else { 
						//print_r($arElementField['PROPERTY_VALUES']);
						
						echo $element_id.' - '.$this->element_list[$element_id].'<br />
						';
						unset($this->element_list[$element_id]);
						$this->repotr(new \Exception("Product ".$tovar['ID'].' update' , 0));
						return 'update';
					}
				}
				else  {	
					if(!$ElementID = $el->Add($arElementField, false, true, false)) {
						throw new \Exception("Error of add product ".$name, 2);
					}
					else {
						unset($this->element_list[$element_id]);
						$this->repotr(new Exception("Product ".$tovar['ID'].' add' , 0));
						return 'add';
					}
				}

		    }
			
			return false;
	    }

	    catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);
			return false;
		} 
    }


    public function UpdateElements (INXImageControler $imageControler) {
 			   	
    	if(!$this->RESULT['FAILED']) {
			if(!is_array($this->arResult['Products']['Product'][0]) ) {					
				$tmps=$this->arResult['Products']['Product']; 
				$this->arResult['Products']['Product'] = array(); 
				if($tmps) $this->arResult['Products']['Product'][0] = $tmps; 
			}

			$this->RESULT['NEW_ELEMENT'] = 0;
			$this->RESULT['UPDATE_ELEMENT'] = 0;

			foreach($this->arResult['Products']['Product'] as $cnt=>$tovar) {
				$code = $this->updateElement($tovar, $cnt, $imageControler);
				if($code == 'add') $this->RESULT['NEW_ELEMENT']++;
				elseif($code == 'update') $this->RESULT['UPDATE_ELEMENT']++;
				
			}
		}
    }


    public function DeactivateOldElements() {
    	try {
	    	if(!$this->RESULT['FAILED']) {
	    		$el = new \CIBlockElement;
		    	$this->RESULT['DEACTIVATION_ELEMENTS'] = 0;
		 		echo '<pre>';
		 		print_r($this->element_list);
		 		echo '</pre>';
		 		return false;

		    	foreach ($this->element_list as $element_id => $element_xml_id) {
		    		
		    		$arElementField = Array('IBLOCK_ID' => $this->IB, 'ACTIVE' => 'N');
					
		    		if(!$this->deactiveElements[$element_id]) {
						if(!$el->Update($element_id, $arElementField, false, true, false)) throw new Exception("Error of deactivaion element ".$element_id, 2);
						else { 
							$this->RESULT['DEACTIVATION_ELEMENTS']++;
						}
					}

		    	}
		   	unset ($el);
		    }	
		}

		catch (\Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);

		} 
    }


    public function DeleteOldElements() {
    	try {
	    	if(!$this->RESULT['FAILED']) {
	    		$el = new CIBlockElement;
		    	$this->RESULT['DELETE_ELEMENTS'] = 0;

		    	foreach ($this->element_list as $element_id => $element_xml_id) {
					if(!$el->Delete($element_id)) throw new Exception("Error of delete element ".$element_id, 2);
					else { 
						$this->RESULT['DELETE_ELEMENTS']++;
					}
				}
				
				foreach ($this->deactiveElements as $element_id => $element_xml_id) {
					if(!$el->Delete($element_id)) throw new Exception("Error of delete element ".$element_id, 2);
					else { 
						$this->RESULT['DELETE_ELEMENTS']++;
					}
				}

		   	unset ($el);
		    }	
		}

		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			$this->repotr($e);

		} 
    }
   
   
	
	public function Archive(INXFileManager $fileControler) {
		if(!$fileControler->Archive()) return false;
		$this->RESULT['DATE_END'] = microtime(true);
		return true;
	}

	public function GetStat() {
		
		list($usec, $sec) = explode(" ", $this->RESULT['DATE_START']);
        $start = ((float)$usec + (float)$sec);

        list($usec, $sec) = explode(" ", $this->RESULT['DATE_END']);
        $end = ((float)$usec + (float)$sec);

        $this->RESULT['TIME_SEC'] = $end - $start;
        $this->RESULT['TIME_MINUTE'] = round(($this->RESULT['TIME_SEC']/60),3);
		
		if(is_array($this->LOG)) $this->RESULT['LOG'] = $this->LOG;
		
		return $this->RESULT;

	}
	
}
?>