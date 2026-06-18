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

//TODO - протестировать, отладить, проверить что можно переписать на D7

use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Web\Json;


use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

use Bitrix\Main\Localization\Loc;

class CNXPropertyHlblock
{
    public static function Render($filterId, $propertyType, array $listProperty)
    {
        $html = '';

        if (!empty($listProperty)) {
            $html .= self::getJsHandlerNxDerectory();
            global $APPLICATION;

            $filterOption = new FilterOptions($filterId);
            $filterData = $filterOption->getFilter();


            foreach ($listProperty as $property) {
                $currentElements = array();
                if (!empty($filterData[$property['FIELD_ID']])) {
                    try {
                        global $APPLICATION;
                        $convertValue = $APPLICATION->ConvertCharset(
                                $filterData[$property['FIELD_ID']], 'UTF-8', LANG_CHARSET);
                        $currentValues = Json::decode($convertValue);

                    } catch (SystemException $e) {
                        return $e->getMessage();
                    }
                    if (is_array($currentValues)) {
                        foreach ($currentValues as $value) {
                            $currentElements[] = current($value);
                        }
                    } else {
                        $currentElements[] = $currentValues;
                    }
                }

                ob_start();
                $APPLICATION->includeComponent('ip:nx.hlblock.element.selector', '',
                        array(
                                'SELECTOR_ID' => $filterId . '_' . $property['FIELD_ID'],
                                'SEARCH_INPUT_ID' => $filterId . '_' . $property['FIELD_ID'],
                                'MULTIPLE' => 'Y',
                                'PANEL_SELECTED_VALUES' => 'N',
                                'CURRENT_ELEMENTS_ID' => $currentElements,
                                'POPUP' => 'Y',
                                'HLBLOCK_TABLE' => $property['USER_TYPE_SETTINGS']['TABLE_NAME'],
                                'TYPE' => 'iblock'
                        ),
                        null, array('HIDE_ICONS' => 'Y')
                );
                ?>
                <script data-type="test_1">
                    BX.ready(function () {
                        BX.FilterHandlerNxDerectory.create(
                            '<?=\CUtil::JSEscape($filterId . '_' . $property['FIELD_ID'])?>',
                            {
                                fieldId: '<?=\CUtil::JSEscape($property['FIELD_ID'])?>',
                                controlId: '<?=\CUtil::JSEscape($filterId . '_' . $property['FIELD_ID'])?>',
                                multiple: 'Y'
                            }
                        );
                    });
                </script>
                <?
                $html .= ob_get_contents();
                ob_end_clean();
            }
        }

        return $html;
    }

    protected static function getJsHandlerNxDerectory()
    {
        ob_start();
        ?>
        <script>
            (function () {
                'use strict';
                if (typeof (BX.FilterHandlerNxDerectory) === 'undefined') {
                    BX.FilterHandlerNxDerectory = function () {
                        this._id = '';
                        this._settings = {};
                        this._fieldId = '';
                        this._control = null;

                        this._currentElements = [];
                        this._controlId = null;
                        this._controlObj = null;
                        this._controlContainer = null;
                        this._serviceContainer = null;

                        this._zIndex = 1100;
                        this._isDialogDisplayed = false;
                        this._dialog = null;

                        this._inputKeyPressHandler = BX.proxy(this.onInputKeyPress, this);
                    };
                    BX.FilterHandlerNxDerectory.prototype =
                        {
                            initialize: function (id, settings) {
                                this._id = id;
                                this._settings = settings ? settings : {};
                                this._fieldId = this.getSetting('fieldId', '');
                                this._controlId = this.getSetting('controlId', '');
                                this._multiple = this.getSetting('multiple', 'Y') === 'Y';
                                this._controlContainer = BX(this._controlId);

                                this._serviceContainer = this.getSetting('serviceContainer', null);
                                if (!BX.type.isDomNode(this._serviceContainer)) {
                                    this._serviceContainer = document.body;
                                }

                                BX.addCustomEvent(window, 'BX.Main.Filter:customEntityFocus',
                                    BX.proxy(this.onCustomEntitySelectorOpen, this));
                                BX.addCustomEvent(window, 'BX.Main.Filter:customEntityBlur',
                                    BX.proxy(this.onCustomEntitySelectorClose, this));
                            },
                            getId: function () {
                                return this._id;
                            },
                            getSetting: function (name, defaultval) {
                                return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
                            },
                            getSearchInput: function () {
                                return this._control ? this._control.getLabelNode() : null;
                            },
                            isOpened: function () {
                                return this._isDialogDisplayed;
                            },
                            open: function () {
                                if (this._controlObj === null) {
                                    var objName = BX.HLblock[this._controlId];
                                    if (!objName) {
                                        throw 'BX.FilterHandlerNxDerectory: Could not find ' + objName + ' element selector.';
                                    }
                                    this._controlObj = objName;
                                }

                                this._multiple = this._controlObj.multiple;
                                var searchInput = this.getSearchInput();
                                if (this._controlObj.searchInput) {
                                    BX.unbind(this._controlObj.searchInput, 'keyup',
                                        BX.proxy(this._controlObj.search, this._controlObj));
                                }
                                this._controlObj.searchInput = searchInput;
                                BX.bind(this._controlObj.searchInput, 'keyup',
                                    BX.proxy(this._controlObj.search, this._controlObj));
                                this._controlObj.onSelect = BX.proxy(this.onSelect, this);
                                BX.bind(searchInput, 'keyup', this._inputKeyPressHandler);
                                if (this._multiple) {
                                    this._controlObj.onUnSelect = BX.proxy(this.onSelect, this);
                                }

                                if (this._currentElements) {
                                    this._controlObj.setSelected(this._currentElements);
                                } else {
                                    var selected = this._controlObj.getSelected();
                                    if (selected) {
                                        for (var key in selected) {
                                            if (selected.hasOwnProperty(key)) {
                                                this._controlObj.unselect(key);
                                            }
                                        }
                                    }
                                }

                                if (this._dialog === null) {
                                    this._controlContainer.style.display = '';
                                    this._dialog = new BX.PopupWindow(
                                        this._id,
                                        this.getSearchInput(),
                                        {
                                            autoHide: false,
                                            draggable: false,
                                            closeByEsc: true,
                                            offsetLeft: 0,
                                            offsetTop: 0,
                                            zIndex: this._zIndex,
                                            bindOptions: {forceBindPosition: true},
                                            content: this._controlContainer,
                                            events:
                                                {
                                                    onPopupShow: BX.delegate(this.onDialogShow, this),
                                                    onPopupClose: BX.delegate(this.onDialogClose, this),
                                                    onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
                                                }
                                        }
                                    );
                                }

                                this._dialog.show();
                                this._controlObj._onFocus();
                                if (this._control) {
                                    this._control.setPopupContainer(this._controlContainer);
                                }
                            },
                            close: function () {
                                var searchInput = this.getSearchInput();
                                if (searchInput) {
                                    BX.bind(searchInput, 'keyup', this._inputKeyPressHandler);
                                }

                                if (this._dialog) {
                                    this._dialog.close();
                                }

                                if (this._control) {
                                    this._control.setPopupContainer(null);
                                }
                            },
                            onCustomEntitySelectorOpen: function (control) {
                                console.log(control)
                                console.log(control.getId())
                                if (control.getId() !== this._fieldId) {
                                    this._control = null;
                                    this.close();
                                } else {
                                    this._control = control;
                                    var currentValues = this._control.getCurrentValues();
                                    if (!!currentValues.value) {
                                        this._currentElements = [];
                                        if (this._multiple) {
                                            var values = JSON.parse(currentValues.value);
                                            for (var k in values) {
                                                this._currentElements.push(
                                                    {"id": values[k][0], "name": values[k][1]});
                                            }
                                        } else {
                                            this._currentElements.push(
                                                {"id": currentValues.value, "name": currentValues.label});
                                        }
                                    }
                                    this.open();
                                }
                            },
                            onCustomEntitySelectorClose: function (control) {
                                if (control.getId() !== this._fieldId) {
                                    return;
                                }

                                var currentValues = control.getCurrentValues();
                                if (!currentValues.value && control.getLabelNode()) {
                                    var value = control.getLabelNode().value;
                                    if (value) {
                                        control.getLabelNode().value = "";
                                        control.setData(value, value);
                                    }
                                }

                                this.close();
                            },
                            onDialogShow: function () {
                                this._isDialogDisplayed = true;
                            },
                            onDialogClose: function () {
                                this._isDialogDisplayed = false;
                                this._controlContainer.parentNode.removeChild(this._controlContainer);
                                this._serviceContainer.appendChild(this._controlContainer);
                                this._controlContainer.style.display = 'none';
                                this._dialog.destroy();
                            },
                            onDialogDestroy: function () {
                                this._dialog = null;
                            },
                            onInputKeyPress: function () {
                                if (!this._dialog || !this._isDialogDisplayed) {
                                    this.open();
                                }
                                if (this._controlObj) {
                                    this._controlObj.search();
                                }
                            },
                            onSelect: function (element) {
                                console.log(element)
                                if (!this._control || this._control.getId() !== this._fieldId) {
                                    return;
                                }
                                var node = this._control.getLabelNode();
                                node.value = '';

                                if (this._multiple) {
                                    this._currentElements = element;
                                    var labels = [];
                                    var values = {};
                                    for (var k in this._currentElements) {
                                        if (!this._currentElements.hasOwnProperty(k) || !this._currentElements[k]) {
                                            continue;
                                        }
                                        labels.push(this._currentElements[k].name);
                                        if (typeof (values[this._currentElements[k].id]) === 'undefined') {
                                            values[this._currentElements[k].id] = [];
                                        }
                                        values[this._currentElements[k].id].push(this._currentElements[k].id);
                                        values[this._currentElements[k].id].push(this._currentElements[k].name);
                                    }
                                    if (labels.join(', ')) {
                                        this._control.setData(labels.join(', '), JSON.stringify(values));
                                    } else {
                                        this._control.removeSquares();
                                    }
                                } else {
                                    this._currentElements.push(element);
                                    this._control.setData(element.name, element.id);
                                    this.close();
                                }
                            }
                        };
                    BX.FilterHandlerNxDerectory.closeAll = function () {
                        for (var k in this.items) {
                            if (this.items.hasOwnProperty(k)) {
                                this.items[k].close();
                            }
                        }
                    };
                    BX.FilterHandlerNxDerectory.items = {};
                    BX.FilterHandlerNxDerectory.create = function (id, settings) {
                        var self = new BX.FilterHandlerNxDerectory(id, settings);
                        self.initialize(id, settings);
                        BX.FilterHandlerNxDerectory.items[self.getId()] = self;
                        return self;
                    }
                }
            })();
        </script>
        <?
        $script = ob_get_contents();
        ob_end_clean();
        return $script;
    }

    public static function AddFilterByHlb($property, $controlSettings, &$filter, &$filtered)
    {

        $filtered = false;

        $filterOption = new \Bitrix\Main\UI\Filter\Options($controlSettings["FILTER_ID"]);
        $filterData = $filterOption->getFilter();
        if (!empty($filterData[$controlSettings['VALUE']]))
            $currentValue = $filterData[$controlSettings['VALUE']];

        if (!empty($currentValue)) {
            try {
                $values = array();
                global $APPLICATION;
                $currentValue = $APPLICATION->ConvertCharset($currentValue, 'UTF-8', LANG_CHARSET);
                $currentValues = Json::decode($currentValue);
                if (is_array($currentValues)) {
                    foreach ($currentValues as $value) {
                        $values[] = current($value);
                    }
                } else {
                    $values[] = $currentValues;
                }
                if (!empty($values)) {
                    $filter[$controlSettings['VALUE']] = array();
                    foreach ($values as $value) {
                        $filter[$controlSettings['VALUE']][] = $value;
                    }
                    $filtered = true;
                }
            } catch (SystemException $e) {
                return;
            }
        }
    }

}