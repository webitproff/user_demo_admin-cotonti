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
 * @version 5.0.1
 * @author webitproff
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

// Только админка + авторизованный пользователь
if (!defined('COT_ADMIN') || empty(Cot::$usr['id'])) {
    return;
}

require_once cot_incfile('user_demo_admin', 'plug', 'functions');

// Если это не демо-пользователь — выходим
if (!cot_user_demo_admin_is_demo_user()) {
    return;
}

// === Демо-пользователь в админке ===

Cot::$usr['auth_write'] = false;

$m   = cot_import('m', 'G', 'ALP');
$a   = cot_import('a', 'G', 'ALP');
$n   = cot_import('n', 'G', 'ALP');
$o   = cot_import('o', 'G', 'ALP');
$p   = cot_import('p', 'G', 'ALP');   // для config = код расширения
$mod = cot_import('mod', 'G', 'ALP');
$pl  = cot_import('pl', 'G', 'ALP');
$tab = cot_import('tab', 'G', 'ALP');

/**
 * ---------------------------------------------------------
 * 1. Блокировка config и details запрещённых расширений
 * ---------------------------------------------------------
 */

// /admin/config?n=edit&o=module&p=CODE
// /admin/config?n=edit&o=plug&p=CODE
if ($m === 'config' && $n === 'edit' && !empty($p) && in_array($o, ['module', 'mod', 'plug'], true)) {
    $type = ($o === 'plug') ? 'plug' : 'module';

    if (!cot_user_demo_admin_is_item_allowed($type, $p)) {
        cot_message('Режим демонстрации: доступ к конфигурации этого раздела запрещён.', 'warning');
        cot_redirect(cot_url('admin', ['m' => 'extensions'], '', true));
        exit;
    }
}

// /admin/extensions?a=details&mod=CODE
// /admin/extensions?a=details&pl=CODE
if ($m === 'extensions' && $a === 'details') {
    if (!empty($mod) && !cot_user_demo_admin_is_item_allowed('module', $mod)) {
        cot_message('Режим демонстрации: доступ к этому модулю запрещён.', 'warning');
        cot_redirect(cot_url('admin', ['m' => 'extensions'], '', true));
        exit;
    }

    if (!empty($pl) && !cot_user_demo_admin_is_item_allowed('plug', $pl)) {
        cot_message('Режим демонстрации: доступ к этому плагину запрещён.', 'warning');
        cot_redirect(cot_url('admin', ['m' => 'extensions'], '', true));
        exit;
    }
}

/**
 * ---------------------------------------------------------
 * 2. Общая блокировка любых операций записи
 * ---------------------------------------------------------
 */

$isWriteAttempt = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isWriteAttempt = true;
}

$dangerousActions = [
    'update', 'save', 'add', 'edit', 'delete', 'remove',
    'install', 'uninstall', 'config', 'reset', 'import', 'export',
    'clone', 'move', 'sort', 'toggle', 'enable', 'disable',
    'rights', 'extrafields', 'updateconfig', 'update_rights'
];

if ($a && in_array($a, $dangerousActions, true)) {
    $isWriteAttempt = true;
}

if ($m === 'config' && ($_SERVER['REQUEST_METHOD'] === 'POST' || $a === 'update')) {
    $isWriteAttempt = true;
}

if ($m === 'rights' || $m === 'rightsbyitem') {
    $isWriteAttempt = true;
}

// Своё сохранение прав плагина настоящим админом не трогаем
// (сюда демо-пользователь и так не попадёт из-за is_demo_user)
$isOurRightsSave = ($p === 'user_demo_admin' && $tab === 'rights' && $a === 'save');

if ($isWriteAttempt && !$isOurRightsSave) {
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
