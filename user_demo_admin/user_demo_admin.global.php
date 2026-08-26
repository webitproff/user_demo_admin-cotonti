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
    cot_message('Режим демонстрации: изменения не сохраняются. Вы можете только просматривать интерфейс.', 'warning');
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer && cot_url_check($referer)) {
        cot_redirect($referer);
    } else {
        cot_redirect(cot_url('admin'));
    }
    exit;
}

$dangerousActions = [
    'update','save','add','edit','delete','remove','del',
    'install','uninstall','config','reset','import','export',
    'clone','move','sort','toggle','enable','disable',
    'rights','extrafields','updateconfig','update_rights',
    'val','unval','validate','invalidate',
    'read','unread','mark','status',
    'send','reply','forward','archive','restore',
    'publish','unpublish','approve','reject',
    'ban','unban','lock','unlock'
];

if ($a && in_array($a, $dangerousActions, true)) {
    cot_message('Режим демонстрации: управление этим разделом запрещено.', 'warning');
    cot_redirect(cot_url('admin'));
    exit;
}

if ($m === 'other' && $p === 'user_demo_admin') {
    cot_message('Режим демонстрации: управление этим разделом запрещено.', 'warning');
    cot_redirect(cot_url('admin'));
    exit;
}
