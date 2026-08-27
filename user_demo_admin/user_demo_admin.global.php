<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=global
[END_COT_EXT]
==================== */

/**
 * Filename: user_demo_admin.global.php
 *
 * Защита режима демонстрации:
 * 1) Блокирует любые попытки записи в админке для Demo Admin
 * 2) Блокирует доступ к config и details запрещённых модулей/плагинов
 *
 * Path: plugins/user_demo_admin/user_demo_admin.global.php
 *
 * @package user_demo_admin
 * @version 5.1.1
 * @author webitproff
 * @license BSD
 */

/* 
 * два ключевых массива:
 * $dangerousActions — список опасных значений действий (delete, update, add, newtopic, upload и т.д.).
 * $actionParams — список имён параметров (a, action, op, do и т.д.), в которых эти значения могут передаваться.
 *
 * Цикл перебирает $actionParams, извлекает значение каждого параметра из GET и проверяет, не совпадает ли оно с одним из $dangerousActions. 
 * совпадает — запрос блокируется. 
*/

/* 
 * Пример:
  * ?a=delete → параметр a (из $actionParams) имеет значение delete (есть в $dangerousActions) → блокировка. 
*/

defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('user_demo_admin', 'plug', 'functions');

if (empty(Cot::$usr['id'])) {
    return;
}

if (!cot_user_demo_admin_is_demo_user()) {
    return;
}

$a = cot_import('a', 'G', 'ALP');
$m = cot_import('m', 'G', 'ALP');
$p = cot_import('p', 'G', 'ALP');

$isLoginLogout = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($m === 'login' || $a === 'logout'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLoginLogout) {
    $_POST = [];
    $_GET['a'] = '';
    $_REQUEST = $_GET;
    cot_message(Cot::$L['user_demo_admin_demo_mode_warning'] ?? 'Demo mode: Write operations are prohibited.', 'warning');
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer && cot_url_check($referer)) {
        cot_redirect($referer);
    } else {
        cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
    }
    exit;
}

// Список действий, которые считаются опасными для демо-администратора.
// Этот список, пока болванки и частые варианты, - дополняем в процессе тестирования
// Любое из этих значений, переданное через параметры запроса, будет заблокировано.
$dangerousActions = [
    'add',             // добавление нового элемента
    'approve',         // одобрение
    'archive',         // архивирование
    'ban',             // бан
    'clone',           // клонирование
    'config',          // изменение конфигурации
    'create',          // изменение конфигурации
    'del',             // сокращение от delete
    'delete',          // удаление
    'disable',         // отключение
    'edit',            // редактирование
    'enable',          // включение
    'export',          // экспорт данных
    'extrafields',     // управление доп. полями
    'forward',         // пересылка
    'import',          // импорт данных
    'install',         // установка расширений
    'invalidate',      // снятие проверки
    'lock',            // блокировка
    'mark',            // пометка
    'move',            // перемещение
    'publish',         // публикация
    'purge',           // полная очистка / удаление всех данных
    'read',            // отметка прочитанным
    'reject',          // отклонение
    'remove',          // удаление (альтернативное имя)
    'reply',           // ответ
    'reset',           // сброс настроек
    'restore',         // восстановление
    'rights',          // управление правами
    'save',            // сохранение изменений
    'send',            // отправка
    'sort',            // сортировка
    'status',          // смена статуса
    'toggle',          // переключение состояния
    'unban',           // снятие бана
    'uninstall',       // удаление расширений
    'unlock',          // разблокировка
    'unpublish',       // снятие с публикации
    'unread',          // отметка непрочитанным
    'unval',           // отмена валидации
    'update',          // обновление записи
    'update_rights',   // обновление прав
    'updateconfig',    // обновление конфигурации
    'val',             // валидация (подтверждение)
    'validate',        // проверка
];

// Проверяем HTTP-метод: разрешены только GET, POST 
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    cot_message(Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.', 'warning');
    cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
    exit;
}

// Проверяем дополнительные параметры действия
// Проверяем стандартный параметр действия 'a' и дополнительные,
// которые могут использоваться в сторонних модулях Cotonti.
// Этот список, пока болванки и частые варианты, - дополняем в процессе тестирования
$actionParams = [
    'a',            // стандартный в Cotonti
    'act',          // сокращение от action
    'action',       // часто в формах
    'action_name',  // имя действия
    'admin_action', // админ-действие
    'cmd',          // команда
    'do',           // действие
    'event',        // событие
    'func',         // функция
    'getaction',    // GET-действие
    'handler',      // обработчик
    'method',       // метод
    'mode',         // режим
    'op',           // операция
    'operation',    // операция
    'part',         // часть
    'postaction',   // POST-действие
    'process',      // процесс
    'section',      // секция
    'step',         // шаг
    'task',         // задача
    'tool',         // инструмент
    'type',         // тип действия
    'view',         // вид
];


foreach ($actionParams as $param) {
    $val = cot_import($param, 'G', 'ALP'); // получаем из GET
    if ($val && in_array($val, $dangerousActions, true)) {
        cot_message(Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.', 'warning');
        cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
        exit;
    }
}

/* if ($a && in_array($a, $dangerousActions, true)) {
    cot_message(Cot::$L['user_demo_admin_demo_mode_forbidden'], 'warning');
    cot_redirect(cot_url('admin'));
    exit;
} */


// Блок запрещённого модуля
if ($m === 'other' && $p === 'user_demo_admin') {
    cot_message(Cot::$L['user_demo_admin_demo_mode_forbidden'] ?? 'Demo mode: Write operations are prohibited.', 'warning');
    cot_redirect(cot_demo_admin_get_current_url_without_dangerous_params());
    exit;
}
