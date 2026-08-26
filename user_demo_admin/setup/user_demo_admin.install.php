<?php
/**
 * ============================================================
 * ДОКУМЕНТАЦИЯ ПО ФАЙЛУ user_demo_admin.install.php
 * ============================================================
 *
 * Файл выполняется при установке плагина user_demo_admin.
 *
 * Path:     plugins/user_demo_admin/setup/user_demo_admin.install.php
 *
 * @package user_demo_admin
 * @version 5.0.0
 * @author webitproff
 * @copyright Copyright (c) 2026 | https://github.com/webitproff
 * @license BSD
 */

/**
 * Installation script
 * Path: plugins/user_demo_admin/setup/user_demo_admin.install.php
 */

defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('user_demo_admin', 'plug', 'functions');

$groupId = cot_user_demo_admin_ensure_group();

if (!$groupId) {
    cot_error('Failed to create Demo Admin group');
}