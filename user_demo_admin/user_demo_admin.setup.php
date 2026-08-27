<?php
/* ====================
[BEGIN_COT_EXT]
Code=user_demo_admin
Name=User Demo Admin
Category=administration
Description=Creates users with demo-admin rights: access to admin panel + read-only by default
Version=5.1.1
Date=2026-08-27
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
 * @package user_demo_admin
 * @version 5.1.1
 * @author webitproff
 * @copyright Copyright (c) 2026 | https://github.com/webitproff
 * @license BSD
 */
