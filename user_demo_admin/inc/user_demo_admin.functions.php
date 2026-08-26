<?php
/**
 * ============================================================
 * ДОКУМЕНТАЦИЯ ПО ФАЙЛУ user_demo_admin.functions.php
 * ============================================================
 *
 * Файл содержит функции для управления группой демо-администратора
 * и её правами в системе Cotonti.
 *
 * Path:     plugins/user_demo_admin/inc/user_demo_admin.functions.php
 *
 * @package user_demo_admin
 * @version 5.0.0
 * @author webitproff
 * @copyright Copyright (c) 2026 | https://github.com/webitproff
 * @license BSD
 */

/**
 * Helper functions for User Demo Admin
 * Path: plugins/user_demo_admin/inc/user_demo_admin.functions.php
 */

defined('COT_CODE') or die('Wrong URL');

/**
 * Returns group ID by configured alias
 */
function cot_user_demo_admin_get_group(): ?int
{
    $alias = Cot::$cfg['plugin']['user_demo_admin']['group_alias'] ?? 'demo_admin';
    $row = Cot::$db->query(
        'SELECT grp_id FROM ' . Cot::$db->groups . ' WHERE grp_alias = ? LIMIT 1',
        [$alias]
    )->fetch();

    return $row ? (int) $row['grp_id'] : null;
}

/**
 * Checks whether current user belongs to Demo Admin group
 */
function cot_user_demo_admin_is_demo_user(): bool
{
    static $result = null;
    if ($result !== null) {
        return $result;
    }

    if (empty(Cot::$usr['id'])) {
        return $result = false;
    }

    $groupId = cot_user_demo_admin_get_group();
    if (!$groupId) {
        return $result = false;
    }

    if ((int) Cot::$usr['maingrp'] === $groupId) {
        return $result = true;
    }

    if (!empty(Cot::$usr['groups']) && is_array(Cot::$usr['groups'])) {
        return $result = in_array($groupId, Cot::$usr['groups'], true);
    }

    $exists = Cot::$db->query(
        'SELECT 1 FROM ' . Cot::$db->groups_users .
        ' WHERE gru_userid = ? AND gru_groupid = ? LIMIT 1',
        [Cot::$usr['id'], $groupId]
    )->fetchColumn();

    return $result = (bool) $exists;
}

/**
 * Creates the Demo Admin group (if missing) and guarantees correct rights
 */
function cot_user_demo_admin_ensure_group(): ?int
{
    $groupId = cot_user_demo_admin_get_group();

    if (!$groupId) {
        $alias = Cot::$cfg['plugin']['user_demo_admin']['group_alias'] ?? 'demo_admin';

        $ok = Cot::$db->insert(Cot::$db->groups, [
            'grp_name'         => 'Demo Admin',
            'grp_title'        => 'Demo Admin',
            'grp_level'        => 50,
            'grp_disabled'     => 0,
            'grp_skiprights'   => 0,
            'grp_alias'        => $alias,
            'grp_desc'         => 'Read-only demo administrators (view only)',
            'grp_icon'         => '',
            'grp_ownerid'      => (int) (Cot::$usr['id'] ?? 0),
            'grp_maintenance'  => 0,
            'grp_pfs_maxfile'  => 0,
            'grp_pfs_maxtotal' => 0,
        ]);

        if (!$ok) {
            return null;
        }
        $groupId = (int) Cot::$db->lastInsertId();
    }

    // admin = R + A (чтобы пускало в админку)
    // users = R
    // всё остальное + все категории структуры = только R
    cot_user_demo_admin_set_right($groupId, 'admin', 'a', 129, 254);
    cot_user_demo_admin_set_right($groupId, 'users', 'a', 1, 254);

    cot_user_demo_admin_ensure_all_read($groupId);

    cot_auth_reorder();
    cot_auth_clear('all');

    return $groupId;
}

/**
 * Sets / updates a single auth row
 */
function cot_user_demo_admin_set_right(
    int $groupId,
    string $code,
    string $option,
    int $rights,
    int $lock = 254
): void {
    $exists = Cot::$db->query(
        'SELECT auth_id, auth_rights, auth_rights_lock FROM ' . Cot::$db->auth .
        ' WHERE auth_groupid = ? AND auth_code = ? AND auth_option = ?',
        [$groupId, $code, $option]
    )->fetch();

    $data = [
        'auth_rights'      => $rights,
        'auth_rights_lock' => $lock,
        'auth_setbyuserid' => (int) (Cot::$usr['id'] ?? 0),
    ];

    if (!$exists) {
        $data['auth_groupid'] = $groupId;
        $data['auth_code']    = $code;
        $data['auth_option']  = $option;
        Cot::$db->insert(Cot::$db->auth, $data);
    } else {
        if ((int)$exists['auth_rights'] !== $rights || (int)$exists['auth_rights_lock'] !== $lock) {
            Cot::$db->update(
                Cot::$db->auth,
                $data,
                'auth_id = ' . (int) $exists['auth_id']
            );
        }
    }
}

/**
 * Главная функция выдачи прав «только чтение»
 * - R на корень модуля
 * - R на ВСЕ категории структуры (это как раз то, чего не хватало)
 * - R на плагины
 */
function cot_user_demo_admin_ensure_all_read(int $groupId): void
{
    // Специальные коды ядра
    foreach (['message', 'structure'] as $code) {
        cot_user_demo_admin_set_right($groupId, $code, 'a', 1, 254);
    }

    // Модули
    global $cot_modules;
    if (!empty($cot_modules) && is_array($cot_modules)) {
        foreach (array_keys($cot_modules) as $code) {
            if (in_array($code, ['admin', 'users'], true)) {
                continue;
            }

            // Корень модуля
            cot_user_demo_admin_set_right($groupId, $code, 'a', 1, 254);

            // Все категории структуры этого модуля (page, forums и т.д.)
            if (!empty(Cot::$structure[$code]) && is_array(Cot::$structure[$code])) {
                foreach (array_keys(Cot::$structure[$code]) as $cat) {
                    if ($cat === '' || $cat === 'all') {
                        continue;
                    }
                    cot_user_demo_admin_set_right($groupId, $code, $cat, 1, 254);
                }
            }
        }
    }

    // Активные плагины
    $plugins = Cot::$db->query(
        'SELECT DISTINCT pl_code FROM ' . Cot::$db->plugins . ' WHERE pl_active = 1'
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($plugins as $code) {
        cot_user_demo_admin_set_right($groupId, 'plug', $code, 1, 254);
    }
}

/**
 * Returns flat list of permissions for the rights UI (только корень + плагины)
 */
function cot_user_demo_admin_get_permissions(int $groupId): array
{
    global $cot_modules;

    $map = [];
    $sql = Cot::$db->query(
        'SELECT auth_code, auth_option, auth_rights FROM ' . Cot::$db->auth .
        ' WHERE auth_groupid = ? AND auth_option = \'a\' OR auth_code = \'plug\'',
        [$groupId]
    );

    while ($row = $sql->fetch()) {
        if ($row['auth_code'] === 'plug') {
            $key = 'plug:' . $row['auth_option'];
        } elseif (in_array($row['auth_code'], ['message', 'structure'], true)) {
            $key = 'core:' . $row['auth_code'];
        } else {
            $key = 'module:' . $row['auth_code'];
        }
        $map[$key] = ((int) $row['auth_rights'] & 1) === 1;
    }

    $result = [];

    foreach (['message', 'structure'] as $code) {
        $result['core:' . $code] = $map['core:' . $code] ?? true;
    }

    if (!empty($cot_modules)) {
        foreach (array_keys($cot_modules) as $code) {
            if (in_array($code, ['admin', 'users'], true)) {
                continue;
            }
            $result['module:' . $code] = $map['module:' . $code] ?? true;
        }
    }

    $plugins = Cot::$db->query(
        'SELECT DISTINCT pl_code FROM ' . Cot::$db->plugins . ' WHERE pl_active = 1'
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($plugins as $code) {
        $result['plug:' . $code] = $map['plug:' . $code] ?? true;
    }

    ksort($result);
    return $result;
}

/**
 * Sets allow/deny Read for one item from the rights UI
 */
function cot_user_demo_admin_set_permission(int $groupId, string $itemKey, bool $allowed): void
{
    $rights = $allowed ? 1 : 0;

    if (str_starts_with($itemKey, 'plug:')) {
        $code   = 'plug';
        $option = substr($itemKey, 5);
    } elseif (str_starts_with($itemKey, 'module:')) {
        $code   = substr($itemKey, 7);
        $option = 'a';
    } elseif (str_starts_with($itemKey, 'core:')) {
        $code   = substr($itemKey, 5);
        $option = 'a';
    } else {
        return;
    }

    cot_user_demo_admin_set_right($groupId, $code, $option, $rights, 254);

    // Если это модуль со структурой — синхронно ставим R/запрет на все его категории
    if ($option === 'a' && !empty(Cot::$structure[$code])) {
        foreach (array_keys(Cot::$structure[$code]) as $cat) {
            if ($cat === '' || $cat === 'all') {
                continue;
            }
            cot_user_demo_admin_set_right($groupId, $code, $cat, $rights, 254);
        }
    }
}