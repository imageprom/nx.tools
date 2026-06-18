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
    Bitrix\Highloadblock as HL;
use CIBlockPropertyDirectory;
use CJSCore;

\Bitrix\Main\Loader::includeModule('highloadblock');

IncludeModuleLangFile(__FILE__);

class CNXUserTypeDirectory extends \CIBlockPropertyDirectory
{

    public const USER_TYPE_ID = StringType::USER_TYPE_ID;

    public static function GetUserTypeDescription(): array
    {

        $Asset = \Bitrix\Main\Page\Asset::getInstance();
        $Asset->addJs('/local/js/nx_tools/nx_usertype_directory.js');

        $result = parent::GetUserTypeDescription();

        $result['USER_TYPE'] = 'nx_directory';
        $result['DESCRIPTION'] = 'NX - привязка к элементам highloadblock';
        $result['GetPropertyFieldHtml'] = array(__CLASS__, 'GetPropertyFieldHtml');
        $result['GetSettingsHTML'] = array(__CLASS__, 'GetSettingsHTML');
        $result['GetUIFilterProperty'] = array(__CLASS__, 'GetUIFilterProperty');
        $result['AddFilterFields'] = array(__CLASS__, 'AddFilterFields');
        $result['GetAdminListViewHTML'] = array(__CLASS__, 'GetAdminListViewHTML');

        return $result;

        //TODO вынести в языковой файл
        // И вернуть результат в стиле остльаных.
        // Так же добавить поля ниже и проверитьч то еще потерялось
        //

//        return [
//            'USER_TYPE_ID' => 'nx_directory',
//            'CLASS_NAME' => 'NXTools\CNXUserTypeDirectory',
//            'DESCRIPTION' => 'NX - привязка к элементам highloadblock',
//            'BASE_TYPE' => self::USER_TYPE_ID,
//        ];

    }

//TODO - проверить корректность работы свойства в списке страниц эл-та ИБ в режиме редактирования
//TODO - проверить корректность работы свойства фильтре по свойству эл-тов ИБ (над списком)
//TODO - проверить корректность работы свойства в списке страниц эл-та ИБ


    /**
     * Return html for edit single value.
     *
     * @param array $arProperty Property description.
     * @param array $value Current value.
     * @param array $strHTMLControlName Control description.
     * @return string
     */
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName): string
    {
        $html = '';
        $settings = CIBlockPropertyDirectory::PrepareSettings($arProperty);
        $size = ($settings["size"] > 1 ? ' size="' . $settings["size"] . '"' : '');
        $width = ($settings["width"] > 0 ? ' style="width:' . $settings["width"] . 'px"' : '');

        $options = CIBlockPropertyDirectory::GetOptionsHtml($arProperty, array($value["VALUE"]));

        $highLoadIBTableName = (isset($arProperty["USER_TYPE_SETTINGS"]["TABLE_NAME"]) ? $arProperty["USER_TYPE_SETTINGS"]["TABLE_NAME"] : '');

        $element = self::getEntityFieldByFilter(
            $highLoadIBTableName,
            array(
                'select' => array('UF_XML_ID', 'UF_NAME', 'ID')
            ),
            $value['VALUE']
        );

        $html .= '<input name="' . $strHTMLControlName["VALUE"] . '" id="' . $strHTMLControlName["VALUE"] . '" value="' . $value['VALUE'] . '" size="5" type="text">';

        $name = $element['UF_NAME'];
        if ($element['ID']) $name .= ' [' . $element['ID'] . ']';

        $HlBlockId = 0;
        $hibTableInfo = HL\HighloadBlockTable::getList(array('filter'=>array('=TABLE_NAME'=>$highLoadIBTableName)));

        if($row = $hibTableInfo->fetch()) {
            $HlBlockId = $row["ID"];
        }

        $html .= '<input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/nx_highloadblock_rows_list_search.php?ENTITY_ID='.$HlBlockId.'&lang=ru&n=' . $strHTMLControlName["VALUE"] . '\', 900, 700);">' .
            '&nbsp;<span class="name_element" id="sp_' . md5($strHTMLControlName["VALUE"]) . '_" >' . $name . '</span>';

        return $html;
    }


    /**
     * Returns entity data.
     *
     * @param string $tableName HL table name.
     * @param array $listDescr Params for getList.
     * @return array
     */
    private static function getEntityFieldByFilter($tableName, $listDescr = array(), $value)
    {
        $arResult = array();
        $tableName = (string)$tableName;
        if (!is_array($listDescr))
            $listDescr = array();
        if (!empty($tableName)) {
            if (!isset(self::$hlblockCache[$tableName])) {
                self::$hlblockCache[$tableName] = HL\HighloadBlockTable::getList(
                    array(
                        'select' => array('TABLE_NAME', 'NAME', 'ID'),
                        'filter' => array('=TABLE_NAME' => $tableName)
                    )
                )->fetch();
            }
            if (!empty(self::$hlblockCache[$tableName])) {
                if (!isset(self::$directoryMap[$tableName])) {
                    $entity = HL\HighloadBlockTable::compileEntity(self::$hlblockCache[$tableName]);
                    self::$hlblockClassNameCache[$tableName] = $entity->getDataClass();
                    self::$directoryMap[$tableName] = $entity->getFields();
                    unset($entity);
                }
                if (!isset(self::$directoryMap[$tableName]['UF_XML_ID']))
                    return $arResult;
                $entityDataClass = self::$hlblockClassNameCache[$tableName];

                $nameExist = isset(self::$directoryMap[$tableName]['UF_NAME']);
                if (!$nameExist)
                    $listDescr['select'] = array('UF_XML_ID', 'ID');
                $fileExists = isset(self::$directoryMap[$tableName]['UF_FILE']);
                if ($fileExists)
                    $listDescr['select'][] = 'UF_FILE';

                $sortExist = isset(self::$directoryMap[$tableName]['UF_SORT']);
                $listDescr['order'] = array();
                if ($sortExist) {
                    $listDescr['order']['UF_SORT'] = 'ASC';
                    $listDescr['select'][] = 'UF_SORT';
                }
                if ($nameExist)
                    $listDescr['order']['UF_NAME'] = 'ASC';
                else
                    $listDescr['order']['UF_XML_ID'] = 'ASC';
                $listDescr['order']['ID'] = 'ASC';

                $listDescr['filter']['UF_XML_ID'] = $value;
                /** @var \Bitrix\Main\DB\Result $rsData */
                $rsData = $entityDataClass::getList($listDescr);
                while ($arData = $rsData->fetch()) {
                    if (!$nameExist)
                        $arData['UF_NAME'] = $arData['UF_XML_ID'];
                    $arData['SORT'] = ($sortExist ? $arData['UF_SORT'] : $arData['ID']);
                    $arResult = $arData;
                }
                unset($arData, $rsData);
            }
        }
        return $arResult;
    }


    /**
     * Returns html for show in edit property page.
     *
     * @param array $arProperty Property description.
     * @param array $strHTMLControlName Control description.
     * @param array $arPropertyFields Property fields for edit form.
     * @return string
     */
    public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields): string
    {
        $iblockID = 0;
        if (isset($arProperty['IBLOCK_ID']))
            $iblockID = (int)$arProperty['IBLOCK_ID'];
        CJSCore::Init(array('translit'));
        $settings = static::PrepareSettings($arProperty);
        if (isset($settings['USER_TYPE_SETTINGS']))
            $settings = $settings['USER_TYPE_SETTINGS'];
        $arPropertyFields = array(
            'HIDE' => ['ROW_COUNT', 'COL_COUNT', 'MULTIPLE_CNT', 'DEFAULT_VALUE', 'WITH_DESCRIPTION'],
            'SET' => ['DEFAULT_VALUE' => '']
        );

        $directory = [];
        $cellOption = '<option value="-1"' . ('' == $settings["TABLE_NAME"] ? ' selected' : '') . '>' . Loc::getMessage('HIBLOCK_PROP_DIRECTORY_NEW_DIRECTORY') . '</option>';

        $rsData = HL\HighloadBlockTable::getList(array(
            'select' => array('*', 'NAME_LANG' => 'LANG.NAME'),
            'order' => array('NAME_LANG' => 'ASC', 'NAME' => 'ASC')
        ));
        while ($arData = $rsData->fetch()) {
            if ($settings['TABLE_NAME'] == $arData['TABLE_NAME']) {
                $directory = $arData;
                unset($directory['NAME_LANG']);
            }
            $arData['NAME_LANG'] = (string)$arData['NAME_LANG'];
            $hlblockTitle = ($arData['NAME_LANG'] != '' ? $arData['NAME_LANG'] : $arData['NAME']) . ' (' . $arData["TABLE_NAME"] . ')';
            $selected = ($settings["TABLE_NAME"] == $arData['TABLE_NAME']) ? ' selected' : '';
            $cellOption .= '<option ' . $selected . ' value="' . htmlspecialcharsbx($arData["TABLE_NAME"]) . '">' . htmlspecialcharsbx($hlblockTitle) . '</option>';
            unset($hlblockTitle);
        }
        unset($arData, $rsData);

        unset($directory);

        $multiple = $arProperty['MULTIPLE'];

        $tablePrefix = self::TABLE_PREFIX;
        $selectDir = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_SELECT_DIR");
        $headingXmlId = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_XML_ID");
        $headingName = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_NAME");
        $headingSort = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_SORT");
        $headingDef = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_DEF");
        $headingLink = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_LINK");
        $headingFile = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_FILE");
        $headingDescription = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_DECSRIPTION");
        $headingFullDescription = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_FULL_DESCRIPTION");
        $directoryName = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_NEW_NAME");
        $directoryMore = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_MORE");

        $emptyDefaultValue = '';
        if ($multiple == 'N') {
            $emptyDefaultValue = '<tr id="hlbl_property_tr_empty">' .
                '<td colspan="6" style="text-align: center;">' . Loc::getMessage('HIBLOCK_PROP_DIRECTORY_EMPTY_DEFAULT_VALUE') . '</td>' .
                '<td style="text-align:center;">' .
                '<input type="radio" name="PROPERTY_VALUES_DEF" id="PROPERTY_VALUES_DEF_EMPTY" value="-1" checked="checked">' .
                '<td colspan="2">&nbsp;</td>' .
                '</tr>';
        }

        return <<<"HIBSELECT"
<script type="text/javascript">
function getTableHead()
{
	BX('hlb_directory_table').innerHTML = '<tr class="heading"><td></td><td>$headingName</td><td>$headingSort</td><td>$headingXmlId</td><td>$headingFile</td><td>$headingLink</td><td>$headingDef</td><td>$headingDescription</td><td>$headingFullDescription</td></tr>$emptyDefaultValue';
}

function getDirectoryTableRow(addNew)
{
	addNew = (addNew === 'row' ? 'row' : 'full');
	var obSelectHLBlock = BX('hlb_directory_table_id');
	if (!!obSelectHLBlock)
	{
		var rowNumber = parseInt(BX('hlb_directory_row_number').value, 10);
		if (BX('IB_MAX_ROWS_COUNT'))
			rowNumber = parseInt(BX('IB_MAX_ROWS_COUNT').value, 10);
		if (isNaN(rowNumber))
			rowNumber = 0;
		var hlBlock = (-1 < obSelectHLBlock.selectedIndex ? obSelectHLBlock.options[obSelectHLBlock.selectedIndex].value : '');
		var selectHLBlockValue = hlBlock;

		if (addNew === 'full')
		{
			if (selectHLBlockValue == '-1')
			{
				getTableHead();
				BX('hlb_directory_table_tr').style.display = 'table-row';
				BX('hlb_directory_title_tr').style.display = 'table-row';
				BX('hlb_directory_table_name').style.display = 'table-row';
				BX('hlb_directory_table_name').disabled = false;
                BX('hlb_directory_table_button').style.display = 'inline';

				addNew = 'row';
				rowNumber = 0;
			}
			else
			{
				BX('hlb_directory_table_name').disabled = true;
                BX('hlb_directory_table_tr').style.display = 'none';
				BX('hlb_directory_title_tr').style.display = 'none';
				BX('hlb_directory_table_button').style.display = 'none';

			}
		}
		if (addNew === 'row')
		{
			BX.ajax.loadJSON(
				'highloadblock_directory_ajax.php',
				{
					lang: BX.message('LANGUAGE_ID'),
					sessid: BX.bitrix_sessid(),
					hlBlock: hlBlock,
					rowNumber: rowNumber,
					addEmptyRow: 'Y',
					IBLOCK_ID: '{$iblockID}',
					multiple: '{$multiple}'
				},
				BX.delegate(function(result) {
					var obRow = null,
						obTable = BX('hlb_directory_table'),
						i = '',
						obCell = null,
						rowNumber = 0;

					if (!!obTable && 'object' === typeof result)
					{
						rowNumber = parseInt(BX('hlb_directory_row_number').value, 10);
						if (!!BX('IB_MAX_ROWS_COUNT'))
							rowNumber = parseInt(BX('IB_MAX_ROWS_COUNT').value, 10);
						if (isNaN(rowNumber))
							rowNumber = 0;
						obRow = obTable.insertRow(obTable.rows.length);
						obRow.id = 'hlbl_property_tr_'+rowNumber;
						for (i in result)
						{
							obCell = obRow.insertCell(-1);
							BX.adjust(obCell, { style: result[i].style, html: result[i].html });
						}
						BX('hlb_directory_row_number').value = rowNumber + 1;
						if(BX('IB_MAX_ROWS_COUNT'))
							BX('IB_MAX_ROWS_COUNT').value = rowNumber + 1;
					}
				})
			);
		}
	}
}
function getDirectoryTableHead(e)
{
    
	e.value = BX.translit(e.value, {
		'change_case' : 'L',
		'replace_space' : '',
		'delete_repeat_replace' : true
	});

	var obSelectHLBlock = BX('hlb_directory_table_id');
	if (!!obSelectHLBlock)
	{
		if (-1 < obSelectHLBlock.selectedIndex && '-1' == obSelectHLBlock.options[obSelectHLBlock.selectedIndex].value)
		{
			BX('hlb_directory_table_id_hidden').disabled = false;
			BX('hlb_directory_table_id_hidden').value = '{$tablePrefix}'+BX('hlb_directory_table_name').value;
			BX('hlb_directory_table_id_hidden').value = BX('hlb_directory_table_id_hidden').value.substr(0, 30);
		}
	}
}

</script>
<tr>
	<td>{$selectDir}:</td>
	<td>
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[TABLE_NAME]" disabled id="hlb_directory_table_id_hidden">
		<select name="{$strHTMLControlName["NAME"]}[TABLE_NAME]" id="hlb_directory_table_id" onchange="getDirectoryTableRow('full');"/>
			$cellOption
		</select>
	</td>
</tr>
<tr id="hlb_directory_title_tr" class="adm-detail-required-field">
	<td>$directoryName</td>
	<td>
		<input type="hidden" value="0" id="hlb_directory_row_number">
		<input type="text" name="HLB_NEW_TITLE" size="30" id="hlb_directory_table_name" onchange="getDirectoryTableHead(this);">
	</td>
</tr>

<tr id="hlb_directory_table_tr">
	<td colspan="2" style="text-align: center;">
		<table class="internal" id="hlb_directory_table" style="margin: 0 auto;">
			<script type="text/javascript">getDirectoryTableRow('full');</script>
		</table>
	</td>
</tr>
<tr>
	<td colspan="2" style="text-align: center;">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_NAME]" value="{$headingName}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_SORT]" value="{$headingSort}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_XML_ID]" value="{$headingXmlId}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_FILE]" value="{$headingFile}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_LINK]" value="{$headingLink}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_DEF]" value="{$headingDef}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_DESCRIPTION]" value="{$headingDescription}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_FULL_DESCRIPTION]" value="{$headingFullDescription}">
		<div style="width: 100%; text-align: center; margin: 10px 0;">
		<input type="button" value="{$directoryMore}" onclick="getDirectoryTableRow('row');" id="hlb_directory_table_button" class="adm-btn-big">
		</div>
	</td>
</tr>
HIBSELECT;
    }


    /**
     * @param array $property
     * @param array $strHTMLControlName
     * @param array &$fields
     * @return void
     */
    public static function GetUIFilterProperty($property, $strHTMLControlName, &$fields)
    {
        $fields["type"] = "custom_entity";
        $fields["property"] = $property;
        $fields["customRender"] = ["\NXTools\CNXPropertyHlblock", "Render"];
        $fields["customFilter"] = ["\NXTools\CNXPropertyHlblock", "AddFilterByHlb"];
        $fields["operators"] = array(
            "default" => "=",
            "exact" => "=",
            "enum" => "@"
        );
    }


    /**
     * Add values in filter.
     *
     * @param array $arProperty
     * @param array $strHTMLControlName
     * @param array &$arFilter
     * @param bool &$filtered
     * @return void
     */
    public static function AddFilterFields($arProperty, $strHTMLControlName, &$arFilter, &$filtered): void
    {
        $filtered = false;
        $values = array();

        if (isset($_REQUEST[$strHTMLControlName["VALUE"]]))
            $values = (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) ? $_REQUEST[$strHTMLControlName["VALUE"]] : array($_REQUEST[$strHTMLControlName["VALUE"]]));
        elseif (isset($GLOBALS[$strHTMLControlName["VALUE"]]))
            $values = (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) ? $GLOBALS[$strHTMLControlName["VALUE"]] : array($GLOBALS[$strHTMLControlName["VALUE"]]));

        if (!empty($values))
        {
            $clearValues = array();
            foreach ($values as $oneValue)
            {
                $oneValue = (string)$oneValue;
                if ($oneValue != '')
                    $clearValues[] = $oneValue;
            }
            $values = $clearValues;
            unset($oneValue, $clearValues);
        }
        if (!empty($values))
        {
            $filtered = true;
            $arFilter['=PROPERTY_'.$arProperty['ID']] = $values;
        }
    }
}