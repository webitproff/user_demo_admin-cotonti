<?php
/**
 * ============================================================
 * DOCUMENTATION FOR user_demo_admin.en.lang.php
 * ============================================================
 *
 * Language file for the user_demo_admin plugin for English.
 *
 * Path:     plugins/user_demo_admin/lang/user_demo_admin.en.lang.php
 *
 * @package user_demo_admin
 * @version 5.1.0
 * @author webitproff
 * @copyright Copyright (c) 2026 | https://github.com/webitproff
 * @license BSD
 */



/**
 * English language file
 * Path: plugins/user_demo_admin/lang/user_demo_admin.en.lang.php
 */

defined('COT_CODE') or die('Wrong URL.');

$L['info_name'] = 'User Demo Admin';
$L['info_desc'] = 'Creating users with admin access and read-only permissions';

$L['user_demo_admin_title']               = 'User Demo Admin';
$L['user_demo_admin_tab_list']            = 'Demo Admins';
$L['user_demo_admin_tab_create']          = 'Create User';
$L['user_demo_admin_tab_rights']          = 'Group Rights';

$L['user_demo_admin_col_name']            = 'Username';
$L['user_demo_admin_col_password']        = 'Password';
$L['user_demo_admin_col_password_repeat'] = 'Repeat Password';
$L['user_demo_admin_col_regdate']         = 'Registration Date';

$L['user_demo_admin_total']               = 'Total demo admins';
$L['user_demo_admin_no_users']            = 'No demo administrators found';
$L['user_demo_admin_save']                = 'Create User';
$L['user_demo_admin_created']             = 'User successfully created';
$L['user_demo_admin_create_failed']       = 'Failed to create user';
$L['user_demo_admin_group_error']         = 'Failed to create or find Demo Admin group';

$L['user_demo_admin_module']              = 'Module / Section';
$L['user_demo_admin_allow']               = 'Allowed (R)';
$L['user_demo_admin_deny']                = 'Denied';
$L['user_demo_admin_save_rights']         = 'Save Rights';
$L['user_demo_admin_rights_saved']        = 'Rights updated';
$L['user_demo_admin_rights_help']         = 'Here you can selectively allow or deny read (R) access for each section. Write (W) and full Admin (A) are disabled by default.';

$L['cfg_group_alias']      = 'Group Alias';
$L['cfg_group_alias_hint'] = 'Internal group identifier (do not change unless necessary)';
$L['cfg_perpage']          = 'Users per page';
$L['cfg_perpage_hint']     = 'Number of records in the list';

$L['user_demo_admin_demo_mode_warning'] = 'Demo mode: changes are not saved. You can only view the interface.';
$L['user_demo_admin_demo_mode_forbidden'] = 'Demo mode: management of this section is forbidden.';
