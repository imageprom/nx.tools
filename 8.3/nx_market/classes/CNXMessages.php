<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;

//class Exception extends \Exception {}

global $NX_XML_UPDATE_ERROR; $NX_XML_UPDATE_ERROR = new NXMessages('UpdateErrors', 0);
global $NX_XML_UPDATE_WARNING; $NX_XML_UPDATE_WARNING = new NXMessages('UpdateWarning', 1);
global $NX_XML_UPDATE_LOG; $NX_XML_UPDATE_LOG = new NXMessages('UpdateWarning', 2);

class NXMessages extends \Exception {

	protected $messagesQueue;
	protected $messagesCount;
	protected $currentMessage;
	
	public function __construct($message, $code = 0, Exception $previous = null) {
		 $this->messagesQueue = array();
		 $this->messagesCount = 0;
		 $this->messagesCount = false;
		 parent::__construct($message, $code, $previous);
	}
	
	public function AddMessage($exetption) {
		 $this->messagesQueue[] = $exetption;
		 $this->messagesCount = count($this->messagesQueue);
		 $this->currentMessage =  $this->messagesCount - 1;
	}
	
	public function GetMesaage($position = false) {
		if(!$position || ($this->messagesCount <= $position)) {
			$result = $this->messagesQueue[$this->currentMessage];
			if($this->currentMessage>0) $this->currentMessage--;
		}
		
		else {
			$result = $this->messagesQueue[$position];
			if($position > 0 ) $this->currentMessage = $position - 1;
		}
		
		return $result;
	}
	 
	public function GetCount() {
		return $this->messagesCount;
	}
	
	public function GetTextLog($template = '<p>#MESSAGE#<p>') {
		if($this->GetCount() == 0) return false;
		$res ='';
		foreach ($this->messagesQueue as $message) {
			$res .= self::FormatLogMessage( $message->getMessage(), $template, false);
		}
		return $res;
	}

	public static function FormatLogMessage($message, $template = false, $date = false) {

		if(!$template) $template = '<p>#MESSAGE#<p>';

		if($date) $message = date('d.m.Y H:i').' '.$message;
		$message = str_replace('#MESSAGE#', $message, $template).PHP_EOL;
		return $message;
	}

	public static function WriteLog($message) {

		$fp = fopen(CNXConfig::$path['root'].CNXConfig::$path['update'].CNXConfig::$path['log'], 'a+');
	    fwrite($fp, $message);
	  	fclose($fp);
	}

	private static function readLog($lines) {

    	$handle = fopen(CNXConfig::$path['root'].CNXConfig::$path['update'].CNXConfig::$path['log'], 'r');

	    $linecounter = $lines;
	    $pos = -2;
	    $beginning = false;
	    $text = array();
	    while ($linecounter > 0) {
	        $t = " ";
	        while ($t != "\n") {
	            if(fseek($handle, $pos, SEEK_END) == -1) {
	                $beginning = true; 
	                break; 
	            }
	            $t = fgetc($handle);
	            $pos --;
	        }
	        $linecounter --;
	        if ($beginning) {
	            rewind($handle);
	        }
	        $text[$lines-$linecounter-1] = fgets($handle);
	        if ($beginning) break;
	    }
	    fclose ($handle);
	    return $text;
	}


	public static function ShowLog() {

		$lines = self::readLog(2500);
		foreach ($lines as $line) {
		    echo $line.'</br>';
		}

	}

	
}
?>