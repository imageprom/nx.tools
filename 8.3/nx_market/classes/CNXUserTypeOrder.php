<?php
/**
 * Imageprom
 * @package    nx
 * @subpackage nx_market
 * @copyright  2024 Imageprom
 * PHP 8.1 Version
 */

namespace NXMarket;

use Bitrix\Main\UserField\Types\StringType;

class CNXUserTypeOrder extends StringType
{

    public const USER_TYPE_ID = StringType::USER_TYPE_ID;

    protected static array $field = [
        'ID' => 'Ид.',
        'PRICE' => 'Цена',
        'SUM' => 'Стоим.',
        'COUNT' => 'Кол-во',
        'NAME' => 'Товар',
        'NOTE' => 'Доп. поля',
        'art' => 'Арт.',
        'price_old' => 'Старая цена',
        'real_id' => 'ID товара',
    ];

    protected static array $fieldSort = [
        'ID' => 10,
        'PRICE' => 30,
        'COUNT' => 40,
        'SUM' => 50,
        'NAME' => 20,
        'NOTE' => 500,
    ];

    /**
     * @return array
     */

    public static function getDescription(): array
    {
        return [
            'USER_TYPE_ID' => 'nx_order',
            'CLASS_NAME' => 'NXMarket\CNXUserTypeOrder',
            'DESCRIPTION' => 'NXMarket - заказ',
            'BASE_TYPE' => self::USER_TYPE_ID,
        ];
    }

    /**
     * @return string
     */

    public static function getDbColumnType(): string
    {
        global $DB;
        switch (strtolower($DB->type)) {
            case 'oracle':
                return 'varchar2(200000 char)';
            case 'mssql':
                return 'varchar(200000)';
            default:
                return 'longtext';
        }

    }

    /**
     * @param array $userField
     * @return array
     */

    public static function prepareSettings(array $userField): array
    {
        $settings = parent::prepareSettings($userField);

        $settings['SIZE'] = ($settings['SIZE'] <= 20) ? 40 : $settings['SIZE'];
        $settings['ROWS'] = ($settings['ROWS'] <= 4) ? 4 : $settings['ROWS'];
        $settings['DEFAULT_VALUE'] = '';

        return $settings;
    }

    /**
     * @param string $code
     * @return string
     */
    protected static function getFieldName(string $code): string
    {
        if (!isset(self::$field[$code])) return $code;
        else return self::$field[$code];
    }

    /**
     * @param string $code
     * @return int
     */
    protected static function getFieldSort(string $code): int
    {
        if (!isset(self::$fieldSort[$code])) return 100;
        return self::$fieldSort[$code];
    }

    /**
     * @param string $a
     * @param string $b
     * @return int
     */
    public static function arCmp(string $a, string $b): int
    {

        $a = self::getFieldSort($a);
        $b = self::getFieldSort($b);

        if ($a == $b) return 0;
        return ($a < $b) ? -1 : 1;
    }

    public static function getEditFormHTML(?array $userField, ?array $additionalParameters): string
    {
        $arHtmlControl = $additionalParameters;

        if ($userField['ENTITY_VALUE_ID'] < 1 && strlen($userField['SETTINGS']['DEFAULT_VALUE']) > 0)
            $arHtmlControl['VALUE'] = htmlspecialcharsbx($userField['SETTINGS']['DEFAULT_VALUE']);

        $orderData = json_decode(html_entity_decode($arHtmlControl['VALUE']), true);
        if (is_array($orderData)) {
            $arColumn = array_keys(reset($orderData));
            $arColumn[] = 'SUM';

            uasort($arColumn, 'self::arCmp');

            $border = 'border:1px solid #9ea7b1';

            $orderTable = '<table cellpadding="5" style="width:100%; ' . $border . ' border-collapse: collapse;"><tr>';

            foreach ($arColumn as $column) {
                $orderTable .= '<th style="border:1px solid #9ea7b1; white-space:nowrap">' .
                    self::getFieldName($column) .
                    '</th>';
            }

            $orderTable .= '</tr>';

            foreach ($orderData as $row) {

                $orderTable .= '<tr>';
                $row['SUM'] = $row['PRICE'] * $row['COUNT'];

                foreach ($arColumn as $column) {

                    $value = $row[$column];
                    $cell = '';
                    $style = '';

                    switch ($column) {
                        case 'NOTE':
                            $style = 'style="' . $border . '"';
                            $cell .= '<small>';
                            foreach ($value as $key => $note) {
                                $cell .= '<span style="white-space:nowrap; display:block;">';
                                if (!$note) $note = ' - ';
                                $cell .= self::getFieldName($key) . ': ' . $note;
                                $cell .= '</span>';
                            }
                            $cell .= '</small>';
                            break;

                        case 'SUM':
                        case 'PRICE':
                            $cell = nx_fprice($value);
                            $style = 'style="text-align:right; border:1px solid #9ea7b1;"';
                            break;

                        case 'ID':
                        case 'COUNT':
                            $cell = $value;
                            $style = 'style="text-align:center; ' . $border . '"';
                            break;

                        default:
                            $style = 'style="' . $border . '"';
                            $cell = $value;
                            break;
                    }

                    $orderTable .= '<td ' . $style . '>' . $cell . '</td>';

                }

                $orderTable .= '</tr>';

            }

            $orderTable .= '</table>';
            $orderTable = '<div style="width:100%; margin-bottom:2em;">' . $orderTable . '</div>';

        } else $orderTable = '';

        if ($userField['SETTINGS']['ROWS'] < 2) {
            return $orderTable . '<input type="text" ' .
                'name="' . $arHtmlControl['NAME'] . '" ' .
                'size="' . $userField['SETTINGS']['SIZE'] . '" ' .
                ($userField['SETTINGS']['MAX_LENGTH'] > 0 ? 'maxlength="' . $userField['SETTINGS']['MAX_LENGTH'] . '" ' : '') .
                'value="' . $arHtmlControl['VALUE'] . '" ' .
                ($userField['EDIT_IN_LIST'] != 'Y' ? 'disabled="disabled" ' : '') .
                '>';
        } else {
            return $orderTable . '<textarea ' .
                'name="' . $arHtmlControl['NAME'] . '" ' .
                'cols="' . $userField['SETTINGS']['SIZE'] . '" ' .
                'rows="' . $userField['SETTINGS']['ROWS'] . '" ' .
                ($userField['SETTINGS']['MAX_LENGTH'] > 0 ? 'maxlength="' . $userField['SETTINGS']['MAX_LENGTH'] . '" ' : '') .
                ($userField['EDIT_IN_LIST'] != "Y" ? 'disabled="disabled" ' : '') .
                '>' . $arHtmlControl['VALUE'] . '</textarea>';
        }
    }

    public static function GetAdminListViewHTML(?array $userField, ?array $additionalParameters): string
    {

        $arHtmlControl = $additionalParameters;

        if (strlen($arHtmlControl['VALUE']) > 0) {

            $orderTable = '';
            $orderData = json_decode(html_entity_decode($arHtmlControl['VALUE']), true);
            $arColumn = array_keys(reset($orderData));
            $arColumn[] = 'SUM';
            uasort($arColumn, 'self::arCmp');

            foreach ($orderData as $row) {

                $orderTable .= '<div style="border-bottom:1px dashed #9ea7b1; margin-bottom:1em;">';
                $row['SUM'] = $row['PRICE'] * $row['COUNT'];

                foreach ($arColumn as $column) {

                    $value = $row[$column];

                    $cell = '';
                    $style = '';

                    switch ($column) {
                        case 'NOTE':
                            $style = '';
                            $cell .= '<small style="display:block;">';
                            foreach ($value as $key => $note) {
                                $cell .= '<span style="white-space:nowrap; display:block;">';
                                if ($note) {
                                    $cell .= self::getFieldName($key) . ': ' . $note;
                                }
                                $cell .= '</span>';
                            }
                            $cell .= '</small>';
                            break;

                        case 'SUM':
                        case 'PRICE':
                            $cell = nx_fprice($value);
                            $style = 'style="margin:0;"';
                            break;

                        case 'ID':
                        case 'COUNT':
                            $cell = $value;
                            $style = 'style="margin:0;"';
                            break;

                        case 'NAME':
                            $cell = $value;
                            $style = 'style="margin:0 0 1em 0;"';
                            break;

                        default:
                            $style = 'style="margin:0;"';
                            $cell = $value;
                            break;
                    }

                    $orderTable .= '<p ' . $style . '><b>' . self::getFieldName($column) . '</b>: ' . $cell . '</p>';

                }

                $orderTable .= '</div>';

            }

            return '<div style="min-width:200px;">' . $orderTable . '</div>';

        } else
            return ' ';
    }
}