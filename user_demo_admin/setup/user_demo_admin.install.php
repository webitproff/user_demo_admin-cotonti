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