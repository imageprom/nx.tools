<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;

interface INXUpdaterXML {

	public function WriteLog();
    public function ParseXML(INXFileManager $fileControler); 
	public function UpdateSpecialProperty($code, $source, $name, $values, $sort = 1000);
	public function UpdateLineProperties($line_props = false);
	public function UpdateProperties();
    public function UpdateSections(INXImageControler $imageControler);
    public function UpdateElements (INXImageControler $imageControler);
    public function DeactivateOldElements();
    public function DeleteOldElements(); 
	public function Archive(INXFileManager $fileControler);
	public function GetStat();	
}
?>