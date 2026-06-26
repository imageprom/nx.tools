<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;
class Exception extends \Exception {}

use Bitrix\Main\Application;
use Bitrix\Main\DB\MssqlConnection;
use Bitrix\Main\DB\OracleConnection;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;


define('IMAGIC', '/usr/bin/');
define('NX_BR', '
');

interface INXImageControler {

	public function GetPreview($name, $params = false);
	public function GetBig($name, $params = false);
	public static function DiffPictureByFilename ($original, $copy);
	public static function DiffPictureByParams ($original, $copy);

}

interface INXImageUpdater {
	public function ResizeBig ($path = false, $size = array('WIDTH'=>1000, 'HEIGHT'=> 1000), $resizeAll = false);
	public function ResizePrw ($path = false,  $size = array('WIDTH'=>160, 'HEIGHT'=> 160), $resizeAll = false);
	public function RenameSource($path = false);

}

interface INXImageModfier {
	public function ModiftyImage ($source, $destination, $params = false, $root = false);
}

class CNXWatermark implements INXImageModfier {

	public function ModiftyImage($source, $destination, $params = false, $root = false) {
		try {

			return $source;
			if (!$root) $root = $_SERVER["DOCUMENT_ROOT"].CNXConfig::$path['update'].CNXConfig::$path['watermark'];
			
			if(!is_readable($source)) throw new Exception('Can\'t read file '.$root.$sourceFolder, 0);
			else {

				$titleMargin = $params['WIDTH'] - 120;
				
				if($params['WIDTH'] < 500) $font_size = 14;
				else $font_size = 16;	
				
				$brandLogo = $root.strtolower($params['BRAND']).'.png';
				if (!is_readable($brandLogo)) $brandLogo = $root.'empty.png';
				
				$convert = IMAGIC."convert ".$source." -strip -interlace Plane -quality 95%\
				-gravity east \
				-append \
				-gravity SouthWest  -font Palatino-Roman  -pointsize ".$font_size." \
				-background none  -stroke 'rgb(220,220,220, 0.4)' -strokewidth 3  -size ".$titleMargin."x  \
				caption:'".$params['TITLE']."' -gravity SouthWest  -geometry +19+20  -compose over -composite \
				-background none  -stroke  none  -fill 'rgb(55,55,55)' -size ".$titleMargin."x  \
				caption:'".$params['TITLE']."' -gravity SouthWest  -geometry +20+20  -compose over -composite \
				\( ".$brandLogo." \) -gravity SouthEast -composite \
				".$destination;
				
				exec($convert);
				
				if(file_exists($destination)) return $destination;
				else return false;
			}
		}

		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			return false;
		} 
	}
}


class CNXImageControler implements INXImageControler, INXImageUpdater {

	private $root;
	private $sourceFolder;
	private $bigFolder;
	private $prwFolder;
	private $tmpFolder;
	private $imageType;
	private $modier;
	
	function __construct($root = false, $sourceFolder = '/photos', $bigFolder = '/photos_big', $prwFolder = '/photos_prw', $tmpFolder = '/tmp/photos', $imageType = 'jpg') {
		try {
			
			if (!$root) $root = CNXConfig::$path['root']; 
					
			if (is_writable($root)) $this->root = $root;
			else throw new Exception('We can\'t write to directoy '.$root, 0);

			if (is_readable($root.$sourceFolder)) $this->sourceFolder = $sourceFolder;
			else throw new Exception('We can\'t write to directoy '.$root.$sourceFolder, 0);

			if (is_readable($root.$prwFolder)) $this->prwFolder = $prwFolder;
			else throw new Exception('We can\'t write to directoy '.$root.$prwFolder, 0);

			if (is_readable($root.$bigFolder)) $this->bigFolder = $bigFolder;
			else throw new Exception('We can\'t write to directoy '.$root.$bigFolder, 0);

			if (is_readable($root.$tmpFolder)) $this->tmpFolder = $tmpFolder;
			else throw new Exception('We can\'t write to directoy '.$root.$tmpFolder, 0);
	
			$this->imageType = $imageType;
	
			$modier = false;
			
		}
		
		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
		} 
	}
		
   
	public function SetModifier(INXImageModfier $modifier) {
		$this->modifier = $modifier;
	}

	public function GetPreview($name, $params = false) {
		$imgSource = $this->root.$this->prwFolder.'/'.$name;	

		if(is_readable($imgSource)) return $imgSource;
		else return false;
	}

	public function GetBig($name, $params = false) {
		if(!$this->modifier) $imgSource = $this->root.$this->prwFolder.'/'.$name;
		else {
						
            //date("F d Y H", filemtime($name));
			
			$mod_params = array('TITLE' => $params['TITLE'], 'BRAND' => $params['BRAND']);

			$source = $this->root.$this->sourceFolder.'/'.$name;
			$big = $this->root.$this->bigFolder.'/'.$name;
			$tmp = $this->root.$this->tmpFolder.'/'.$name;
			
			clearstatcache();
			
			$dateBig = date("YmdH", fileatime($big));
			$dateTmp = date("YmdH", fileatime($tmp));
			
			$dateBigC = date("YmdH", filectime($big));
			$dateTmpC = date("YmdH", filectime($tmp));
			
			if($dateBig > $dateTmp || $dateBigC > $dateTmpC) {
				
				$size_big = getimagesize($big);
				$mod_params['WIDTH'] =  $size_big[0];
				$mod_params['HEIGHT'] = $size_big[1];
			    
			    $imgSource = $this->modifier->ModiftyImage($big, $tmp, $mod_params);

			    if(!$imgSource || !is_readable($imgSource)) $imgSource = false;	
			}

			else $imgSource = $tmp;
		}

		return $imgSource;
	}


	public static function DiffPictureByFilename ($original, $copy) {

		if(filesize($original) == filesize($copy)) true;
		return false;
	}

	public static function DiffPictureByParams($original, $copy){
		try {

			if (is_array($original) && is_array($copy)) {

				if(count($original) == 0) return false;
				
				$result = true;

				foreach ($original as $key => $value) {
					$result = ($result && ($value == $copy[$key]));
				}
                
				return $result;
			}
			else {
				throw new Exception('Input date can\'t be compare ', 0);	
			} 
		}
		
		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
			return false;
		} 
	}

	public function ResizeBig($path = false, $size = array('WIDTH'=>1000, 'HEIGHT'=> 1000), $resizeAll = false) {
		return false;
	}
	
	
	

	public function ResizePrw($path = false,  $size = array('WIDTH'=>160, 'HEIGHT'=> 160), $resizeAll = false) {
		
		try { 
			if(!$path) $path = $this->root.$this->sourceFolder;

			$pictures = glob($path.'/*.'.$this->imageType);
			$updateCount = 0;
			 $smSize=$size['WIDTH']."x".$size['WIDTH'];

			foreach ($pictures as $pic) {
	   	
	   			
				$tmp = getimagesize($pic);
				$source['WIDTH'] = $tmp[0];
				$source['HEIGHT']  = $tmp[1];
				$source['BYTES'] = filesize($pic); 
				               
				$big = str_replace($this->sourceFolder.'/', $this->bigFolder.'/', $pic);
				$prw = str_replace($this->sourceFolder.'/', $this->prwFolder.'/', $pic);
				

				$destination= array('WIDTH' => 0,  'HEIGHT' => 0 , 'BYTES' => 0); 

				if(file_exists($big))  {
		
					$tmp = getimagesize($pic); 
					$destination['WIDTH'] = $tmp[0];
					$destination['HEIGHT']  = $tmp[1]; 
					$destination['BYTES']=filesize($big);
				}

				if(!(self::DiffPictureByParams($source, $destination)) || $resizeAll) {				 
					
					if(!copy($pic, $big)) throw new Exception('Copy error '.$pic, 2);

					if($size['MODE'] == 'square') {
						if($source['WIDTH'] == $size['WIDTH'] && $source['HEIGHT'] == $size['HEIGHT']) copy($big, $prw);
						else exec(IMAGIC."convert -define jpeg:size=".$smSize." ".$big." -strip -interlace Plane -gaussian-blur 0.05 -quality 100% -thumbnail '".$smSize.">' -bordercolor white -border 100 -gravity center  -crop ".$smSize."+0+0 +repage  ".$prw);
					}

					elseif( $source['WIDTH'] >= $source['HEIGHT']) {
						if($source['WIDTH'] <= $size['WIDTH']) copy($big, $prw);
						else exec(IMAGIC.'convert '.$big.' -strip -interlace Plane -gaussian-blur 0.05 -quality 100%  -resize '.$size['WIDTH'].' '.$prw);
					}
					
					else {
					    if($source['HEIGHT'] <= $size['HEIGHT']) copy($big, $prw);
						else exec(IMAGIC.'convert '.$big.' -strip -interlace Plane -gaussian-blur 0.05 -quality 100%  -resize x'.$size['HEIGHT'].' '.$prw);	
					}
					
					$updateCount++;
				}

			}

			return $updateCount;
		}
		
		catch (Exception $e) {
			echo "Error (File: ".$e->getFile().", line ".$e->getLine()."): ".$e->getMessage();
		} 

	}

	public function RenameSource($path = false) {
		return false;
	}

}
?>