<?
/** @noinspection PhpUndefinedConstantInspection */

define('ADMIN_MODULE_NAME", "ip.nx_tools');
IncludeModuleLangFile(__FILE__);
define('ADMIN_MODULE_ICON',
       '<a href="nx_tools_list.php?lang=' . LANGUAGE_ID . '">'.
       '<img src="/bitrix/images/ip.nx_tools/nx_market.gif" width="48" height="48" border="0" '.
       'alt="' . GetMessage('NX_TOOLS_MODULE_TITLE') . '" title="' . GetMessage('NX_TOOLS_MODULE_TITLE') . '">'
       .'</a>'
      );
