<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2015 Imageprom
 */

namespace NXMarket;
class CNXConfig {
  public static $LockTime = 600;
  public static $Project = 'test';

  // MAIL DESCRIPTION 

  public static $mail = array(
    'from' => 'info@site-nn.ru',
    'to' => 'info@site-nn.ru',
    'bcc' =>  array(
      'order@imageprom.com', 
      'liska_m@bk.ru'
      )
  );

  // MAIN PATH

  public static $path = array(
    'root' => '/mnt/data/www/reezo/vhost/public',
    'update' => '/update',
    'tmp' => '/tmp', 
    'bad'  => '/bad', 
    'zip'  => '/zip', 
    'xml'  => '/xml',
    'txt'  => '/txt',
    'csv'  => '/csv',   
    'cron'  => '/cron',
    'archive' => '/archive',
    'log' => '/update_log.txt',
    'files' => '/files',
    );

  public static $source = array(
    'full' => 'price.csv',
    'inc' => '*_inc_*.csv'
  );

   public static $destination = array(
    'iblockId' => '1',
    'iblockName' => 'Каталог продукции',
    'iblockFlag' => 'reezo',
    'sid' => 's1',
    'hibOffersId' => '12',
  );

  // CRON TASK

  public static $exec = 'nice -n 15 /usr/bin/php -d memory_limit=-1 -d max_execution_time=-1 ';

  public static $task = array(
      'tail' => 'nx_tails.php',
      'exec' => array(
        'nx_yml.php nn',
        'nx_gmr.php nn'
        ),
  );

  public static function GetPath($obj) {
    if($obj == 'update' )
      $path =  self::$path['root'].self::$path[$obj];
    else  
      $path = self::$path['root'].self::$path['update'].self::$path[$obj];

     return $path;
  }

  public static function GetSource($obj = false) {
    if($obj == 'inc' || $obj == 'full') {
      $pos = strpos(self::$source[$obj], 'xml');
      if($pos !== false) $source = self::GetPath('xml').'/'.self::$source[$obj];
      else $source = self::GetPath('zip').'/'.self::$source[$obj];
      return $source;
    }
    return false;
  }
}