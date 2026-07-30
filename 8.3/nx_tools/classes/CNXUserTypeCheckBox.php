<?php

/**
 * Imageprom
 * @package    nx
 * @subpackage nx_tools
 * @copyright  2026 Imageprom
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace NXTools;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\SystemException,
    Bitrix\Main\UI\Filter\Options,
    Bitrix\Iblock\PropertyTable;

class CNXUserTypeCheckBox
{
    public static function GetUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => PropertyTable::TYPE_NUMBER,
            'USER_TYPE' => 'nx_checkbox',
            'DESCRIPTION' => 'NX - Да/Нет',
            'GetAdminListViewHTML' => [__CLASS__, 'GetAdminListViewHTML'],
            'GetPublicViewHTML'    => [__CLASS__, 'GetAdminListViewHTML'],
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetPropertyFieldHtmlMulty' => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetUIFilterProperty' => [__CLASS__, 'GetUIFilterProperty'],
            'AddFilterFields' => [__CLASS__, 'AddFilterFields'],
            'ConvertToDB' => [__CLASS__, 'ConvertToFromDB'],
            'ConvertFromDB' => [__CLASS__, 'ConvertToFromDB'],
            'GetSearchContent' =>  [__CLASS__, 'GetSearchContent'],
            'GetLength' => [__CLASS__, 'GetLength'],
        ];
    }

    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        return $value['VALUE'] == 1 ? 'Да' : 'Нет';
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $html = '<input type="hidden" value="" name="'.$strHTMLControlName["VALUE"].'">';
        $html .= '<input type="checkbox" size="'.$arProperty['COL_COUNT'].'" value="1" name="'.$strHTMLControlName["VALUE"].'"'.((int)$value['VALUE'] ? ' checked' : '').'>';

        return $html;
    }

    public static function AddFilterFields($arProperty, $strHTMLControlName, &$arFilter, &$filtered)
    {
        if (isset($strHTMLControlName['FILTER_ID']))
        {
            $filterOption = new Options($strHTMLControlName['FILTER_ID']);
            $filterData = $filterOption->getFilter();

            if (isset($filterData[$strHTMLControlName['VALUE']])){
                $prefix = $filterData[$strHTMLControlName['VALUE']] == 1 ? '=' : '!';

                unset($arFilter[$strHTMLControlName['VALUE']]);
                $arFilter[$prefix . 'PROPERTY_'.$arProperty['ID']] = 1;
                $filtered = true;
            }
        }
    }

    public static function GetUIFilterProperty($property, $strHTMLControlName, &$fields)
    {
        $fields['type'] = 'list';
        $fields['items'] = [1 => 'Да', 0 => 'Нет'];
        $fields['filterable'] = '';
        $fields['params'] = ['multiple' => 'N'];
    }

    public static function GetSearchContent($arProperty, $value, $strHTMLControlName)
    {
        $propId = $arProperty;
        $propParams = \CIBlockProperty::GetByID($propId)->Fetch();

        return $value['VALUE'] == 1 ? $propParams['NAME'] : '';
    }

    public static function ConvertToFromDB($arProperty, $value)
    {
        $value['VALUE'] = $value['VALUE'] == 1 ? 1 : 0;
        return $value;
    }

    public static function GetLength($arProperty, $value)
    {
        return 1;
    }
}