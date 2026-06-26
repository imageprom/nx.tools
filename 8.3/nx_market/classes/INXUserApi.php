<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;

interface INXApiGet {
	
	public function ShowData(INXApiFormat $format);
   
}

interface INXApiFormat {
	
	public function Format($data);
   
}


?>