<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_tools
 * @copyright  2026 Imageprom
 *
 * @noinspection PhpUndefinedConstantInspection
 */

//TODO - проверить после инсталляции в списке пользовательских полей должно появиться свойство "NX - привязка к элементам highloadblock"

//TODO - проверить корректность работы свойства в списке страниц эл-та хиба в режиме редактирования - синнгл и мульти
//TODO - проверить корректность работы свойства фильтре по свойству эл-тов хиба (над списком)
//TODO - проверить корректность работы свойства в списке страниц эл-та хиба -  синнгл и мульти

namespace NXTools;

IncludeModuleLangFile(__FILE__);

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\UI\Filter\Options as FilterOptions;

class CNXUserTypeHlblock extends \CUserTypeHlblock
{

    public const USER_TYPE_ID = StringType::USER_TYPE_ID;

    public static function getDescription(): array
    {

        $Asset = \Bitrix\Main\Page\Asset::getInstance();
        $Asset->addJs('/local/js/nx_tools/nx_usertype.js');

        //TODO вынести в языковой файл
        return [
            'USER_TYPE_ID' => 'nx_hlblock',
            'CLASS_NAME' => 'NXTools\CNXUserTypeHlblock',
            'DESCRIPTION' => 'NX - привязка к элементам highloadblock',
            'BASE_TYPE' => self::USER_TYPE_ID,
        ];
    }

    public static function getEditFormHtml(array $arUserField, $arHtmlControl): string
    {
        if (($arUserField["ENTITY_VALUE_ID"] < 1) && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"]) > 0)
            $arHtmlControl["VALUE"] = intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]);

        $arUserField['filter'] = ['ID' => $arUserField['VALUE']];
        $result = '';
        $rsEnum = call_user_func_array(
            [$arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"],
            [$arUserField],
        );
        if (!$rsEnum)
            return '';

        if (current($arHtmlControl["VALUE"])) {
            foreach ($arHtmlControl["VALUE"] as $inputVal) {
                $result = self::UserRow($inputVal, $arHtmlControl["NAME"], $arUserField);
            }
        } else {
            $result = self::UserRow($arHtmlControl['VALUE'], $arHtmlControl["NAME"], $arUserField);
        }

        return $result;
    }

    function UserRow($inputVals, $inputName, $arUserField)
    {
        $result = '';
        if ($inputVals > 0) {


            $arUserField['filter'] = ['ID' => $inputVals];

            $rsEnum = call_user_func_array(
                [$arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"],
                [$arUserField]
            );

            $hibRows = [];
            while ($arEnum = $rsEnum->GetNext()) {
                $hibRows[$arEnum['ID']] = $arEnum;

                $inputValsName = $arEnum['UF_NAME'] . ' [' . $arEnum['ID'] . ']';
            }
        }
        $result .= '<input name="' . $inputName . '" id="' . $inputName . '" value="' . $inputVals . '" size="5" type="text">';

        $result .= "<IFRAME style=\"width:0px; height:0px; border: 0px\" src=\"javascript:void(0)\" name=\"hiddenframe" . $inputName . "\" id=\"hiddenframe" . $inputName . "\"></IFRAME>";

        $result .= '<input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/nx_highloadblock_rows_list_search.php?ENTITY_ID=' . $arUserField["SETTINGS"]['HLBLOCK_ID'] . '&lang=ru&n=' . $inputName . '&type=hib\', 900, 700);">' .
            '&nbsp;<span class="name_element" id="div_' . $inputName . '" >' . $inputValsName . '</span>';


        return $result;
    }


    public static function renderAdminListEdit($arUserField, $arHtmlControl): string
    {
        $result = '';
        $className = $arUserField["USER_TYPE"]["CLASS_NAME"];

        if (($arUserField["ENTITY_VALUE_ID"] < 1) && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"]) > 0)
            $arHtmlControl["VALUE"] = [intval($arUserField["SETTINGS"]["DEFAULT_VALUE"])];
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


                $fieldHtml .= self::UserRow($value, $name . '[' . $key . ']', $arUserField);

                $fieldHtml .= '</td></tr>';
                $i++;
            }
        }
        $fieldHtml .= '<tr><td>';

        $tag_name_x = preg_replace("/([^a-z0-9])/is", "x", $arUserField["EDIT_FORM_LABEL"] . '[' . $i . ']');
        $tag_name_escaped = \CUtil::JSEscape($arUserField["EDIT_FORM_LABEL"] . '[' . $i . ']');
        $fieldHtml .= self::UserRow('', $name . '[' . $i . ']', $arUserField);


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

    public static function getHlRows($userfield, $clearValues = false): array
    {
        global $USER_FIELD_MANAGER;

        $userfieldtmp = $userfield;

        $rows = [];

        $hlblock_id = $userfield['SETTINGS']['HLBLOCK_ID'];
        $hlfield_id = $userfield['SETTINGS']['HLFIELD_ID'];

        if (!empty($hlblock_id)) {
            $hlblock = HLBT::getById($hlblock_id)->fetch();
        }

        if (!empty($hlblock)) {

            $userfield = null;

            if ($hlfield_id == 0) {
                $userfield = ['FIELD_NAME' => 'ID'];
            } else {
                $userfields = $USER_FIELD_MANAGER->GetUserFields('HLBLOCK_' . $hlblock['ID'], 0, LANGUAGE_ID);

                foreach ($userfields as $_userfield) {
                    if ($_userfield['ID'] == $hlfield_id) {
                        $userfield = $_userfield;
                        break;
                    }
                }
            }

            if ($userfield) {
                // validated successfully. get data
                $hlDataClass = HLBT::compileEntity($hlblock)->getDataClass();
                $arrSettings = [
                    'select' => ['ID', $userfield['FIELD_NAME']],
                    'order' => 'ID'
                ];
                if (isset($userfieldtmp['filter'])) {
                    $arrSettings['filter'] = $userfieldtmp['filter'];
                }

                $rows = $hlDataClass::getList($arrSettings)->fetchAll();

                $rowstmp = [];
                foreach ($rows as &$row) {
                    if ($userfield['FIELD_NAME'] == 'ID') {
                        $row['VALUE'] = $row['ID'];
                    } else {
                        //see #0088117
                        if ($userfield['USER_TYPE_ID'] != 'enumeration' && $clearValues) {
                            $row['VALUE'] = $row[$userfield['FIELD_NAME']];
                        } else {
                            $row['VALUE'] = $USER_FIELD_MANAGER->getListView($userfield, $row[$userfield['FIELD_NAME']]);
                        }
                        $row['VALUE'] .= ' [' . $row['ID'] . ']';
                    }
                }
            }
        }

        return $rows;
    }


    public static function getFilterHtml($arUserField, $arHtmlControl): string
    {

        global $APPLICATION;

        ob_start();

        $APPLICATION->includeComponent('ip:nx.hlblock.element.selector', 'nx_select_search_1.0',
            [
                'SEARCH_INPUT_ID' => $arUserField['FIELD_NAME'],
                'MULTIPLE' => 'N',
                'CURRENT_ELEMENTS_ID' => $arHtmlControl["VALUE"],
                'HLBLOCK_TABLE' => $arUserField["SETTINGS"]['HLBLOCK_ID'],
                'TYPE' => 'hlblock',
            ],
            null, ['HIDE_ICONS' => 'Y']
        );
        ?>

        <?
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    public static function getSettingsHtml($arUserField = false, $arHtmlControl, $bVarsFromForm): string
    {
        $result = '';

        if ($bVarsFromForm)
            $hlblock_id = $GLOBALS[$arHtmlControl["NAME"]]["HLBLOCK_ID"];
        elseif (is_array($arUserField))
            $hlblock_id = $arUserField["SETTINGS"]["HLBLOCK_ID"];
        else
            $hlblock_id = "";

        if ($bVarsFromForm)
            $hlfield_id = $GLOBALS[$arHtmlControl["NAME"]]["HLFIELD_ID"];
        elseif (is_array($arUserField))
            $hlfield_id = $arUserField["SETTINGS"]["HLFIELD_ID"];
        else
            $hlfield_id = "";

        if ($bVarsFromForm)
            $value = $GLOBALS[$arHtmlControl["NAME"]]["DEFAULT_VALUE"];
        elseif (is_array($arUserField))
            $value = $arUserField["SETTINGS"]["DEFAULT_VALUE"];
        else
            $value = "";

        if (\CModule::IncludeModule('highloadblock')) {
            $dropDown = static::getDropDownHtml($hlblock_id, $hlfield_id);

            $result .= '
			<tr>
				<td>' . GetMessage("USER_TYPE_HLEL_DISPLAY") . ':</td>
				<td>
					' . $dropDown . '
				</td>
			</tr>
			';
        }


        if ($bVarsFromForm)
            $value = $GLOBALS[$arHtmlControl["NAME"]]["DISPLAY"];
        elseif (is_array($arUserField))
            $value = $arUserField["SETTINGS"]["DISPLAY"];
        else
            $value = self::DISPLAY_LIST;
        $result .= '
		<tr>
			<td class="adm-detail-valign-top">' . GetMessage("USER_TYPE_ENUM_DISPLAY") . ':</td>
			<td>
				<label><input type="radio" name="' . $arHtmlControl["NAME"] . '[DISPLAY]" value="' . self::DISPLAY_LIST . '" ' . (self::DISPLAY_LIST == $value ? 'checked="checked"' : '') . '>' . GetMessage("USER_TYPE_HLEL_LIST") . '</label><br>
				<label><input type="radio" name="' . $arHtmlControl["NAME"] . '[DISPLAY]" value="' . self::DISPLAY_CHECKBOX . '" ' . (self::DISPLAY_CHECKBOX == $value ? 'checked="checked"' : '') . '>' . GetMessage("USER_TYPE_HLEL_CHECKBOX") . '</label><br>
			</td>
		</tr>
		';

        if ($bVarsFromForm)
            $value = intval($GLOBALS[$arHtmlControl["NAME"]]["LIST_HEIGHT"]);
        elseif (is_array($arUserField))
            $value = intval($arUserField["SETTINGS"]["LIST_HEIGHT"]);
        else
            $value = 5;
        $result .= '
		<tr>
			<td>' . GetMessage("USER_TYPE_HLEL_LIST_HEIGHT") . ':</td>
			<td>
				<input type="text" name="' . $arHtmlControl["NAME"] . '[LIST_HEIGHT]" size="10" value="' . $value . '">
			</td>
		</tr>
		';

        return $result;
    }

}