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
 * Проверка: текущий пользователь в группе Demo Admin.
 * Группа ищется ТОЛЬКО по алиасу из настроек плагина.
 * Никаких фиксированных grp_id.
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

    // grp_id получаем только через алиас
    $groupId = cot_user_demo_admin_get_group();
    if (!$groupId) {
        return $result = false;
    }

    // Основная группа
    if ((int) Cot::$usr['maingrp'] === (int) $groupId) {
        return $result = true;
    }

    // Дополнительные группы (без strict: в массиве могут быть строки)
    if (!empty(Cot::$usr['groups']) && is_array(Cot::$usr['groups'])) {
        foreach (Cot::$usr['groups'] as $gid) {
            if ((int) $gid === (int) $groupId) {
                return $result = true;
            }
        }
    }

    // Проверка в БД
    $exists = Cot::$db->query(
        'SELECT 1 FROM ' . Cot::$db->groups_users .
        ' WHERE gru_userid = ? AND gru_groupid = ? LIMIT 1',
        [(int) Cot::$usr['id'], (int) $groupId]
    )->fetchColumn();

    return $result = (bool) $exists;
}

/**
 * Creates the Demo Admin group (if missing) and guarantees correct rights
 */
 
/**
 * Creates the Demo Admin group (if missing) and guarantees correct rights
 */
function cot_user_demo_admin_ensure_group(): ?int
{
    $groupId = cot_user_demo_admin_get_group();
    $isNewGroup = false;

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
        $isNewGroup = true;
    }

    // Критически важные права всегда поддерживаем
    // admin = R + A (чтобы пускало в админку)
    // users = R
    cot_user_demo_admin_set_right($groupId, 'admin', 'a', 129, 254);
    cot_user_demo_admin_set_right($groupId, 'users', 'a', 1, 254);

    // Полный сброс всех прав в «только чтение» делаем
    // ТОЛЬКО при первом создании группы.
    // Иначе при каждом открытии вкладки «Права» всё будет сбрасываться.
    if ($isNewGroup) {
        cot_user_demo_admin_ensure_all_read($groupId);
    }

    cot_auth_reorder();
    cot_auth_clear('all');

    return $groupId;
}

/**
 * Проверяет, разрешён ли модуль/плагин для группы Demo Admin
 * (есть ли бит R)
 *
 * @param string $type  module|plug
 * @param string $code  код модуля или плагина
 * @return bool
 */
function cot_user_demo_admin_is_item_allowed(string $type, string $code): bool
{
    $groupId = cot_user_demo_admin_get_group();
    if (!$groupId || $code === '') {
        return false;
    }

    if ($type === 'plug') {
        $authCode = 'plug';
        $option   = $code;
    } else {
        // module / core
        $authCode = $code;
        $option   = 'a';
    }

    $row = Cot::$db->query(
        'SELECT auth_rights FROM ' . Cot::$db->auth .
        ' WHERE auth_groupid = ? AND auth_code = ? AND auth_option = ? LIMIT 1',
        [$groupId, $authCode, $option]
    )->fetch();

    // Нет записи = нет прав
    if (!$row) {
        return false;
    }

    // Достаточно бита R (1). Для плагинов с 129 он тоже есть.
    return (((int) $row['auth_rights'] & 1) === 1);
}

/**
 * Sets / updates a single auth row
 *
 * @param int $groupId  ID группы
 * @param string $code  auth_code
 * @param string $option auth_option
 * @param int $rights   битовая маска прав (1 = R, 129 = R+A и т.д.)
 * @param int $lock     auth_rights_lock (что нельзя менять через редактор прав)
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
        // Обновляем только если значения изменились
        if ((int) $exists['auth_rights'] !== $rights || (int) $exists['auth_rights_lock'] !== $lock) {
            Cot::$db->update(
                Cot::$db->auth,
                $data,
                'auth_id = ' . (int) $exists['auth_id']
            );
        }
    }
}
/**
 * Главная функция выдачи прав «только чтение» при создании группы
 * - R на корень модулей
 * - R на ВСЕ категории структуры (фронтенд)
 * - R+A на плагины (чтобы открывался admin/other?p=...)
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

            // Корень модуля — только чтение
            cot_user_demo_admin_set_right($groupId, $code, 'a', 1, 254);

            // Все категории структуры этого модуля
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

    // Активные плагины — R + A (129),
    // чтобы демо-админ мог открывать admin/other?p=plugin
    $plugins = Cot::$db->query(
        'SELECT DISTINCT pl_code FROM ' . Cot::$db->plugins . ' WHERE pl_active = 1'
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($plugins as $code) {
        cot_user_demo_admin_set_right($groupId, 'plug', $code, 129, 254);
    }
}
/**
 * Returns flat list of permissions for the rights UI (только корень + плагины)
 *
 * ВАЖНО: в SQL обязательно используются скобки,
 * иначе из-за приоритета AND/OR возвращаются права чужих групп.
 */
function cot_user_demo_admin_get_permissions(int $groupId): array
{
    global $cot_modules;

    $map = [];

    // Исправленный запрос — скобки обязательны!
    $sql = Cot::$db->query(
        'SELECT auth_code, auth_option, auth_rights FROM ' . Cot::$db->auth .
        ' WHERE auth_groupid = ? AND (auth_option = \'a\' OR auth_code = \'plug\')',
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

    // Core
    foreach (['message', 'structure'] as $code) {
        $result['core:' . $code] = $map['core:' . $code] ?? true;
    }

    // Modules
    if (!empty($cot_modules)) {
        foreach (array_keys($cot_modules) as $code) {
            if (in_array($code, ['admin', 'users'], true)) {
                continue;
            }
            $result['module:' . $code] = $map['module:' . $code] ?? true;
        }
    }

    // Plugins
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
 * Sets allow/deny for one item from the rights UI
 *
 * Смысл:
 * - module/core:
 *     Разрешено  = R (1)   — можно смотреть
 *     Запрещено  = 0       — нельзя config/details
 * - plug:
 *     Разрешено  = R+A (129) — можно открыть admin/other?p=...
 *     Запрещено  = 0         — нельзя details/tools
 *
 * Категории структуры всегда оставляем R=1 (фронтенд не ломаем)
 */
function cot_user_demo_admin_set_permission(int $groupId, string $itemKey, bool $allowed): void
{
    if (str_starts_with($itemKey, 'plug:')) {
        $code   = 'plug';
        $option = substr($itemKey, 5);
        // Для плагинов нужен A, иначе tools-страницы дают 930
        $rights = $allowed ? 129 : 0;
    } elseif (str_starts_with($itemKey, 'module:')) {
        $code   = substr($itemKey, 7);
        $option = 'a';
        $rights = $allowed ? 1 : 0;
    } elseif (str_starts_with($itemKey, 'core:')) {
        $code   = substr($itemKey, 5);
        $option = 'a';
        $rights = $allowed ? 1 : 0;
    } else {
        return;
    }

    cot_user_demo_admin_set_right($groupId, $code, $option, $rights, 254);

    // Категории структуры — всегда только чтение
    if ($option === 'a' && !empty(Cot::$structure[$code]) && is_array(Cot::$structure[$code])) {
        foreach (array_keys(Cot::$structure[$code]) as $cat) {
            if ($cat === '' || $cat === 'all') {
                continue;
            }
            cot_user_demo_admin_set_right($groupId, $code, $cat, 1, 254);
        }
    }
}
