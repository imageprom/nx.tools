# Аудит модуля `nx_tools` (версия для PHP 8.3)

Ветка: `nx_ai_audit`. Объект проверки: папка `8.3/nx_tools`. Папка `7.4` не рассматривалась.
Код **не исправлялся**: это только отчёт. Каждый пункт нужно согласовать, потому что часть поведения может быть задумана специально.

## Как проводилась проверка

- Прочитаны все PHP- и JS-файлы модуля, кроме сторонней библиотеки `chosen.jquery.js`.
- Все PHP-файлы проверены линтером PHP 8.3.6: `php -l`, `error_reporting=-1`.
- Поведение PHP 8.3 в спорных местах (ошибки `Error` и `TypeError`) проверено отдельными мини-скриптами.
- Ядра Битрикса в репозитории нет, поэтому модуль **не запускался на живом сайте**. Утверждения о внутреннем поведении ядра помечены «*(проверить на стенде)*».

Уровни важности:

| Метка | Значение |
|---|---|
| **[К]** | Критично: фатальная ошибка или полная неработоспособность функции |
| **[Б]** | Безопасность |
| **[О]** | Ошибка логики или отображения, PHP-предупреждения |
| **[Р]** | Рекомендация, расхождение с гайдами Битрикса или D7 |
| **[?]** | Вопрос: баг это или так задумано |

---

## 1. Удаление модуля

Файлы: `install/index.php`, `install/unstep.php`.

| # | Ур. | Где | Проблема | Что предлагается |
|---|---|---|---|---|
| 1.1 | **[К]** | `install/index.php:215-216` | `DeleteDirFiles(..., true, true)`. Третий параметр функции в ядре — массив исключений, внутри вызывается `in_array($entry, $arExept)`. На PHP 8 передача `true` даёт **`TypeError: in_array(): Argument #2 ($haystack) must be of type array, true given`** (проверено на PHP 8.3). Скорее всего, поэтому вызов `UnInstallFiles()` закомментирован (`:270`). При его включении удаление будет падать. | Убрать лишние аргументы. Для каталогов (компонент, JS) использовать `DeleteDirFilesEx('/local/components/ip/nx.hlblock.element.selector')` и `DeleteDirFilesEx('/local/js/nx_tools')`. |
| 1.2 | **[О]** | `install/index.php:211` | `$this->path` — такого свойства нет (есть статическое `self::$path`). Будет предупреждение. Переменная `$instPath` дальше не используется. | Удалить строку. |
| 1.3 | **[О]** | `install/index.php:213` | `DeleteDirFiles` не рекурсивна. Для `admin` это работает, но для `components/ip/...` (подкаталоги) файлы не удалятся даже после исправления 1.1. | См. 1.1: использовать `DeleteDirFilesEx`. |
| 1.4 | **[О]** | `install/index.php:268` и `install/unstep.php:45` | Форма отправляет флажок `savedata`, код читает `$_REQUEST['save_tables']`. Каждый раз появляется предупреждение `Undefined array key "save_tables"`, а значение никогда не передаётся. При включённом `debug` в `.settings.php` предупреждение видно на странице удаления. | Привести имена к одному. В D7: `Application::getInstance()->getContext()->getRequest()->get('savedata')`. |
| 1.5 | **[?]** | `install/unstep.php:44-51` | Флажки «Сохранить таблицы» и «Сохранить почтовые шаблоны» скопированы из другого модуля. У `nx_tools` нет ни своих таблиц, ни почтовых шаблонов. | Убрать флажки или наполнить их смыслом (см. 1.8). |
| 1.6 | **[Р]** | `install/index.php:65` и `:272` | Порядок действий: сначала `UnRegisterModule()`, потом `UnInstallEvents()`. Правильно наоборот: снять обработчики, удалить файлы, затем снять регистрацию модуля. Иначе при ошибке на середине в `b_module_to_module` останутся «висящие» обработчики. `UnRegisterModule` — устаревшая функция. | `UnInstallEvents()` → `UnInstallFiles()` → `UnInstallDB()`. Внутри `UnInstallDB` вызывать `\Bitrix\Main\ModuleManager::unRegisterModule()`. |
| 1.7 | **[Р]** | `install/index.php:60` | Прямой SQL к `b_file`. Строки модуль в `b_file` не пишет, так что код фактически мёртвый. | Удалить или заменить на `\Bitrix\Main\FileTable::getList(['filter' => ['=MODULE_ID' => $this->MODULE_ID]])` + `CFile::Delete`. |
| 1.8 | **[?]** | вся процедура | Модуль не проверяет, остались ли в системе пользовательские поля типов `nx_hlblock` / `nx_user` и свойства инфоблоков `nx_directory`. После удаления такие поля останутся «осиротевшими»: тип не найден, редактор не отрисуется. | Предупреждать на шаге 1 удаления или запрещать удаление, пока поля существуют. |
| 1.9 | **[О]** | `install/index.php:263,274` | При удалении заголовок окна — `NX_TOOLS_INSTALL_TITLE` («Установка…»). Ключ `NX_TOOLS_UNINSTALL_TITLE` есть в языковом файле, но не используется. | Подставить `NX_TOOLS_UNINSTALL_TITLE`. |
| 1.10 | **[Р]** | `install/index.php:225,255` | `global $step`, `$_REQUEST`, `global $errors`. Свойство `$this->errors` всегда `null`, ошибки никуда не попадают. | D7: брать `step` из `Request`. Ошибки передавать через `$APPLICATION->ThrowException()`: `step.php`/`unstep.php` их уже читают через `GetException()`. |
| 1.11 | **[О]** | `install/index.php:120-125,170-185` | Регистрируется и снимается обработчик `NXTools\CNXUserTypeOrder::getDescription`, а **класса `CNXUserTypeOrder` в модуле нет**. При удалении это не мешает, но см. 2.2. | Убрать регистрацию, пока класс не написан. Снятие можно оставить для «чистки» старых установок. |
| 1.12 | **[О]** | `install/index.php:85-92,138-145` | Обработчик `OnBeforeProlog` указывает на `/modules/nx_tools/before.php`. Такого файла нет. Поскольку указан `TO_MODULE_ID`, ядро на каждом хите просто подключает модуль (`include.php`), а сам файл не вызывается. Итог: лишняя нагрузка на **каждой** странице сайта (см. 4.1). | Удалить регистрацию или создать `before.php`, если он нужен. |

**Вывод по удалению.** Главная причина падения, которую видно в коде, — пункт 1.1 (`TypeError` в `DeleteDirFiles`). Её, вероятно, обошли, закомментировав `UnInstallFiles()`. В текущем виде удаление проходит, но выдаёт предупреждение из пункта 1.4 и не удаляет файлы. **Если вы видите другую ошибку, пришлите её текст или лог** (`exception_handling` → `log` в `/bitrix/.settings.php`): без ядра я не могу воспроизвести её точно.

---

## 2. Критические ошибки (фатальные на PHP 8.3)

| # | Где | Проблема | Проверено |
|---|---|---|---|
| 2.1 | `classes/CNXUserTypeHlblock.php:27,40` | `public const USER_TYPE_ID = StringType::USER_TYPE_ID;` в пространстве имён `NXTools` без `use`. Имя разрешается в `NXTools\StringType`, такого класса нет. `getDescription()` обращается к константе, получаем **`Error: Class "NXTools\StringType" not found`**. Обработчик `OnUserTypeBuildList` вызывается везде, где строится список типов пользовательских полей: список UF, редактирование элемента HL-блока, профиль пользователя с UF. | да, PHP 8.3 |
| 2.2 | `install/index.php:120` | Обработчик `OnUserTypeBuildList` → `NXTools\CNXUserTypeOrder::getDescription`, класса нет. Если ядро не проверяет `is_callable`, это `TypeError` в тех же местах, что и 2.1. *(проверить на стенде)* | нет |
| 2.3 | `classes/CNXUserTypeHlblock.php:60,63,132,142`, `classes/CNXUserTypeUser.php:53,92,102` | `self::UserRow(...)` вызывается из `static`-методов, а `UserRow` объявлен **нестатическим** (`:69` и `:144`). PHP 8: **`Error: Non-static method ...::UserRow() cannot be called statically`**. Падает форма редактирования любого поля этих типов. | да |
| 2.4 | `classes/CNXUserTypeHlblock.php:164`, `classes/CNXUserTypeUser.php:123` | `$arUserField[FIELD_NAME]` вне строки (без кавычек). PHP 8: **`Error: Undefined constant "NXTools\FIELD_NAME"`**. Падает `renderAdminListEdit`, то есть редактирование в списке. Внутри heredoc (`:169`, `:128`) такая запись допустима. | да |
| 2.5 | `classes/CNXUserTypeHlblock.php:58,123`, `classes/CNXUserTypeUser.php:83` | `current($arHtmlControl["VALUE"])`. У немножественного поля `VALUE` — скаляр. PHP 8: **`TypeError: current(): Argument #1 ($array) must be of type array, string given`**. В `:123` и `:83` массив гарантирован строкой выше, а в `:58` — нет. | да |
| 2.6 | `classes/CNXUserTypeHlblock.php`, `classes/CNXPropertyHlblock.php` | Внутри методов используется `?> ... <?` (короткий тег). При `short_open_tag=Off` (значение по умолчанию в `php.ini-production`) — **Parse error** при автозагрузке класса. Линтер PHP 8.3 с настройками по умолчанию падает именно на этих двух файлах. Ещё 23 файла начинаются с `<?`, кроме шаблонов. | да |
| 2.7 | `install/components/ip/nx.hlblock.element.selector/class.php:110-111,161-162`, `ajax.php:60-62` | `getEntityDataClass()` возвращает `false`, если HL-блок не найден. Дальше вызывается `$entity_data_class::getList(...)` без проверки, получаем фатальную ошибку. Случается, например, при пустом `HLBLOCK_TABLE` или удалённом HL-блоке. | по коду |
| 2.8 | `prolog.php:4` | `define('ADMIN_MODULE_NAME", "ip.nx_tools');`: кавычки перепутаны, это **один** аргумент. Получаем `ArgumentCountError`. Сейчас файл нигде не подключается, то есть он мёртвый. Ссылается на несуществующие `nx_tools_list.php` и `/bitrix/images/ip.nx_tools/`. | да |

---

## 3. Безопасность

| # | Ур. | Где | Проблема | Что предлагается |
|---|---|---|---|---|
| 3.1 | **[Б]** | `install/components/.../ajax.php` | Проверяется только `sessid`; авторизации и прав нет. Любой посетитель сайта (`sessid` есть на любой странице) может получить список пользователей (`LOGIN`, `NAME`, `LAST_NAME`) и содержимое **любого** HL-блока по ID или имени таблицы. Фильтры `'=ID'` и `'?NAME'` для старого `CUser::GetList`, вероятно, не распознаются *(проверить)*. Тогда возвращаются **все** пользователи без лимита. | Как минимум: проверка `$USER->IsAuthorized()` и права (например, `CanDoOperation('view_all_users')` и права на HL-блок). В духе D7: перенести в `Controllerable`-действие компонента (`BX.ajax.runComponentAction`) с префильтрами `Authentication` и `Csrf`. Для пользователей — `UserTable::getList` с `limit` и фильтром `%NAME`. |
| 3.2 | **[Б]** | `admin/nx_highloadblock_rows_list_search.php` | Нет проверки прав на HL-блок, в отличие от оригинального `highloadblock_rows_list.php`. Любой, у кого есть доступ в админку, видит строки любого HL-блока. | `HL\HighloadBlockRightsTable::getOperationsName($ENTITY_ID)`, при пустом результате — `AuthForm`. |
| 3.3 | **[Б]** | `admin/nx_user_search.php:183,187,212` | `$f_NAME` экранируется `htmlspecialcharsbx`, но одинарная кавычка не экранируется, и строка подставляется в JS `SetValue('..', '$f_NAME')`. Пользователь может сам указать в своём имени `'` и JS, а сработает это у администратора: **хранимая XSS**. | `CUtil::JSEscape()` и затем `htmlspecialcharsbx()`. |
| 3.4 | **[Б]** | `admin/nx_highloadblock_rows_list_search.php:258-267` | `UF_XML_ID` попадает в JS-строку `SelEl('...')` без экранирования. | `CUtil::JSEscape`. |
| 3.5 | **[Б]** | `classes/CNXUserTypeDirectory.php:91,104`, `classes/CNXUserTypeHlblock.php:89,94`, `classes/CNXUserTypeUser.php:161` | Значение поля и название элемента HL (`UF_NAME`) выводятся в HTML без `htmlspecialcharsbx`. | Экранировать. |
| 3.6 | **[Б]** | `templates/nx_select_search_1.0/template.php:28-33`, `.../script.js:37` | `HLBLOCK_TABLE`, `TYPE`, `SEARCH_INPUT_ID`, `NAME` выводятся без экранирования. В JS `item.NAME` вставляется в HTML строкой. | `HtmlFilter::encode` в шаблоне. В JS — `new Option(item.NAME, item.ID)` или `BX.util.htmlspecialchars`. |

---

## 4. Ошибки логики, предупреждения и совместимость с PHP 8.x

| # | Ур. | Где | Проблема |
|---|---|---|---|
| 4.1 | **[О]** | `include.php:9-10` | На **каждом** хите (через `OnBeforeProlog`, см. 1.12) подключаются `admin_tools.php` и `filter_tools.php`, в том числе в публичной части. Для модуля это не нужно: админ-страницы подключают их сами. Константы `CACHED_b_nx_tools`, `NX_TOOLS_CACHE_TIME` и `$GLOBALS['NX_TOOLS_CACHE']` нигде не используются. |
| 4.2 | **[О]** | `classes/CNXUserTypeUser.php:215` против `:224` | После переписывания на D7 в `select` пропал `LOGIN`, а вывод его использует: в списке отображается `() Имя Фамилия`. Это тот самый TODO «проверить соответствие старому». |
| 4.3 | **[О]** | `classes/CNXUserTypeHlblock.php:58-61` | В цикле `$result = ...` вместо `.=`: у множественного значения отображается только последнее. |
| 4.4 | **[О]** | `classes/CNXUserTypeHlblock.php:94` | `$inputValsName` не определена, если элемент не найден: предупреждение. Перед `->GetNext()` (`:83`) нет проверки `$rsEnum`. |
| 4.5 | **[О]** | `classes/CNXUserTypeHlblock.php:129`, `classes/CNXUserTypeUser.php:89` | `$fieldHtml .=` без инициализации: предупреждение. Переменные `$tag_name_escaped` и `$rowstmp` не используются. |
| 4.6 | **[О]** | `classes/CNXUserTypeHlblock.php:159-178` и `classes/CNXUserTypeUser.php:118-137` | Генерируется JS-функция `addNewRowUser...`, но кнопка «Добавить» вызывает другую, `addNewRow`, из `nx_usertype.js`. Сгенерированный код мёртвый. В классе HL-блока он к тому же ведёт на `nx_user_search.php` (копипаст из класса пользователя). |
| 4.7 | **[О]** | `classes/CNXUserTypeDirectory.php:117`, `classes/CNXUserTypeHlblock.php:275` | Необязательный параметр стоит перед обязательным. PHP 8: `Deprecated`, значение по умолчанию игнорируется. |
| 4.8 | **[О]** | везде, где `strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"])`, `strlen(trim($find_...))` | `null` в строковые функции: `Deprecated` с PHP 8.1. |
| 4.9 | **[О]** | `classes/CNXUserTypeDirectory.php:25`, `classes/CNXUserTypeUser.php:25` | `USER_TYPE_ID` переопределена как `'string'`. В `Directory` это та же проблема, что 2.1 (класс `NXTools\StringType` не найден), но константа там пока не используется. В `User` она указывает на `StringType`, хотя тип наследуется от `EnumType`. В D7-типах константа `USER_TYPE_ID` должна совпадать с ID самого типа (`nx_user`): базовый класс использует `static::USER_TYPE_ID`, например при выборе компонента отрисовки. *(проверить на стенде)* |
| 4.10 | **[?]** | `classes/CNXUserTypeUser.php:23` | Привязка к пользователю наследуется от `EnumType` (список), хотя хранит ID пользователя. Унаследованные `checkFields` и `getList` рассчитаны на значения списка `b_user_field_enum`. Возможны ошибки валидации при сохранении. *(проверить)* Логичнее наследоваться от `IntegerType` или `BaseType`. |
| 4.11 | **[О]** | `install/components/.../class.php:126-141,184-201` | Для `TYPE = user` результат складывается в локальную `$elements` и **не попадает** в `arResult`. «Последние» и «текущие» пользователи всегда пустые, и выбранный пользователь не отображается в фильтре. |
| 4.12 | **[О]** | `class.php:129,190`, `ajax.php:86` | `CUser::GetList(($by = "..."), ...)`: передача выражения по ссылке, notice «Only variables should be passed by reference». Сортировка по `personal_country` выглядит случайной. |
| 4.13 | **[О]** | `ajax.php:50` | `'?UF_NAME' => '%строка%'`: оператор `?` — логический поиск, `%` в нём лишние. | 
| 4.14 | **[О]** | `ajax.php:24-28` | Чтобы загрузить класс, компонент подключается с пустыми параметрами: выполняется `executeComponent` и шаблон. Правильно — `CBitrixComponent::includeComponentClass('ip:nx.hlblock.element.selector')` (или см. 3.1). |
| 4.15 | **[О]** | `admin/nx_highloadblock_rows_list_search.php:115,157,166,267,387` | Неопределённые `$canEdit`, `$canDelete`, `$FIELDS`, `$jsPath`, `$nameSeparator`, `$k`: предупреждения. Редактирование и удаление фактически выключены, что для окна выбора, видимо, правильно (см. 8.4). |
| 4.16 | **[О]** | `admin/nx_user_search.php:191` | `$arRes[""]` вместо `$arRes["PERSONAL_PROFESSION"]`. |
| 4.17 | **[О]** | `admin/nx_user_search.php`, 12 строк комментариев | Файл когда-то был в cp1251; при конвертации комментарии превратились в `�` (U+FFFD), восстановить их нельзя. Файл — копия ядрового `user_search.php` 2007 года. |
| 4.18 | **[О]** | `classes/CNXPropertyHlblock.php:236-237,298` | Отладочные `console.log` оставлены в коде. В `:49` при ошибке JSON в HTML фильтра выводится текст исключения. |
| 4.19 | **[О]** | `classes/CNXPropertyHlblock.php:44-45,369` | `ConvertCharset(..., 'UTF-8', LANG_CHARSET)` — наследие однобайтовых сайтов. Если сайт в UTF-8 (обязательно для новых версий), это лишнее. |
| 4.20 | **[О]** | `install/js/nx_usertype_directory.js:3-121` | Глобально **переопределяет ядровую** `BX.IBlock.Tools.addNewRow` своей (старой) копией. JS подключается везде, где строится список типов (см. 5.4), поэтому после обновлений ядра может сломаться кнопка «Добавить» у множественных свойств инфоблоков. |
| 4.21 | **[О]** | `templates/*/script.map.js` | Source map-файлы от ядрового `iblock.element.selector` (`BX.Iblock.IblockElementSelector`), а у `nx_select_search_1.0` карта лежит рядом с совершенно другим скриптом. Они не соответствуют исходникам. |
| 4.22 | **[О]** | `templates/nx_select_search_1.0/script.js:30` | `async: false`: синхронный AJAX блокирует интерфейс и объявлен устаревшим в браузерах. |

---

## 5. Языковые файлы и текст, вписанный прямо в код

### 5.1 Строки, которые надо вынести в `lang`

| Файл:строка | Текст | Предлагаемый ключ |
|---|---|---|
| `classes/CNXUserTypeDirectory.php:36` | `NX - привязка к элементам highloadblock` | `NX_TOOLS_UT_DIRECTORY_DESCRIPTION` |
| `classes/CNXUserTypeHlblock.php:39` | `NX - привязка к элементам highloadblock` | `NX_TOOLS_UT_HLBLOCK_DESCRIPTION` |
| `classes/CNXUserTypeUser.php:36` | `NX - привязка к Пользователям` | `NX_TOOLS_UT_USER_DESCRIPTION` |
| `classes/CNXUserTypeHlblock.php:153`, `classes/CNXUserTypeUser.php:112` | `Добавить` | `NX_TOOLS_UT_ADD_BUTTON` |
| `admin/nx_highloadblock_rows_list_search.php:266`, `admin/nx_user_search.php:210` | `Выбрать` | `NX_TOOLS_ADMIN_SELECT` |
| `templates/nx_select_search_1.0/template.php:30` | `(все)` | `IEST_ALL` |
| `templates/nx_select_search_1.0/script.js:3` | `Нет элемента` | передать в JS через `BX.message` |
| `templates/nx_select_search_1.0/script.js:35` | `(все)` | передать в JS через `BX.message` |

Также захардкожен **`lang=ru`** в URL всплывающих окон: `CNXUserTypeDirectory.php:103`, `CNXUserTypeHlblock.php:93,171`, `CNXUserTypeUser.php:130,165`. Нужно подставлять `LANGUAGE_ID`. Ещё используется устаревшая константа `LANG`: `CNXUserTypeUser.php:224`, `nx_highloadblock_rows_list_search.php:33`.

### 5.2 Отсутствующие языковые файлы

Код вызывает `IncludeModuleLangFile(__FILE__)` или `Loc::loadMessages(__FILE__)`, а файлов нет:

- `lang/{ru,en}/include.php`
- `lang/{ru,en}/classes/CNXUserTypeDirectory.php`, `CNXUserTypeHlblock.php`, `CNXUserTypeUser.php`
- `lang/{ru,en}/admin/nx_highloadblock_rows_list_search.php`, `nx_user_search.php`

### 5.3 Сообщения «чужих» модулей

Код использует ключи, которые есть только в языковых файлах других модулей. Работает это только тогда, когда эти файлы случайно уже подключены:

- `admin/nx_highloadblock_rows_list_search.php`: `HLBLOCK_ADMIN_ROWS_LIST_PAGE_TITLE`, `HLBLOCK_ADMIN_ROWS_LIST_NOT_FOUND`, `HLBLOCK_ADMIN_ROWS_ADD_NEW_BUTTON` из модуля `highloadblock`. Здесь они **не загружаются**, поэтому заголовок окна и сообщение «не найден» пустые.
- `classes/CNXUserTypeUser.php:224`: `MAIN_EDIT_USER_PROFILE`, не загружен, поэтому пустой `title`.
- `classes/CNXUserTypeHlblock.php`: `USER_TYPE_HLEL_*`, `USER_TYPE_ENUM_DISPLAY`; `classes/CNXUserTypeDirectory.php`: `HIBLOCK_PROP_DIRECTORY_*`. Берутся из языковых файлов родительских классов ядра и подгружаются вместе с ними. Работает, но зависит от ядра.

Рекомендация: завести собственные ключи с префиксом `NX_TOOLS_` в своих файлах.

### 5.4 Прочее по языковым файлам

- `lang/{ru,en}/install/step.php` и `unstep.php` пустые: используются общие `MOD_*` из `main`. Так допустимо.
- Ключ `NX_TOOLS_UNINSTALL_TITLE` не используется (см. 1.9). `NX_TOOLS_MODULE_TITLE` используется только в мёртвом `prolog.php`.
- Языковые файлы шаблона `nx_select_search_1.0` содержат 5 ключей, ни один не используется в этом шаблоне.
- `IES_MODULE_NOT_INSTALLED` использует плейсхолдер `MODULE_ID` без решёток: замена срабатывает случайно. Принято писать `#MODULE_ID#`.
- В ru-описании модуля опечатка: «инструмен**а**тами».
- Смешаны `GetMessage` / `IncludeModuleLangFile` и `Loc::getMessage` / `Loc::loadMessages`. В D7 используется только `Loc`.
- Ключи в `ru` и `en` совпадают. Кодировка всех `lang`-файлов — UTF-8, без проблем.

---

## 6. Расхождения с гайдами Битрикса и D7

| # | Ур. | Что | Как по гайдам / D7 |
|---|---|---|---|
| 6.1 | **[?]** | ID модуля `nx_tools` без префикса партнёра. Компонент в пространстве `ip:`. | Для партнёрских модулей (и обязательно для Marketplace) формат `partner.module`, например `ip.nxtools`, а класс установщика `ip_nxtools`. Такой модуль отображается в «Решениях Marketplace». Для внутреннего модуля в `/local/modules` допустимо как есть. |
| 6.2 | **[Р]** | `CModule::AddAutoloadClasses` + папка `classes/` | `\Bitrix\Main\Loader::registerAutoLoadClasses('nx_tools', [...])` или PSR-4-автозагрузка из `lib/` (`lib/usertype/hlblock.php` → `\NXTools\UserType\Hlblock`). Минимальное изменение — только замена вызова. |
| 6.3 | **[Р]** | `UnRegisterModule`, `$DB->Query`, `CUser::GetList`, `CModule::IncludeModule`, `GetMessage` | `ModuleManager::unRegisterModule`, ORM (`FileTable`, `UserTable`), `Loader::includeModule`, `Loc::getMessage`. |
| 6.4 | **[Р]** | JS подключается через `Asset::addJs()` внутри `getDescription()` / `GetUserTypeDescription()` | Эти методы вызываются при построении списка типов, в том числе в публичной части. JS попадает на страницы, где он не нужен. Правильно: зарегистрировать расширение (`CJSCore::RegisterExt` в `include.php` или D7-extension с `config.php`) и вызывать `CJSCore::Init` / `Extension::load` в методах отрисовки. |
| 6.5 | **[О]** | Путь JS: код ждёт `/local/js/nx_tools/*.js`, а `InstallFiles()` копирует из несуществующего `install/install/js` (`install/index.php:201`) в корень `/local/js`. К тому же `InstallFiles()` закомментирован (`:240`). | Источник `install/js`, назначение `/local/js/nx_tools` (или `/bitrix/js/nx_tools`). См. 8.1. |
| 6.6 | **[Р]** | `install/admin/*.php` жёстко ссылаются на `/local/modules/nx_tools/...` | Чтобы модуль работал и из `/bitrix/modules`: `require getLocalPath('modules/nx_tools/admin/...')` или проверка `file_exists` для обоих путей, как в ядровых модулях. |
| 6.7 | **[Р]** | AJAX компонента — отдельный `ajax.php` с прямым адресом `/local/components/...` (прописан в `.default/script.js:36` и `nx_select_search_1.0/script.js:27`) | D7: `Controllerable` + `configureActions()` + `BX.ajax.runComponentAction`. Если оставлять `ajax.php`, путь брать из `$componentPath`, а не хардкодить. |
| 6.8 | **[Р]** | У компонента нет `.description.php` (и `.parameters.php`) | По стандарту компонента обязателен `.description.php`. Без него компонент не виден в визуальном редакторе. |
| 6.9 | **[Р]** | `class.php:12`: `CJSCore::Init(['jquery'])` на уровне файла (jQuery 1.x) | Подключать в шаблоне `nx_select_search_1.0`, которому нужен `chosen`, и использовать `jquery3`. `onIncludeComponentLang` + `includeComponentLang` — устаревший приём: достаточно `Loc::loadMessages`. |
| 6.10 | **[Р]** | `templates/nx_select_search_1.0/template.php:24`: `$APPLICATION->SetAdditionalCSS` | В шаблоне: `$this->addExternalCss()`. |
| 6.11 | **[Р]** | Короткие теги `<?` | В PHP-классах и файлах без HTML — `<?php`, а закрывающий `?>` в конце файла не ставить (см. 2.6). |
| 6.12 | **[Р]** | В установщике нет `PARTNER_NAME` / `PARTNER_URI` | Рекомендуется заполнять: отображается в списке модулей. |
| 6.13 | **[Р]** | `MODULE_GROUP_RIGHTS = 'Y'`, но права модуля нигде не проверяются (`$APPLICATION->GetGroupRight('nx_tools')`) | Либо проверять права в админ-страницах, либо поставить `'N'`. |
| 6.14 | **[Р]** | Типы пользовательских полей написаны в старом стиле (`UserRow`, HTML-строки, `jsUtils.OpenWindow`), хотя наследуются от D7-классов | Без большой переделки: оставить разметку, но привести сигнатуры к родителю (`renderEditForm`, `renderAdminListEdit`, `renderFilter`) и описание брать через `parent::getDescription()` с заменой `USER_TYPE_ID` / `CLASS_NAME` / `DESCRIPTION`, как уже сделано в `CNXUserTypeDirectory`. Тогда `BASE_TYPE` и остальные ключи будут корректными. |

---

## 7. Прочее

- `.gitignore` (корень репозитория) содержит `*.md`: этот `ReadMe.md` пришлось добавить принудительно (`git add -f`). Если документация в репозитории нужна, правило стоит сузить.
- `install/images/`: файлы называются `nx_market*.gif` (от другого модуля), есть дубль `nx_market.gif.gif`. Картинки нигде не используются, кроме мёртвого `prolog.php`.
- Пути `/bitrix/images/nx_tools/` (установка) и `/bitrix/images/ip.nx_tools/` (`prolog.php`) не совпадают.
- `classes/CNXPropertyHlblock.php`: опечатка в имени `FilterHandlerNxDerectory` (Directory). Менять стоит только вместе со всеми вызовами.
- `admin/nx_user_search.php`: объявляет глобальную функцию `CheckFilter()`. Если на той же странице подключится ядровой файл с такой же функцией, получим `Cannot redeclare`. Лучше дать функции префикс или сделать её анонимной.

---

## 8. Вопросы к вам: баг или так задумано?

1. **`InstallFiles()` и `UnInstallFiles()` закомментированы** (`install/index.php:240,270`). Без них не копируются админ-страницы (`nx_*_search.php`), JS и компонент, и типы полей не работают. Файлы раскладываются вручную, или это временный обход 1.1?
2. **ID модуля без префикса партнёра** (6.1): модуль только внутренний или планируется Marketplace?
3. **Удаление при существующих полях** модуля (1.8): запрещать, предупреждать или удалять поля вместе с модулем?
4. **Окно выбора HL-элемента**: редактирование и удаление строк там отключено (неопределённые `$canEdit` / `$canDelete`). Так и нужно, только выбор?
5. **`nx_user` от `EnumType`** (4.10): осознанный выбор?
6. **`OnBeforeProlog` → `before.php`** и **`CNXUserTypeOrder`**: в планах или удалить?
7. **Флажки сохранения данных** при удалении (1.5): убрать?

---

## 9. Предлагаемый порядок исправлений (после согласования)

1. Критичные ошибки из раздела 2 (2.1–2.7). Каждая правится 1–3 строками.
2. Удаление и установка (раздел 1): аргументы `DeleteDirFiles`, `savedata`, порядок действий, заголовок, пути JS, включение `Install/UnInstallFiles` (по ответу на вопрос 8.1).
3. Безопасность (раздел 3): проверки прав в `ajax.php` и админ-страницах, экранирование.
4. Языковые файлы (раздел 5).
5. Рекомендации D7 (раздел 6): по одной, без изменения поведения.
