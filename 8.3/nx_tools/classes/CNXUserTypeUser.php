<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_tools
 * @copyright  2026 Imageprom
 *
 * @noinspection PhpUndefinedConstantInspection
 */

//TODO - проверить после инсталляции в списке пользовательских полей должно появиться свойство "NX - привязка к Пользователям"
namespace NXTools;

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UserField\Types\EnumType,
    Bitrix\Main\UserField\Types\StringType,
    Bitrix\Main\UserTable,
    Bitrix\Highloadblock\HighloadBlockTable as HLBT;

//class CNXUserTypeUser extends \CUserTypeEnum {
class CNXUserTypeUser extends EnumType
{
    public const USER_TYPE_ID = StringType::USER_TYPE_ID;

    public static function getDescription(): array
    {
        $Asset = \Bitrix\Main\Page\Asset::getInstance();
        $Asset->addJs('/local/js/nx_tools/nx_usertype.js');

        //TODO вынести в языковой файл
        return [
            'USER_TYPE_ID' => 'nx_user',
            'CLASS_NAME' => 'NXTools\CNXUserTypeUser',
            'DESCRIPTION' => 'NX - привязка к Пользователям',
            'BASE_TYPE' => self::USER_TYPE_ID,
        ];
    }

    public static function getEditFormHTML(?array $arUserField, ?array $arHtmlControl): string
    {
        //$tag_name_x = preg_replace("/([^a-z0-9])/is", 'x', $arHtmlControl['NAME']);
        //$tag_name_escaped = \CUtil::JSEscape($arHtmlControl['NAME']);

        $form_name = 'hlrow_edit_' . str_replace('HLBLOCK_', '', $arUserField['ENTITY_ID']) . '_form';

        $inputVals = '';
        if ($arHtmlControl['VALUE'] > 0) {
            $inputVals = $arHtmlControl['VALUE'];
        }

        return self::UserRow($inputVals, $arHtmlControl['NAME'], $form_name);
    }

    //TODO - проверить корректность работы свойства в списке страниц эл-та хиба в режиме редактирования

    public static function renderAdminListEdit(?array $arUserField, ?array $arHtmlControl): string
    {
        $result = '';

        if (!empty($arHtmlControl['VALUE'])) {
            $arUserField['filter'] = ['ID' => $arHtmlControl['VALUE']];
        }

        if (($arUserField["ENTITY_VALUE_ID"] < 1) && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"]) > 0)
            $arHtmlControl["VALUE"] = [(intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]))];
        elseif (!is_array($arHtmlControl["VALUE"]))
            $arHtmlControl["VALUE"] = [];

        $idHib = str_replace('HLBLOCK_', '', $arUserField["ENTITY_ID"]);
        $resultHib = HLBT::getList(['filter' => ['=ID' => $idHib]]);

        $tableName = '';
        if ($row = $resultHib->fetch()) {
            $tableName = $row["TABLE_NAME"];
        }

        $form_name = 'form_tbl_' . $tableName;

        $i = 0;
        $name = str_replace('[]', '', $arHtmlControl["NAME"]);
        if (current($arHtmlControl["VALUE"])) {
            foreach ($arHtmlControl["VALUE"] as $key => $value) {

                $tag_name_x = preg_replace("/([^a-z0-9])/is", "x", $arUserField["EDIT_FORM_LABEL"] . '[' . $key . ']');
                $tag_name_escaped = \CUtil::JSEscape($arUserField["EDIT_FORM_LABEL"] . '[' . $key . ']');

                $fieldHtml .= '<tr><td>';


                $fieldHtml .= self::UserRow($value, $name . '[' . $key . ']', $form_name);

                $fieldHtml .= '</td></tr>';
                $i++;
            }
        }
        $fieldHtml .= '<tr><td>';

        $tag_name_x = preg_replace("/([^a-z0-9])/is", "x", $arUserField["EDIT_FORM_LABEL"] . '[' . $i . ']');
        $tag_name_escaped = \CUtil::JSEscape($arUserField["EDIT_FORM_LABEL"] . '[' . $i . ']');
        $fieldHtml .= self::UserRow('', $name . '[' . $i . ']', $form_name);


        $fieldHtml .= '</td></tr>';

        $result .=
            '<table id="table_' . $name . '">' . $fieldHtml;

        if ($arUserField['MULTIPLE'] == 'Y') {
            $result .= '<tr>' .
                '<td style="padding-top: 6px;"><input type="button" value="Добавить" onclick="addNewRow(\'table_' . $name . '\', \'' . $name . '\')"></td>' .
                '</tr>';
        }

        $result .= '</table>';

        $result .= '<script>';

        $result .= "var indexCount_" . $tag_name_x . " = " . $i . "+1;\n";
        $result .= "function addNewRowUser" . $tag_name_x . "(){

                var oTbl=document.getElementById('table_" . $arUserField[FIELD_NAME] . "');
                var oRow=oTbl.insertRow(oTbl.rows.length-1);
                var oCell=oRow.insertCell(-1);";

        $result .= <<<EOT
oCell.innerHTML='<input name="$arUserField[FIELD_NAME]['+indexCount_$tag_name_x+']" value="" id="$arUserField[FIELD_NAME]['+indexCount_$tag_name_x+']" size="5" type="text">'+
'<input type="button" value="..." '+
'onClick="jsUtils.OpenWindow(\'/bitrix/admin/nx_user_search.php?FN=$form_name&lang=ru&FC=$arUserField[EDIT_FORM_LABEL]['+indexCount_$tag_name_x+']\', '+
' 900, 700);">'+'&nbsp;<span id="div_$arUserField[EDIT_FORM_LABEL]['+indexCount_$tag_name_x+']" ></span>';
EOT;


        $result .= 'indexCount_' . $tag_name_x . '++;';
        $result .= " }";
        $result .= '</script>';


        return $result;
    }


    function UserRow($inputVals, $inputName, $form_name)
    {
        $result = '';
        $inputValsStr = '';
        $inputValsName = '';
        
        if ($inputVals > 0) {
            $rsUsers = \Bitrix\Main\UserTable::getList([
                'filter' => ['=ID' => $inputVals],
                'select' => ['ID', 'LOGIN', 'NAME', 'LAST_NAME'],
            ]);
            
            if ($arUser = $rsUsers->fetch()) {
                $inputValsName = "(" . htmlspecialcharsbx($arUser["LOGIN"]) . ") " . htmlspecialcharsbx($arUser["NAME"]) . " " . htmlspecialcharsbx($arUser["LAST_NAME"]);
                $inputValsStr = ' [' . $arUser["ID"] . ']';
            }
        }
        $result .= '<input name="' . $inputName . '" id="' . $inputName . '" value="' . $inputVals . '" size="5" type="text">';

        $result .= "<IFRAME style=\"width:0px; height:0px; border: 0px\" src=\"javascript:void(0)\" name=\"hiddenframe" . $inputName . "\" id=\"hiddenframe" . $inputName . "\"></IFRAME>";

        $result .= '<input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/nx_user_search.php?lang=ru&FN=' . $form_name . '&lang=ru&FC=' . $inputName . '\', 900, 700);">' .
            '&nbsp;<span id="div_' . $inputName . '" >' . $inputValsName . $inputValsStr . '</span>';

        return $result;
    }


    //TODO - проверить корректность работы свойства фильтре по свойству эл-тов хиба (над списком)

    public static function getFilterHtml($arUserField, $arHtmlControl): string
    {
        global $APPLICATION;

        ob_start();

        $APPLICATION->includeComponent('ip:nx.hlblock.element.selector', 'nx_select_search_1.0',
            [
                'SEARCH_INPUT_ID' => $arUserField['FIELD_NAME'],
                'MULTIPLE' => 'N',
                'CURRENT_ELEMENTS_ID' => $arHtmlControl["VALUE"],
                'TYPE' => 'user',
            ],
            null, ['HIDE_ICONS' => 'Y']
        );

        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    /**
     * @param array $arUserField
     * @param array|null $arHtmlControl
     * @return string
     */

    //TODO - проверить корректность работы свойства в списке страниц эл-та хиба

    public static function getAdminListViewHtml(array $arUserField, ?array $arHtmlControl): string
    {

        static $cache = [];
        $value = intVal($arHtmlControl["VALUE"]);
        if (!array_key_exists($value, $cache)) {

            //TODO я переписала этот кусок на D7 проверить соовествие старому
            //$rsUsers = \CUser::GetList($by, $order, ["ID" => $value]);
            
            $rsUsers = \Bitrix\Main\UserTable::getList([
                'filter' => ['=ID' => $value],
                'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL'],
                'order'  => ['ID' => 'ASC'],
            ]);
            
            
            $cache[$value] = $rsUsers->Fetch();
        }
        $arUser = $cache[$value];
        if ($arUser) {
            return "[<a title='" . GetMessage('MAIN_EDIT_USER_PROFILE') . "' href='user_edit.php?ID=" . $arUser["ID"] . "&lang=" . LANG . "'>" . $arUser["ID"] . "</a>] (" . htmlspecialcharsbx($arUser["LOGIN"]) . ") " . htmlspecialcharsbx($arUser["NAME"]) . " " . htmlspecialcharsbx($arUser["LAST_NAME"]);
        } else
            return "&nbsp;";
    }

    //TODO - проверить корректность работы свойства в списке страниц эл-та на детальке


}