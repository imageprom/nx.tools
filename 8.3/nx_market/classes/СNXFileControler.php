<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2019 Imageprom
 */

namespace NXMarket;

interface INXFileManager {
	public function LoadFile();
	public function GetSourceFile();
	public function SetSourceFile();
	public function GetFile($updateFile = true);
	public function Archive($prefix = '', $deleteFile = true);

}

class CNXFileManager implements INXFileManager {

	private $root;
	private $updateFolder;
	private $sourceFile;
	private $temp;
	private $bad;
	private $type;
	private $globalUpateFolder;
	private $archiveFolder;
	
	public function __construct($sourceFile = 'price.csv', $root = false, $updateFolder = '/update', $tempFolder = '/tmp',  $badFolder = '/bad',  $archiveFolder = '/archive') { 
		try {
			
			if(!$root) $root = CNXConfig::$path['root'];
			$this->root = $root;
			$this->updateFolder = $updateFolder;
			$this->temp = $tempFolder;
			$this->bad = $badFolder;
			$this->updateFolder = $updateFolder;
			$this->sourceFile = $sourceFile;
			$this->globalUpateFolder = $this->root.$this->updateFolder;
			$this->archiveFolder = $archiveFolder;
			
			if (strpos($sourceFile, '.txt')){
				$this->type = 'txt';
			}
			elseif (strpos($sourceFile, '.csv')){
				$this->type = 'csv';
			}
			elseif (strpos($sourceFile, '.zip')){
				$this->type = 'zip';
			}
			else {
				throw new Exception('Wrong type of file '.$sourceFile);
			}

		}
		
		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
		} 
	}

	public function LoadFile() {
		try {

			$hand = '';

			if($_REQUEST['hand'] == 1 ){
				$hand = ' через форму ';
			}

			NXMessages::WriteLog(NXMessages::FormatLogMessage('Начата загрузка файла'.$hand, '#MESSAGE#', true));
			
			if(isset($_FILES) && count($_FILES)) {
				foreach($_FILES as $file) {
						
					if (!strpos($sourceFile, '.csv')){
						if($file['tmp_name']) {
							if(copy($file['tmp_name'], $this->GetSourceFile())) {
								NXMessages::FormatLogMessage('Загружен файл'.$hand, '#MESSAGE#', true);
								NXMessages::WriteLog(NXMessages::FormatLogMessage('Загружен файл'.$hand, '#MESSAGE#', true));
								return true;
							}
							else {
								NXMessages::FormatLogMessage('Ошибка загрузки файла'.$hand, '#MESSAGE#', true);
								NXMessages::WriteLog(NXMessages::FormatLogMessage('Ошибка загрузки файла'.$hand, '#MESSAGE#', true));
							}
						}
					}
					else {
						throw new Exception('Wrong type of file '.$sourceFile);
					}
				}
			}

		}

		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
		} 
	} 

	public function SetSourceFile($sourceFile = 'price.csv') {
		try {

			if (strpos($sourceFile, '.csv')){
				$this->type = 'csv';
				$this->sourceFile = $sourceFile;
			}

			elseif (strpos($sourceFile, '.zip')){
				$this->type = 'zip';
				$this->sourceFile = $sourceFile;
			}
			else {
				throw new Exception('Wrong type of file '.$sourceFile);
			}
		}

		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
		} 
	} 
		
	public function GetSourceFile()  {
		$path = $this->root.$this->updateFolder.'/'.$this->type.'/'.$this->sourceFile;
		return $path;
	}

	public function GetFile($updateFile = true) {
		try {
			
			$xmlFile = $this->sourceFile;
					
			if($this->type == 'zip') {
				
				$xmlFile = str_replace('.zip', '.txt',  $xmlFile);
				$outputXML = $this->globalUpateFolder.$this->temp.'/'.$xmlFile;
				if(!$updateFile && is_readable($outputXML)) return $outputXML;
						
				$result = array();
		
				exec('unzip -o '.$this->globalUpateFolder.'/'.$this->type.'/'.$this->sourceFile.' -d '.$this->globalUpateFolder.$this->temp, $result);
				
				if (!$result[0] || !is_readable($outputXML)) {
					throw new Exception('Cant not unzipe file'.$this->sourceFile, 1);
				}
				else {
					return $outputXML;
				}
			}
			
			else {
				$outputXML = $this->globalUpateFolder.$this->temp.'/'.$xmlFile;
				$target = $this->globalUpateFolder.'/'.$this->type.'/'.$this->sourceFile;

				if(!$updateFile && is_readable($outputXML)) return $outputXML;
				elseif(copy($target, $outputXML)) {
					
					return $outputXML;
				}
			
				else {
					throw new Exception('Copy error '.$target, 2);
				}
			}
			
		}
		
		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			
			if($e->getCode() == 1) {
				copy($this->globalUpateFolder.'/'.$this->type.'/'.$this->sourceFile, $this->globalUpateFolder.$this->bad);
			}
			return false;
		} 
	}
	
	public function Archive($prefix = '', $deleteFile = true) {
		try{
		    

			$archiveName = str_replace('.txt', '', $this->sourceFile);
			$archiveName = str_replace('.zip', '', $this->sourceFile);
			$archiveName = $prefix.$archiveName.'_'.date('H-i-s_j-m-Y').'.txt';
			
			$temp_xml = $this->GetFile(false);
			
	        if(copy($temp_xml, $this->globalUpateFolder.$this->archiveFolder.'/'.$archiveName)) {
				
				unlink($temp_xml);
				if($deleteFile) unlink ($this->globalUpateFolder.'/'.$this->type.'/'.$this->sourceFile);
				return true;
			}
			
			else throw new Exception('Copy error '.$this->sourceFile, 2);
		}
		
		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			return false;
		} 
	}


	

}
?>