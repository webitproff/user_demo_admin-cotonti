<?php
/* ====================
[BEGIN_COT_EXT]
Code=user_demo_admin
Name=User Demo Admin
Category=administration
Description=Creates users with demo-admin rights: access to admin panel + read-only by default
Version=5.3.1
Date=2026-08-29
Author=webitproff
Copyright=(c) webitproff 2026 https://github.com/webitproff
Notes=Requires users module
Auth_guests=R
Lock_guests=W12345A
Auth_members=RW
Lock_members=12345
Requires_modules=users
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
group_alias=01:string::demo_admin:Internal alias for the Demo Admin group
perpage=02:string::20:Users per page in the list
[END_COT_EXT_CONFIG]
==================== */

defined('COT_CODE') or die('Wrong URL');
/**
 * ============================================================
 * ДОКУМЕНТАЦИЯ ПО ФАЙЛУ user_demo_admin.setup.php
 * ============================================================
 *
 * Файл регистрации плагина user_demo_admin в системе Cotonti.
 * Содержит метаданные расширения и описание настроек.
 *
 * Path:     plugins/user_demo_admin/user_demo_admin.setup.php
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
