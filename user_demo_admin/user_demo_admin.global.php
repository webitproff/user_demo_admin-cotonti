<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=global
[END_COT_EXT]
==================== */

/**
 * Filename: user_demo_admin.global.php
 * user_demo_admin Plugin: Global initialization
 * Подключает языковые файлы и функции плагина, при их явном подключении. 
 * в данном конкретном случае без него слетает кнопка администрирования в карточке расширения после жима "обновить"
 *
 *
 * Path:     plugins/user_demo_admin/user_demo_admin.global.php
 *
 * Extrafields Users Custom i18n plugin for Cotonti v1.+, PHP 8.5+, MySQL 8.4
 *
 * Source and updates   https://github.com/webitproff/user_demo_admin-cotonti
 * ReadMeMore:          https://abuyfile.com/ru/market/cotonti/plugs/user-demo-admin
 * Support:             https://abuyfile.com/ru/forums/cotonti/original/extrafields
 *
 * Date: Aug 25, 2026
 *
 * @package user_demo_admin
 * @version 5.0.0
 * @author webitproff
 * @copyright Copyright (c) webitproff 2026 | https://github.com/webitproff
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL');
// Global stub – plugin is loaded via tools hook


/**
 * Global protection for Demo Admin users
 * Блокирует любые попытки сохранения/редактирования в админке
 */



/**
 * Жёсткая защита режима демонстрации
 * Блокирует ВСЕ операции записи в админке для пользователей группы Demo Admin
 */


/**
 * Защита режима демонстрации — блокирует запись ТОЛЬКО в админке.
 * На фронтенд не влияет.
 */

defined('COT_CODE') or die('Wrong URL');

// Только админка + авторизованный пользователь
if (!defined('COT_ADMIN') || empty(Cot::$usr['id'])) {
    return;
}

require_once cot_incfile('user_demo_admin', 'plug', 'functions');

if (!cot_user_demo_admin_is_demo_user()) {
    return;
}

// === Демо-пользователь в админке ===

// Отключаем запись
Cot::$usr['auth_write'] = false;

// Определяем попытку записи
$isWriteAttempt = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isWriteAttempt = true;
}

$a = cot_import('a', 'G', 'ALP');
$m = cot_import('m', 'G', 'ALP');

$dangerousActions = [
    'update', 'save', 'add', 'edit', 'delete', 'remove',
    'install', 'uninstall', 'config', 'reset', 'import', 'export',
    'clone', 'move', 'sort', 'toggle', 'enable', 'disable',
    'rights', 'extrafields', 'updateconfig', 'update_rights'
];

if ($a && in_array($a, $dangerousActions, true)) {
    $isWriteAttempt = true;
}

// Конфигурация
if ($m === 'config' && ($_SERVER['REQUEST_METHOD'] === 'POST' || $a === 'update')) {
    $isWriteAttempt = true;
}

// Редактор прав
if ($m === 'rights' || $m === 'rightsbyitem') {
    $isWriteAttempt = true;
}

if ($isWriteAttempt) {
    // Очищаем данные
    $_POST = [];
    $_GET['a'] = '';
    $_REQUEST = $_GET;

    cot_message(
        'Режим демонстрации: изменения не сохраняются. Вы можете только просматривать интерфейс.',
        'warning'
    );

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer && cot_url_check($referer)) {
        cot_redirect($referer);
    } else {
        cot_redirect(cot_url('admin'));
    }
    exit;
}