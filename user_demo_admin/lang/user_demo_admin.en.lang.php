<?php
/**
 * ============================================================
 * ДОКУМЕНТАЦИЯ ПО ФАЙЛУ user_demo_admin.ru.lang.php
 * ============================================================
 *
 * Языковой файл плагина user_demo_admin для русского языка.
 *
 * Path:     plugins/user_demo_admin/lang/user_demo_admin.ru.lang.php
 *
 * @package user_demo_admin
 * @version 5.0.0
 * @author webitproff
 * @copyright Copyright (c) 2026 | https://github.com/webitproff
 * @license BSD
 */



/**
 * Russian language file
 * Path: plugins/user_demo_admin/lang/user_demo_admin.ru.lang.php
 */

defined('COT_CODE') or die('Wrong URL.');

$L['info_name'] = 'User Demo Admin';
$L['info_desc'] = 'Создание пользователей с доступом в админку и правами только на чтение';

$L['user_demo_admin_title']               = 'User Demo Admin';
$L['user_demo_admin_tab_list']            = 'Демо-админы';
$L['user_demo_admin_tab_create']          = 'Создать пользователя';
$L['user_demo_admin_tab_rights']          = 'Права группы';

$L['user_demo_admin_col_name']            = 'Имя пользователя';
$L['user_demo_admin_col_password']        = 'Пароль';
$L['user_demo_admin_col_password_repeat'] = 'Повторите пароль';
$L['user_demo_admin_col_regdate']         = 'Дата регистрации';

$L['user_demo_admin_total']               = 'Всего демо-админов';
$L['user_demo_admin_no_users']            = 'Демо-администраторы не найдены';
$L['user_demo_admin_save']                = 'Создать пользователя';
$L['user_demo_admin_created']             = 'Пользователь успешно создан';
$L['user_demo_admin_create_failed']       = 'Не удалось создать пользователя';
$L['user_demo_admin_group_error']         = 'Не удалось создать или найти группу Demo Admin';

$L['user_demo_admin_module']              = 'Модуль / Раздел';
$L['user_demo_admin_allow']               = 'Разрешено (R)';
$L['user_demo_admin_deny']                = 'Запрещено';
$L['user_demo_admin_save_rights']         = 'Сохранить права';
$L['user_demo_admin_rights_saved']        = 'Права обновлены';
$L['user_demo_admin_rights_help']         = 'Здесь можно точечно разрешить или запретить чтение (R) по каждому разделу. Запись (W) и полный Admin (A) по умолчанию отключены.';

$L['cfg_group_alias']      = 'Алиас группы';
$L['cfg_group_alias_hint'] = 'Внутренний идентификатор группы (не меняйте без необходимости)';
$L['cfg_perpage']          = 'Пользователей на странице';
$L['cfg_perpage_hint']     = 'Количество записей в списке';
