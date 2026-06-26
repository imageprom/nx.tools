<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2014 Imageprom
 */

namespace NXMarket;

function nx_fprice($price, $num = 2) {

  $res = str_replace(' ', '', $price);

  if (stripos($res, ',')) {
    $res = str_replace(',', '.', $res);
  }
  
  $res = round($res, $num);

  if (stripos($res, '.')) {$res = number_format($res, $num, '.', ' ');}
  else $res = number_format($res, 0, '.', ' ');
  
  return $res;
}

function nx_html_price($price, $template = '#R#.#K#') {
  $price = nx_fprice($price);
  $tmp = explode('.', $price);
  if(!$tmp[1])  $tmp[1] = '00';
  if ($template) {
    $template = str_replace('#R#', $tmp[0], $template);
    $template = str_replace('#K#', $tmp[1], $template);
    return $template;
  }
  else return $price;
}


// Function to return the JavaScript representation of a TransactionData object.
function getTransactionJs(&$trans) {
  return <<<HTML
ga('ecommerce:addTransaction', {
  'id': '{$trans['id']}',
  'affiliation': '{$trans['affiliation']}',
  'revenue': '{$trans['revenue']}',
  'shipping': '{$trans['shipping']}',
  'tax': '{$trans['tax']}'
});
HTML;
}

// Function to return the JavaScript representation of an ItemData object.
function getItemJs(&$transId, &$item) {
  return <<<HTML
ga('ecommerce:addItem', {
  'id': '{$transId}',
  'name': '{$item['name']}',
  'sku': '{$item['sku']}',
  'category': '{$item['category']}',
  'price': '{$item['price']}',
  'quantity': '{$item['quantity']}'
});
HTML;
}

function getYaGoods($goods) {
    $ya_goods = '';
  foreach ($goods as $item) {
    $ya_goods .= '{"id": "'.$item['sku'].'", "name": "'.$item['name'].'", "price": "'.$item['price'].'", "quantity": '.$item['quantity'].'},';
  }
  return $ya_goods;
  
}

function getYaGoodsDataLayer($goods) {

    if(!is_array($goods)) return null;
	
	$ya_goods = [];
	
	foreach ($goods as $arItem) {

      $item = '{';
      $item .= '"id": "'.$arItem['id'].'", ';
      $item .= '"name": "'.$arItem['name'].'", ';
      $item .= '"price": '.number_format($arItem['price'], 2, '.', '');
      if($arItem['quantity'])  $item .= ', "quantity": '.$arItem['quantity'];
      $item .= '}';

      $ya_goods[] = $item;
    }

	$ya_goods = implode(', ', $ya_goods);
	return $ya_goods;
}
