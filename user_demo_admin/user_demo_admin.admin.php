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
 * @package user_demo_admin
 * @version 5.0.1
 * @author webitproff
 * @copyright Copyright (c) 2026 | https://github.com/webitproff
 * @license BSD
 */

/**
 * Admin panel for User Demo Admin
 * Path: plugins/user_demo_admin/user_demo_admin.admin.php
 */

defined('COT_CODE') or die('Wrong URL');

require_once cot_langfile('user_demo_admin', 'plug');
require_once cot_langfile('users', 'module');
require_once cot_incfile('forms');
require_once cot_incfile('user_demo_admin', 'plug', 'functions');

// Only real admins
list(, , $isadmin) = cot_auth('admin', 'a');
if (Cot::$usr['maingrp'] == COT_GROUP_SUPERADMINS) {
    $isadmin = true;
}
cot_block($isadmin);

$tab = cot_import('tab', 'G', 'ALP') ?: 'list';
$a   = cot_import('a', 'G', 'ALP');

$t = new XTemplate(cot_tplfile('user_demo_admin.admin', 'plug', true));

// Guarantee group + rights
$groupId = cot_user_demo_admin_ensure_group();
if (!$groupId) {
    cot_error(Cot::$L['user_demo_admin_group_error']);
    cot_display_messages($t);
    $t->parse('MAIN');
    $pluginBody = $t->text('MAIN');
    return;
}

$t->assign([
    'PHP.tab'           => $tab,
    'TAB_LIST_ACTIVE'   => $tab === 'list'   ? 'active' : '',
    'TAB_CREATE_ACTIVE' => $tab === 'create' ? 'active' : '',
    'TAB_RIGHTS_ACTIVE' => $tab === 'rights' ? 'active' : '',
    'URL_LIST'          => cot_url('admin', ['m' => 'other', 'p' => 'user_demo_admin', 'tab' => 'list']),
    'URL_CREATE'        => cot_url('admin', ['m' => 'other', 'p' => 'user_demo_admin', 'tab' => 'create']),
    'URL_RIGHTS'        => cot_url('admin', ['m' => 'other', 'p' => 'user_demo_admin', 'tab' => 'rights']),
]);

/* ========== LIST ========== */
if ($tab === 'list') {
    $perPage = (int) (Cot::$cfg['plugin']['user_demo_admin']['perpage'] ?? 20);
    list($pg, $d, $durl) = cot_import_pagenav('d', $perPage);

    $total = (int) Cot::$db->query(
        'SELECT COUNT(*) FROM ' . Cot::$db->users . ' WHERE user_maingrp = ?',
        [$groupId]
    )->fetchColumn();

    $items = Cot::$db->query(
        'SELECT user_id, user_name, user_email, user_regdate
         FROM ' . Cot::$db->users . '
         WHERE user_maingrp = ?
         ORDER BY user_id DESC
         LIMIT ' . (int) $d . ', ' . (int) $perPage,
        [$groupId]
    )->fetchAll();

    $t->assign('LIST_TOTAL', $total);

    if ($items) {
        foreach ($items as $row) {
            $t->assign([
                'LIST_ROW_ID'      => $row['user_id'],
                'LIST_ROW_NAME'    => htmlspecialchars($row['user_name']),
                'LIST_ROW_EMAIL'   => htmlspecialchars($row['user_email']),
                'LIST_ROW_REGDATE' => cot_date('datetime_medium', $row['user_regdate']),
                'LIST_ROW_URL'     => cot_url('users', ['m' => 'details', 'id' => $row['user_id']]),
            ]);
            $t->parse('MAIN.LIST_ROW');
        }
    } else {
        $t->parse('MAIN.LIST_EMPTY');
    }

    $pagenav = cot_pagenav(
        'admin',
        ['m' => 'other', 'p' => 'user_demo_admin', 'tab' => 'list'],
        $d,
        $total,
        $perPage,
        'd'
    );
    $t->assign(cot_generatePaginationTags($pagenav));
}

/* ========== CREATE ========== */
elseif ($tab === 'create') {
    $username = $email = '';

    if ($a === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        cot_shield_protect();

        $username  = cot_import('username', 'P', 'TXT', 100, true);
        $email     = cot_import('email', 'P', 'TXT', 64, true);
        $password1 = (string) cot_import('password1', 'P', 'NOC', 32);
        $password2 = (string) cot_import('password2', 'P', 'NOC', 32);

        if (empty($username) || mb_strlen($username) < 2) {
            cot_error('aut_usernametooshort', 'username');
        }
        if (preg_match('/[<>#\'"\/]/', $username)) {
            cot_error('aut_invalidloginchars', 'username');
        }
        if (!cot_check_email($email)) {
            cot_error('aut_emailtooshort', 'email');
        }
        if (mb_strlen($password1) < 4) {
            cot_error('aut_passwordtooshort', 'password1');
        }
        if ($password1 !== $password2) {
            cot_error('aut_passwordmismatch', 'password2');
        }

        $exists = Cot::$db->query(
            'SELECT user_id FROM ' . Cot::$db->users . ' WHERE user_name = ? LIMIT 1',
            [$username]
        )->fetch();
        if ($exists) {
            cot_error('aut_usernamealreadyindb', 'username');
        }

        $emailExists = Cot::$db->query(
            'SELECT user_id FROM ' . Cot::$db->users . ' WHERE user_email = ? LIMIT 1',
            [$email]
        )->fetch();
        if ($emailExists && empty(Cot::$cfg['useremailduplicate'])) {
            cot_error('aut_emailalreadyindb', 'email');
        }

        if (!cot_error_found()) {
            $ruser = [
                'user_name'      => $username,
                'user_email'     => mb_strtolower($email),
                'user_password'  => $password1,
                'user_maingrp'   => $groupId,
                'user_hideemail' => 1,
                'user_country'   => '',
                'user_timezone'  => Cot::$cfg['defaulttimezone'] ?? 'UTC',
                'user_gender'    => 'U',
                'user_theme'     => Cot::$cfg['defaulttheme'] ?? '',
                'user_scheme'    => Cot::$cfg['defaultscheme'] ?? '',
                'user_lang'      => Cot::$cfg['defaultlang'] ?? '',
            ];

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

/* ========== RIGHTS ========== */
elseif ($tab === 'rights') {

    if ($a === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        cot_shield_protect();

        $permissions = cot_import('permission', 'P', 'ARR') ?: [];

        foreach ($permissions as $itemKey => $val) {
            $allowed = ((int) $val === 1);
            cot_user_demo_admin_set_permission($groupId, $itemKey, $allowed);
        }

        // Принудительно очищаем кеш прав у всех пользователей группы Demo Admin
        Cot::$db->query(
            'UPDATE ' . Cot::$db->users . ' SET user_auth = \'\' WHERE user_maingrp = ?',
            [$groupId]
        );

        // Также чистим общие кеши прав
        cot_auth_reorder();
        cot_auth_clear('all');

        cot_message(Cot::$L['user_demo_admin_rights_saved']);
        cot_redirect(cot_url('admin', [
            'm' => 'other',
            'p' => 'user_demo_admin',
            'tab' => 'rights'
        ], '', true));
    }

    $permissions = cot_user_demo_admin_get_permissions($groupId);

    $t->assign('RIGHTS_FORM_ACTION', cot_url('admin', [
        'm' => 'other',
        'p' => 'user_demo_admin',
        'tab' => 'rights',
        'a' => 'save'
    ]));

    global $cot_modules, $cot_plugins_enabled;

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

cot_display_messages($t);
$t->parse('MAIN');
$pluginBody = $t->text('MAIN');
