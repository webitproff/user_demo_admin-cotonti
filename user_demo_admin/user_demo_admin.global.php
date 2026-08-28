<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=global
[END_COT_EXT]
==================== */

/**
 * =====================================================================
 * Защита режима демонстрации для Demo Admin
 * =====================================================================
 *
 * Данный файл подключается к глобальному хуку Cotonti и выполняет
 * блокировку любых действий, изменяющих данные, для пользователей,
 * входящих в группу демо-администраторов.
 *
 * Основные задачи:
 *   1) Заблокировать все POST-запросы, кроме входа и выхода.
 *   2) Заблокировать опасные действия, передаваемые через GET-параметры.
 *   3) Запретить демо-админу доступ к странице управления самим плагином.
 *
 * Path: plugins/user_demo_admin/user_demo_admin.global.php
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


defined('COT_CODE') or die('Wrong URL');

/* =====================================================================
 * Подключаем вспомогательные функции плагина.
 * Файл inc/user_demo_admin.functions.php содержит все необходимые
 * функции для проверки принадлежности к демо-группе, получения списков
 * опасных действий и параметров, а также формирования безопасного URL.
 * ===================================================================== */
require_once cot_incfile('user_demo_admin', 'plug', 'functions');

/* =====================================================================
 * Если пользователь не авторизован, дальнейшие проверки не нужны.
 * Гости не могут быть демо-администраторами.
 * ===================================================================== */
if (empty(Cot::$usr['id'])) {
    return;
}

/* =====================================================================
 * Проверяем, является ли текущий пользователь демо-администратором.
 * Функция cot_user_demo_admin_is_demo_user() (из functions.php) возвращает
 * true, если пользователь состоит в группе Demo Admin.
 * Если нет — выходим, не ограничивая обычных пользователей.
 * ===================================================================== */
if (!cot_user_demo_admin_is_demo_user()) {
    return;
}

/* =====================================================================
 * Получаем основные параметры запроса:
 *   $a — действие (action)
 *   $m — модуль (module)
 *   $p — плагин (plugin)
 *
 * cot_import() — стандартная функция Cotonti для безопасного получения
 * переменных из GET/POST/REQUEST с фильтрацией.
 *   'G' — источник GET,
 *   'ALP' — допустимые символы (буквенно-цифровые).
 * ===================================================================== */
$a = cot_import('a', 'G', 'ALP');
$m = cot_import('m', 'G', 'ALP');

$p = cot_import('p', 'G', 'ALP');   // для config = код расширения

$n   = cot_import('n', 'G', 'ALP');
$o   = cot_import('o', 'G', 'ALP');

$mod = cot_import('mod', 'G', 'ALP');
$pl  = cot_import('pl', 'G', 'ALP');
$tab = cot_import('tab', 'G', 'ALP');

/* =====================================================================
 * Определяем, является ли текущий POST-запрос попыткой входа или выхода.
 *
 * Демо-администратору разрешается:
 *   - выходить из системы (a=logout, POST);
 *   - входить, если он ещё не вошёл (m=login, POST).
 * Все остальные POST-запросы блокируются.
 * ===================================================================== */
$isLoginLogout = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($m === 'login' || $a === 'logout'));

/* =====================================================================
 * БЛОКИРОВКА POST-ЗАПРОСОВ
 * ---------------------------------------------------------------------
 * Если метод запроса POST и это не вход/выход, немедленно прерываем
 * выполнение, показываем предупреждение и перенаправляем пользователя
 * на безопасную страницу (referer, если он допустим, иначе очищенный URL).
 *
 * Мы не очищаем $_POST и другие суперглобальные массивы, чтобы не
 * нарушать работу последующих хуков и модулей.
 * ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLoginLogout) {
    // Выводим сообщение из языкового файла или стандартное предупреждение.
    cot_message(
        Cot::$L['user_demo_admin_demo_mode_warning'] ?? 'Demo mode: Write operations are prohibited.',
        'warning'
    );

    // Пытаемся вернуть пользователя на предыдущую страницу.
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer && cot_url_check($referer)) {
        // cot_url_check() проверяет, что referer — внутренний URL сайта,
        // защищая от открытого редиректа.
        cot_redirect($referer);
    } else {
        // Если referer недопустим, строим текущий URL без опасных параметров.
        cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
    }
    exit;
}

/* =====================================================================
 * БЛОКИРОВКА НЕСТАНДАРТНЫХ HTTP-МЕТОДОВ
 * ---------------------------------------------------------------------
 * Разрешаем только GET и POST. Любые другие методы (PUT, DELETE, PATCH
 * и т.д.) могут использоваться для изменения данных в обход ограничений,
 * поэтому блокируем их полностью.
 * ===================================================================== */
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    cot_message(
        Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.',
        'warning'
    );
    cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
    exit;
}

/* =====================================================================
 * ПОЛУЧЕНИЕ СПИСКОВ ОПАСНЫХ ДЕЙСТВИЙ И ПАРАМЕТРОВ
 * ---------------------------------------------------------------------
 * Вызываем функции из functions.php, чтобы получить актуальные списки.
 * Это позволяет централизованно управлять перечнем опасных действий
 * и не дублировать его в нескольких файлах.
 * ===================================================================== */
$dangerousActions = cot_user_demo_admin_get_dangerous_actions();
$actionParams = cot_user_demo_admin_get_action_params();

/* =====================================================================
 * БЛОКИРОВКА ОПАСНЫХ ДЕЙСТВИЙ В GET-ПАРАМЕТРАХ
 * ---------------------------------------------------------------------
 * Проходим по всем параметрам, которые могут содержать действие.
 * Если значение параметра совпадает с одним из опасных действий,
 * блокируем запрос и перенаправляем на безопасный URL.
 *
 * Пример: ?a=delete — параметр 'a' (из $actionParams) содержит 'delete'
 * (есть в $dangerousActions) → блокировка.
 * ===================================================================== */
foreach ($actionParams as $param) {
    $val = cot_import($param, 'G', 'ALP');
    if ($val && in_array($val, $dangerousActions, true)) {
        cot_message(
            Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.',
            'warning'
        );
        cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
        exit;
    }
}

/* =====================================================================
 * ЗАПРЕТ ДОСТУПА К СТРАНИЦЕ УПРАВЛЕНИЯ ПЛАГИНОМ
 * ---------------------------------------------------------------------
 * Демо-администратор не должен управлять настройками плагина
 * user_demo_admin, так как это позволило бы ему изменять права
 * группы и создавать других демо-админов.
 *
 * Проверяем, что запрошен модуль 'other' и плагин 'user_demo_admin'.
 * ===================================================================== */
if ($m === 'other' && $p === 'user_demo_admin') {
    cot_message(
        Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.',
        'warning'
    );
    cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
    exit;
}


/**
 * ---------------------------------------------------------
 * Блокировка config и details запрещённых расширений
 * ---------------------------------------------------------
 */

// /admin/config?n=edit&o=module&p=CODE
// /admin/config?n=edit&o=plug&p=CODE
if ($m === 'config' && $n === 'edit' && !empty($p) && in_array($o, ['module', 'mod', 'plug'], true)) {
    $type = ($o === 'plug') ? 'plug' : 'module';

    if (!cot_user_demo_admin_is_item_allowed($type, $p)) {
		cot_message(
			Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.',
			'warning'
		);
		cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
		exit;
    }
}

// /admin/extensions?a=details&mod=CODE
// /admin/extensions?a=details&pl=CODE
if ($m === 'extensions' && $a === 'details') {
    if (!empty($mod) && !cot_user_demo_admin_is_item_allowed('module', $mod)) {
		cot_message(
			Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.',
			'warning'
		);
		cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
		exit;
    }

    if (!empty($pl) && !cot_user_demo_admin_is_item_allowed('plug', $pl)) {
		cot_message(
			Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.',
			'warning'
		);
		cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
		exit;
    }
}