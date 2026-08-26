<?php
/**
 * ============================================================
 * ДОКУМЕНТАЦИЯ ПО ФАЙЛУ user_demo_admin.uninstall.php
 * ============================================================
 *
 * Файл выполняется при удалении плагина user_demo_admin.
 * Удаляет группу «Демо-администратор» и связанные записи.
 * Пользователи группы переводятся в группу «Members» (id=4).
 *
 * Path:     plugins/user_demo_admin/setup/user_demo_admin.uninstall.php
 *
 * @package user_demo_admin
 * @version 5.0.0
 * @author webitproff
 * @copyright Copyright (c) 2026 | https://github.com/webitproff
 * @license BSD
 */

/**
 * Uninstall script
 * Path: plugins/user_demo_admin/setup/user_demo_admin.uninstall.php
 */

defined('COT_CODE') or die('Wrong URL');

$alias = Cot::$cfg['plugin']['user_demo_admin']['group_alias'] ?? 'demo_admin';

$group = Cot::$db->query(
    'SELECT grp_id FROM ' . Cot::$db->groups . ' WHERE grp_alias = ? LIMIT 1',
    [$alias]
)->fetch();

if ($group) {
    $groupId = (int) $group['grp_id'];

    // Переводим пользователей в Members
    Cot::$db->update(
        Cot::$db->users,
        ['user_maingrp' => COT_GROUP_MEMBERS],
        'user_maingrp = ?',
        [$groupId]
    );

    // Чистим связи и права
    Cot::$db->delete(Cot::$db->groups_users, 'gru_groupid = ?', [$groupId]);
    Cot::$db->delete(Cot::$db->auth, 'auth_groupid = ?', [$groupId]);
    Cot::$db->delete(Cot::$db->groups, 'grp_id = ?', [$groupId]);

    cot_auth_clear('all');
}