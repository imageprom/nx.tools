<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

//TODO - протестировать, отладить, проверить что можно переписать на D7

use Bitrix\Main,
    Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\SystemException,
    Bitrix\Highloadblock\HighloadBlockTable as HLBT;

CJSCore::Init(array("jquery"));
\Bitrix\Main\Loader::includeModule('highloadblock');

class NXIblockElementSelector extends CBitrixComponent
{
    public function onIncludeComponentLang()
    {
        $this->includeComponentLang(basename(__FILE__));
        Loc::loadMessages(__FILE__);
    }

    protected function checkModules()
    {
        if ($this->arParams['TYPE'] == 'iblock' || $this->arParams['TYPE'] == 'hlblock') {


            if (!Loader::includeModule('highloadblock')) {
                throw new SystemException(Loc::getMessage('IES_MODULE_NOT_INSTALLED', array('MODULE_ID' => 'highloadblock')));
            }
        }
    }

    public function executeComponent()
    {
        try {

            $this->checkModules();

            $this->checkRequiredParams();

            $this->getLastElements();

            $this->getCurrentElements();

            $this->fillResult();

            $this->includeComponentTemplate();
        } catch (SystemException $exception) {
            ShowError($exception->getMessage());
        }
    }

    /**
     * @return void
     * @throws SystemException
     */
    protected function checkRequiredParams()
    {
        if ($this->arParams['TYPE'] == 'iblock') {
            $listRequiredParams = array('SELECTOR_ID');
            foreach ($listRequiredParams as $requiredParam) {
                if (empty($this->arParams[$requiredParam])) {
                    throw new SystemException(Loc::getMessage(
                        'IES_ERROR_REQUIRED_PARAMETER', array('#PARAM#' => $requiredParam)));
                }
            }
        }
    }

    /**
     * @param string $HlBlock
     * @return Main\ORM\Data\DataManager|false
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @throws SystemException
     */
    public function getEntityDataClass($HlBlock)
    {

        $HlBlockId = 0;
        if (is_numeric($HlBlock)) {
            $HlBlockId = intval($HlBlock);
        } else {
            $result = HLBT::getList(array('filter' => array('=TABLE_NAME' => $HlBlock)));

            if ($row = $result->fetch()) {
                $HlBlockId = $row["ID"];
            }
        }


        if (empty($HlBlockId) || $HlBlockId < 1) {
            return false;
        }

        $hlblock = HLBT::getById($HlBlockId)->fetch();
        $entity = HLBT::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        return $entity_data_class;
    }


    protected function getLastElements()
    {
        $this->arResult['LAST_ELEMENTS'] = [];

        if ($this->arParams['TYPE'] == 'iblock' || $this->arParams['TYPE'] == 'hlblock') {

            $entity_data_class = self::getEntityDataClass($this->arParams['HLBLOCK_TABLE']);
            $rsData = $entity_data_class::getList(array(
                'select' => array('*'),
                "order" => array("ID" => "DESC"),
                'filter' => array(),
                'limit' => 20,
            ));

            while ($i = $rsData->fetch()) {
                $this->arResult['LAST_ELEMENTS'][] = [
                    'ID' => $i['UF_XML_ID'],
                    'NAME' => '[' . $i['ID'] . '] ' . $i['UF_NAME'],
                    'URL' => '',
                ];
            }

        } else if ($this->arParams['TYPE'] == 'user') {

            $rsData = CUser::GetList(
                ($by = "personal_country"),
                ($order = "desc"),
                array(),
                array('NAV_PARAMS' => array("nPageSize" => "20"))
            );

            while ($i = $rsData->fetch()) {
                $elements[] = [
                    'ID' => $i['ID'],
                    'NAME' => '(' . $i['LOGIN'] . ') ' . $i['NAME'] . ' ' . $i['LAST_NAME'],
                ];
            }
        }
        unset($element, $queryObject);
    }

    protected function getCurrentElements()
    {
        $this->arResult['CURRENT_ELEMENTS'] = [];

        if ($this->arParams['TYPE'] == 'iblock' || $this->arParams['TYPE'] == 'hlblock') {

            if ($this->arParams['TYPE'] == 'iblock') {
                $filter = [
                    'UF_XML_ID' => $this->arParams['CURRENT_ELEMENTS_ID'],
                ];
            } elseif ($this->arParams['TYPE'] == 'hlblock') {
                $filter = [
                    'ID' => $this->arParams['CURRENT_ELEMENTS_ID'],
                ];
            }

            $entity_data_class = self::getEntityDataClass($this->arParams['HLBLOCK_TABLE']);
            $queryObject = $entity_data_class::getList(array(
                'select' => array('*'),
                "order" => array("ID" => "DESC"),
                'filter' => $filter,
            ));

            while ($element = $queryObject->fetch()) {
                if ($this->arParams['TYPE'] == 'iblock') {
                    $this->arResult['CURRENT_ELEMENTS'][] = [
                        'ID' => $element['UF_XML_ID'],
                        'NAME' => '[' . $element['ID'] . '] ' . $element['UF_NAME'],
                        'URL' => '',
                    ];
                } elseif ($this->arParams['TYPE'] == 'hlblock') {
                    $this->arResult['CURRENT_ELEMENTS'][] = [
                        'ID' => $element['ID'],
                        'NAME' => '[' . $element['ID'] . '] ' . $element['UF_NAME'],
                        'URL' => '',
                    ];
                }
            }

        } else if ($this->arParams['TYPE'] == 'user') {
            $filter = [
                'ID' => $this->arParams['CURRENT_ELEMENTS_ID'],
            ];

            $rsData = CUser::GetList(
                ($by = "personal_country"),
                ($order = "desc"),
                $filter
            );

            while ($i = $rsData->fetch()) {
                $elements[] = [
                    'ID' => $i['ID'],
                    'NAME' => '(' . $i['LOGIN'] . ') ' . $i['NAME'] . ' ' . $i['LAST_NAME'],
                ];
            }
        }
        unset($element, $queryObject);
    }

    protected function fillResult()
    {
        $this->arResult['SELECTOR_ID'] = $this->arParams['SELECTOR_ID'];
        $this->arResult['SEARCH_INPUT_ID'] = $this->arParams['SEARCH_INPUT_ID'];
        $this->arResult['POPUP'] = $this->arParams['POPUP'];
        $this->arResult['INPUT_NAME'] = $this->arParams['INPUT_NAME'];
        $this->arResult['PANEL_SELECTED_VALUES'] = $this->arParams['PANEL_SELECTED_VALUES'];
        $this->arResult['MULTIPLE'] = $this->arParams['MULTIPLE'];
        $this->arResult['ONLY_READ'] = $this->arParams['ONLY_READ'];
        $this->arResult['ON_CHANGE'] = $this->arParams['ON_CHANGE'];
        $this->arResult['ON_SELECT'] = $this->arParams['ON_SELECT'];
        $this->arResult['ON_UNSELECT'] = $this->arParams['ON_UNSELECT'];

        $this->arResult['TEMPLATE_URL'] = $this->arParams['TEMPLATE_URL'];

        $this->arResult['CURRENT_ELEMENTS_ID'] = $this->arParams['CURRENT_ELEMENTS_ID'];
        $this->arResult['HLBLOCK_TABLE'] = $this->arParams['HLBLOCK_TABLE'];
        $this->arResult['TYPE'] = $this->arParams['TYPE'];
    }
}