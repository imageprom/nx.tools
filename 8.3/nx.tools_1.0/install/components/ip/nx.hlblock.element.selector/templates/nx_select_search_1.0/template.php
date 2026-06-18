<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $element */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

// TODO - протестировать, отладить, проверить что можно переписать на D7,
// TODO сравнить с оригинальным компонентом откуда марина его скопировала

?>

<?
$this->addExternalJs($templateFolder.'/lib/chosen.jquery.js');
$APPLICATION->SetAdditionalCSS($templateFolder.'/lib/chosen.css');

?>

<div class="nx-select-area" data-table="<?=$arResult['HLBLOCK_TABLE']?>" data-type="<?=$arResult['TYPE'];?>">
    <select name="find_<?=$arResult['SEARCH_INPUT_ID']?><?if($arResult['MULTIPLE'] == 'Y'):?>[]<?endif?>" <?if($arResult['MULTIPLE'] == 'Y'):?>multiple<?endif?> class="nx-select" id="<?=$arResult['SEARCH_INPUT_ID']?>">
        <option value="" >(все)</option>
        <?if(count($arResult['CURRENT_ELEMENTS'])):?>
            <?foreach($arResult["CURRENT_ELEMENTS"] as $element):?>
                <option value="<?=$element['ID']?>" selected=""><?=$element['NAME']?></option>
            <?endforeach;?>
        <?endif?>
    </select>
</div>