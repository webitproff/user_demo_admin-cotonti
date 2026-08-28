<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=tools
[END_COT_EXT]
==================== */

/**
 * ============================================================
 * ДОКУМЕНТАЦИЯ ПО ХУКУ `tools` И ФАЙЛУ user_demo_admin.admin.php
 * ============================================================
 *
 * Хук `tools` в Cotonti используется для подключения административных страниц
 * плагинов через раздел «Администрирование» (admin.php?m=other&p=<код_плагина>).
 *
 * Файл user_demo_admin.admin.php является точкой входа в админ-панель плагина
 * и обрабатывает действия:
 *   - просмотр списка демо-администраторов;
 *   - создание нового пользователя с правами демо-администратора;
 *   - настройка прав группы демо-администраторов.
 *
 * Path:     plugins/user_demo_admin/user_demo_admin.admin.php
 *
 * =====================================================================
 * Административная панель плагина User Demo Admin
 * =====================================================================
 *
 * Файл подключается к хуку `tools` и обрабатывает три вкладки:
 *   - list   — список демо-администраторов;
 *   - create — создание нового пользователя с правами демо-админа;
 *   - rights — точечная настройка прав группы демо-админов.
 *
 *
 *
 * Source and updates   https://github.com/webitproff/user_demo_admin-cotonti
 * ReadMeMore:          https://abuyfile.com/ru/market/cotonti/plugs/user-demo-admin
 * Support:             https://abuyfile.com/ru/forums/cotonti/custom/plugs
 *
 * Authorization Management API:     
 * https://github.com/Cotonti/Cotonti/blob/master/system/auth.php
 *
 * Group constants 
 * https://github.com/Cotonti/Cotonti/blob/f43f1fc38ba4e02027786dad9dac1435c7c52b30/system/functions.php#L32
 * 
 * User and Authorization Subsystem 
 * https://github.com/Cotonti/Cotonti/blob/f43f1fc38ba4e02027786dad9dac1435c7c52b30/system/functions.php#L1709
 *
 * Date: Aug 29, 2026
 *
 * @package user_demo_admin
 * @version 5.3.1
 * @author webitproff
 * @copyright Copyright (c) 2026 | https://github.com/webitproff
 * @license BSD
 */

/**
 * Admin panel for User Demo Admin
 * Path: plugins/user_demo_admin/user_demo_admin.admin.php
 */




defined('COT_CODE') or die('Wrong URL'); // Защита от прямого обращения

/* =====================================================================
 * ПОДКЛЮЧЕНИЕ НЕОБХОДИМЫХ ФАЙЛОВ
 * ---------------------------------------------------------------------
 * Подключаем языковой файл модуля users (стандартные сообщения об
 * ошибках пользователей), API форм (функции cot_inputbox, cot_shield_protect
 * и др.) и функции нашего плагина.
 *
 * Языковой файл плагина уже подключён внутри functions.php, поэтому здесь
 * его не дублируем.
 * ===================================================================== */
require_once cot_langfile('users', 'module');
require_once cot_incfile('forms');
require_once cot_incfile('user_demo_admin', 'plug', 'functions');

/* =====================================================================
 * ЗАЩИТА ОТ ДЕМО-АДМИНА
 * ---------------------------------------------------------------------
 * Если текущий пользователь — демо-администратор, немедленно
 * прерываем выполнение и показываем сообщение о запрете доступа.
 * ===================================================================== */
if (cot_user_demo_admin_is_demo_user()) {
    cot_die_message(930);
}

/* =====================================================================
 * ПРОВЕРКА ПРАВ НАСТОЯЩЕГО АДМИНИСТРАТОРА
 * ---------------------------------------------------------------------
 * Получаем права на админ-область: бит A (администрирование).
 * Дополнительно разрешаем, если основная группа пользователя
 * является группой суперадминов (COT_GROUP_SUPERADMINS).
 * Если прав нет — cot_block() перенаправит на страницу ошибки.
 * ===================================================================== */
list(, , $isadmin) = cot_auth('admin', 'a');
if (Cot::$usr['maingrp'] == COT_GROUP_SUPERADMINS) {
    $isadmin = true;
}
cot_block($isadmin);

/* =====================================================================
 * ОПРЕДЕЛЕНИЕ ТЕКУЩЕЙ ВКЛАДКИ И ДЕЙСТВИЯ
 * ---------------------------------------------------------------------
 * $tab — текущая вкладка (list/create/rights), по умолчанию 'list'.
 * $a   — действие (например, 'add' или 'save').
 * ===================================================================== */
$tab = cot_import('tab', 'G', 'ALP') ?: 'list';
$a   = cot_import('a', 'G', 'ALP');

/* =====================================================================
 * ИНИЦИАЛИЗАЦИЯ ШАБЛОНА
 * ---------------------------------------------------------------------
 * Загружаем XTemplate-шаблон плагина.
 * ===================================================================== */
$t = new XTemplate(cot_tplfile('user_demo_admin.admin', 'plug', true));

/* =====================================================================
 * ГАРАНТИРУЕМ НАЛИЧИЕ ГРУППЫ И БАЗОВЫХ ПРАВ
 * ---------------------------------------------------------------------
 * Функция cot_user_demo_admin_ensure_group() создаёт группу демо-админа,
 * если её ещё нет, и выдаёт минимально необходимые права.
 * При ошибке показываем сообщение и завершаем формирование страницы.
 * ===================================================================== */
$groupId = cot_user_demo_admin_ensure_group();
if (!$groupId) {
    cot_error(Cot::$L['user_demo_admin_group_error']);
    cot_display_messages($t);
    $t->parse('MAIN');
    $pluginBody = $t->text('MAIN');
    return;
}

/* =====================================================================
 * ПЕРЕДАЧА ОБЩИХ ПЕРЕМЕННЫХ В ШАБЛОН
 * ---------------------------------------------------------------------
 * Устанавливаем активную вкладку и URL для переключения вкладок.
 * ===================================================================== */
// Передаём в шаблон общие переменные для отображения вкладок и ссылок
$t->assign([
    'TAB_TYPE'           => $tab, // Текущая вкладка, используется в шаблоне для условных блоков
    'TAB_LIST_ACTIVE'   => $tab === 'list'   ? 'active' : '', // CSS-класс 'active' для вкладки «Список», если она выбрана
    'TAB_CREATE_ACTIVE' => $tab === 'create' ? 'active' : '', // CSS-класс 'active' для вкладки «Создать», если она выбрана
    'TAB_RIGHTS_ACTIVE' => $tab === 'rights' ? 'active' : '', // CSS-класс 'active' для вкладки «Права», если она выбрана
    'URL_LIST'          => cot_url('admin', ['m' => 'other', 'p' => 'user_demo_admin', 'tab' => 'list']),   // URL для перехода на вкладку «Список»
    'URL_CREATE'        => cot_url('admin', ['m' => 'other', 'p' => 'user_demo_admin', 'tab' => 'create']), // URL для перехода на вкладку «Создать»
    'URL_RIGHTS'        => cot_url('admin', ['m' => 'other', 'p' => 'user_demo_admin', 'tab' => 'rights']), // URL для перехода на вкладку «Права»
]);

/* =====================================================================
 * ВКЛАДКА "СПИСОК ДЕМО-АДМИНИСТРАТОРОВ"
 * ===================================================================== */
if ($tab === 'list') {
    // Количество записей на страницу из настроек плагина (по умолчанию 20)
    $perPage = (int) (Cot::$cfg['plugin']['user_demo_admin']['perpage'] ?? 20);

    // Получаем параметры пагинации: $d — смещение, $durl — URL параметр
    list($pg, $d, $durl) = cot_import_pagenav('d', $perPage);

    // Общее количество пользователей в группе демо-админов
    $total = (int) Cot::$db->query(
        'SELECT COUNT(*) FROM ' . Cot::$db->users . ' WHERE user_maingrp = ?',
        [$groupId]
    )->fetchColumn();

    // Выполняем SQL-запрос для выборки списка демо-админов для текущей страницы
    $items = Cot::$db->query(
        'SELECT user_id, user_name, user_email, user_regdate
         FROM ' . Cot::$db->users . '
         WHERE user_maingrp = ?
         ORDER BY user_id DESC
         LIMIT ' . (int) $d . ', ' . (int) $perPage,
        [$groupId]
    )->fetchAll();

    // Передаём общее количество в шаблон
    $t->assign('LIST_TOTAL', $total);

    // Если есть записи
    if ($items) {
        // Перебираем каждую запись
        foreach ($items as $row) {
            // Передаём данные строки в шаблон
            $t->assign([
                'LIST_ROW_ID'      => $row['user_id'], // ID пользователя
                'LIST_ROW_NAME'    => htmlspecialchars($row['user_name']), // Имя с экранированием
                'LIST_ROW_EMAIL'   => htmlspecialchars($row['user_email']), // Email с экранированием
                'LIST_ROW_REGDATE' => cot_date('datetime_medium', $row['user_regdate']), // Дата регистрации в читаемом виде
                'LIST_ROW_URL'     => cot_url('users', ['m' => 'details', 'id' => $row['user_id']]), // URL профиля пользователя
            ]);
            // Парсим блок LIST_ROW для каждой строки
            $t->parse('MAIN.LIST_ROW');
        }
    } else { // Если записей нет
        // Парсим блок LIST_EMPTY (сообщение о пустом списке)
        $t->parse('MAIN.LIST_EMPTY');
    }

    // Формируем постраничную навигацию
    $pagenav = cot_pagenav(
        'admin', // Код модуля (для построения URL)
        ['m' => 'other', 'p' => 'user_demo_admin', 'tab' => 'list'], // Параметры URL
        $d, // Текущее смещение
        $total, // Общее количество записей
        $perPage, // Записей на страницу
        'd' // Имя параметра для страницы
    );
    // Передаём сгенерированные теги пагинации в шаблон
    $t->assign(cot_generatePaginationTags($pagenav));
}
/* =====================================================================
 * ВКЛАДКА "СОЗДАНИЕ ПОЛЬЗОВАТЕЛЯ"
 * ===================================================================== */
elseif ($tab === 'create') {
    $username = $email = '';

    // Обработка POST-запроса на создание
    if ($a === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        cot_shield_protect(); // Защита от CSRF

        // Получаем данные формы с фильтрацией
        $username  = cot_import('username', 'P', 'TXT', 100, true);
        $email     = cot_import('email', 'P', 'TXT', 64, true);
        $password1 = (string) cot_import('password1', 'P', 'NOC', 32);
        $password2 = (string) cot_import('password2', 'P', 'NOC', 32);

        // Валидация имени пользователя
        if (empty($username) || mb_strlen($username) < 2) {
            cot_error('aut_usernametooshort', 'username');
        }
        if (preg_match('/[<>#\'"\/]/', $username)) {
            cot_error('aut_invalidloginchars', 'username');
        }

        // Валидация email
        if (!cot_check_email($email)) {
            cot_error('aut_emailtooshort', 'email');
        }

        // Валидация пароля
        if (mb_strlen($password1) < 4) {
            cot_error('aut_passwordtooshort', 'password1');
        }
        if ($password1 !== $password2) {
            cot_error('aut_passwordmismatch', 'password2');
        }

        // Проверка уникальности имени пользователя
        $exists = Cot::$db->query(
            'SELECT user_id FROM ' . Cot::$db->users . ' WHERE user_name = ? LIMIT 1',
            [$username]
        )->fetch();
        if ($exists) {
            cot_error('aut_usernamealreadyindb', 'username');
        }

        // Проверка уникальности email (если запрещено дублирование)
        $emailExists = Cot::$db->query(
            'SELECT user_id FROM ' . Cot::$db->users . ' WHERE user_email = ? LIMIT 1',
            [$email]
        )->fetch();
        if ($emailExists && empty(Cot::$cfg['useremailduplicate'])) {
            cot_error('aut_emailalreadyindb', 'email');
        }

        // Если ошибок нет — создаём пользователя
        if (!cot_error_found()) {
            $ruser = [
                'user_name'      => $username,
                'user_email'     => mb_strtolower($email),
                'user_password'  => $password1,
                'user_maingrp'   => $groupId, // основная группа — демо-админ
                'user_hideemail' => 1,
                'user_country'   => '',
                'user_timezone'  => Cot::$cfg['defaulttimezone'] ?? 'UTC',
                'user_gender'    => 'U',
                'user_theme'     => Cot::$cfg['defaulttheme'] ?? '',
                'user_scheme'    => Cot::$cfg['defaultscheme'] ?? '',
                'user_lang'      => Cot::$cfg['defaultlang'] ?? '',
            ];

            // cot_add_user() — стандартная функция создания пользователя
            $userid = cot_add_user($ruser, null, null, null, $groupId, false);

            if ($userid) {
                cot_message(Cot::$L['user_demo_admin_created']);
                cot_redirect(cot_url('admin', [
                    'm' => 'other', 'p' => 'user_demo_admin', 'tab' => 'list'
                ], '', true));
            } else {
                cot_error(Cot::$L['user_demo_admin_create_failed']);
            }
        }
    }

    // Формируем поля формы
    $t->assign([
        'CREATE_FORM_ACTION' => cot_url('admin', [
            'm' => 'other', 'p' => 'user_demo_admin', 'tab' => 'create', 'a' => 'add'
        ]),
        'CREATE_USERNAME'  => cot_inputbox('text', 'username', htmlspecialchars($username), 'class="form-control"'),
        'CREATE_EMAIL'     => cot_inputbox('text', 'email', htmlspecialchars($email), 'class="form-control"'),
        'CREATE_PASSWORD1' => cot_inputbox('password', 'password1', '', 'class="form-control"'),
        'CREATE_PASSWORD2' => cot_inputbox('password', 'password2', '', 'class="form-control"'),
    ]);
}

/* =====================================================================
 * ВКЛАДКА "ПРАВА ГРУППЫ"
 * ===================================================================== */
elseif ($tab === 'rights') {

    // Обработка сохранения прав
    if ($a === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        cot_shield_protect();

        // Получаем массив разрешений из формы (permission[ключ] = 1/0)
        $permissions = cot_import('permission', 'P', 'ARR') ?: [];

        // Применяем каждое разрешение через функцию плагина.
        // Внутри неё уже происходит обновление кэша и прав.
        foreach ($permissions as $itemKey => $val) {
            $allowed = ((int) $val === 1);
            cot_user_demo_admin_set_permission($groupId, $itemKey, $allowed);
        }

        // Показываем сообщение об успехе и перенаправляем
        cot_message(Cot::$L['user_demo_admin_rights_saved']);
        cot_redirect(cot_url('admin', [
            'm' => 'other',
            'p' => 'user_demo_admin',
            'tab' => 'rights'
        ], '', true));
    }

    // Получаем текущие разрешения для отображения в форме
    $permissions = cot_user_demo_admin_get_permissions($groupId);

    $t->assign('RIGHTS_FORM_ACTION', cot_url('admin', [
        'm' => 'other',
        'p' => 'user_demo_admin',
        'tab' => 'rights',
        'a' => 'save'
    ]));

    // Глобальные переменные списков модулей и плагинов
    global $cot_modules, $cot_plugins_enabled;

    // Перебираем все разрешения и выводим строки таблицы
    foreach ($permissions as $itemKey => $allowed) {
        if (str_starts_with($itemKey, 'core:')) {
            $code  = substr($itemKey, 5);
            $title = Cot::$L['adm_code'][$code] ?? $code;
        } elseif (str_starts_with($itemKey, 'module:')) {
            $code  = substr($itemKey, 7);
            $title = $cot_modules[$code]['title'] ?? $code;
        } elseif (str_starts_with($itemKey, 'plug:')) {
            $code  = substr($itemKey, 5);
            $title = $cot_plugins_enabled[$code]['title'] ?? $code;
        } else {
            continue;
        }

        $t->assign([
            'RIGHTS_ROW_CODE'          => htmlspecialchars($itemKey),
            'RIGHTS_ROW_TITLE'         => htmlspecialchars($title),
            'RIGHTS_ROW_ALLOW_CHECKED' => $allowed ? 'checked="checked"' : '',
            'RIGHTS_ROW_DENY_CHECKED'  => !$allowed ? 'checked="checked"' : '',
        ]);
        $t->parse('MAIN.RIGHTS_ROW');
    }
}

/* =====================================================================
 * ВЫВОД СТРАНИЦЫ
 * ---------------------------------------------------------------------
 * Отображаем накопленные сообщения, парсим шаблон и сохраняем
 * результат в переменную $pluginBody, которая будет выведена Cotonti.
 * ===================================================================== */
// Отображаем накопленные сообщения
cot_display_messages($t);

// Парсим главный блок шаблона
$t->parse('MAIN');

// Сохраняем готовый HTML для вывода
$pluginBody = $t->text('MAIN');