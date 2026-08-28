<?php
/**
 * ============================================================
 * Helper functions for User Demo Admin
 * ============================================================
 *
 * Содержит функции для управления группой демо-администратора,
 * её правами и проверками безопасности.
 *
 * Path:     plugins/user_demo_admin/inc/user_demo_admin.functions.php
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

defined('COT_CODE') or die('Wrong URL');
require_once cot_langfile('user_demo_admin', 'plug');

/**
 * =====================================================================
 * Возвращает список опасных действий (значения GET-параметров).
 *
 * Эти значения используются для блокировки потенциально опасных
 * операций, передаваемых демо-администратором через URL (например,
 * ?a=delete). Если значение любого из параметров, перечисленных в
 * cot_user_demo_admin_get_action_params(), совпадает с одним из этих
 * действий, запрос будет заблокирован.
 *
 * Список не является исчерпывающим и может дополняться при необходимости.
 *
 * @return string[] Массив опасных значений параметров запроса.
 * =====================================================================
 */
function cot_user_demo_admin_get_dangerous_actions(): array
{
    return [
        'add',             // добавление нового элемента
        'approve',         // одобрение
        'archive',         // архивирование
        'ban',             // бан
        'clone',           // клонирование
        'config',          // изменение конфигурации
        'create',          // создание
        'del',             // сокращение от delete
        'delete',          // удаление
        'disable',         // отключение
        'edit',            // редактирование
        'enable',          // включение
        'export',          // экспорт данных
        'extrafields',     // управление доп. полями
        'forward',         // пересылка
        'import',          // импорт данных
        'install',         // установка расширений
        'invalidate',      // снятие проверки
        'lock',            // блокировка
        'mark',            // пометка
        'move',            // перемещение
        'publish',         // публикация
        'purge',           // полная очистка / удаление всех данных
        'read',            // отметка прочитанным (в некоторых модулях может быть действием)
        'reject',          // отклонение
        'remove',          // удаление (альтернативное имя)
        'reply',           // ответ (создание записи)
        'reset',           // сброс настроек
        'restore',         // восстановление
        'rights',          // управление правами
        'save',            // сохранение изменений
        'send',            // отправка
        'sort',            // сортировка (может менять порядок)
        'status',          // смена статуса
        'toggle',          // переключение состояния
        'unban',           // снятие бана
        'uninstall',       // удаление расширений
        'unlock',          // разблокировка
        'unpublish',       // снятие с публикации
        'unread',          // отметка непрочитанным
        'unval',           // отмена валидации
        'update',          // обновление записи
        'update_rights',   // обновление прав
        'updateconfig',    // обновление конфигурации
        'val',             // валидация (подтверждение)
        'validate',        // проверка
    ];
}

/**
 * =====================================================================
 * Возвращает список имён параметров запроса, в которых могут
 * передаваться опасные действия.
 *
 * Используется совместно с cot_user_demo_admin_get_dangerous_actions()
 * для проверки GET-запросов демо-администратора. Если значение
 * любого из этих параметров совпадает с опасным действием,
 * запрос будет заблокирован.
 *
 * Список может дополняться по мере необходимости.
 *
 * @return string[] Массив имён параметров.
 * =====================================================================
 */
function cot_user_demo_admin_get_action_params(): array
{
    return [
        'a',            // стандартный в Cotonti
        'act',          // сокращение от action
        'action',       // часто в формах
        'action_name',  // имя действия
        'admin_action', // админ-действие
        'cmd',          // команда
        'do',           // действие
        'event',        // событие
        'func',         // функция
        'getaction',    // GET-действие
        'handler',      // обработчик
        'method',       // метод
        'mode',         // режим
        'op',           // операция
        'operation',    // операция
        'part',         // часть
        'postaction',   // POST-действие
        'process',      // процесс
        'section',      // секция
        'step',         // шаг
        'task',         // задача
        'tool',         // инструмент
        'type',         // тип действия
        'view',         // вид
    ];
}

/**
 * =====================================================================
 * Возвращает ID группы демо-администраторов по алиасу из настроек.
 *
 * Группа создаётся при установке плагина и хранится в таблице
 * cot_groups. Идентификатор не фиксирован, поэтому всегда получаем
 * его динамически через grp_alias. Это позволяет избежать конфликтов
 * с другими группами и упрощает переносимость.
 *
 * @return int|null ID группы или null, если группа не найдена.
 * =====================================================================
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
 * =====================================================================
 * Проверяет, принадлежит ли текущий пользователь к группе Demo Admin.
 *
 * Результат кэшируется в статической переменной на время одного
 * HTTP-запроса для избежания повторных запросов к БД.
 * Группа определяется по алиасу (см. cot_user_demo_admin_get_group()).
 * Проверяется основная группа, дополнительные группы и прямое
 * членство в таблице cot_groups_users.
 *
 * @return bool True, если пользователь — демо-администратор, иначе false.
 * =====================================================================
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

    // Дополнительные группы
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
 * =====================================================================
 * Создаёт группу демо-администраторов (если её ещё нет) и гарантирует
 * базовые права доступа.
 *
 * При первом создании:
 *   - выдаются права на админ-область (admin = R + A = 129) и на users (R = 1);
 *   - для всех остальных модулей/категорий/плагинов выдаются права «только чтение».
 *
 * Блокировка прав (auth_rights_lock) везде устанавливается в 254,
 * что соответствует маске 'W12345A' (заблокированы все права, кроме R).
 * Это предотвращает расширение прав демо-админа через интерфейс.
 *
 * @return int|null ID группы демо-администраторов или null при ошибке создания.
 * =====================================================================
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
            'grp_skiprights'   => 1, // Группа не получает права автоматически
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
        if ($groupId <= 0) {
            return null;
        }
        $isNewGroup = true;
    }

    // Критически важные права всегда поддерживаем:
    // admin = R + A (чтобы пускало в админку)
    // users = R
    cot_user_demo_admin_set_right($groupId, 'admin', 'a', 129, 254); // 254 = 'W12345A'
    cot_user_demo_admin_set_right($groupId, 'users', 'a', 1, 254);   // 254 = 'W12345A'

    // Полный сброс всех прав в «только чтение» делаем только при создании группы
    if ($isNewGroup) {
        cot_user_demo_admin_ensure_all_read($groupId);
    }

    return $groupId;
}

/**
 * =====================================================================
 * Проверяет, разрешён ли модуль/плагин для группы Demo Admin
 * (наличие бита R в соответствующих правах).
 *
 * @param string $type Тип элемента: 'module' или 'plug'.
 * @param string $code Код модуля или плагина.
 * @return bool True, если чтение разрешено, иначе false.
 * =====================================================================
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
 * =====================================================================
 * Устанавливает или обновляет одну запись в таблице cot_auth для демо-группы.
 *
 * @param int    $groupId ID группы демо-администраторов.
 * @param string $code    Код области (auth_code), например 'admin', 'users', 'plug', 'page'.
 * @param string $option  Объект доступа (auth_option): 'a' для корня, код категории или плагина.
 * @param int    $rights  Числовая битовая маска прав:
 *                        1 = R, 2 = W, 4 = 1, 8 = 2, 16 = 3, 32 = 4, 64 = 5, 128 = A.
 *                        Например, 129 = R + A, 1 = только R.
 * @param int    $lock    Числовая маска блокировки (какие биты нельзя менять через интерфейс).
 *                        По умолчанию 254 = 'W12345A' (заблокировано всё, кроме R).
 * @return void
 * =====================================================================
 */
function cot_user_demo_admin_set_right(
    int $groupId,
    string $code,
    string $option,
    int $rights,
    int $lock = 254 // 254 = 'W12345A'
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
 * =====================================================================
 * Выдаёт демо-группе права «только чтение» на все модули, категории и плагины.
 *
 * Используется при первом создании группы для первоначальной инициализации.
 * Для каждого модуля:
 *   - корень модуля (option='a') получает R (1);
 *   - все категории структуры получают R (1).
 * Для активных плагинов выдаётся R + A (129), чтобы демо-админ мог
 * открывать их страницы в админ-панели (admin/other?p=...).
 * Блокировка прав всегда 254 ('W12345A').
 *
 * @param int $groupId ID группы демо-администраторов.
 * @return void
 * =====================================================================
 */
function cot_user_demo_admin_ensure_all_read(int $groupId): void
{
    // Специальные коды ядра
    foreach (['message', 'structure'] as $code) {
        cot_user_demo_admin_set_right($groupId, $code, 'a', 1, 254); // 254 = 'W12345A'
    }

    // Модули
    global $cot_modules;
    if (!empty($cot_modules) && is_array($cot_modules)) {
        foreach (array_keys($cot_modules) as $code) {
            if (in_array($code, ['admin', 'users'], true)) {
                continue;
            }

            // Корень модуля — только чтение
            cot_user_demo_admin_set_right($groupId, $code, 'a', 1, 254); // 254 = 'W12345A'

            // Все категории структуры этого модуля
            if (!empty(Cot::$structure[$code]) && is_array(Cot::$structure[$code])) {
                foreach (array_keys(Cot::$structure[$code]) as $cat) {
                    if ($cat === '' || $cat === 'all') {
                        continue;
                    }
                    cot_user_demo_admin_set_right($groupId, $code, $cat, 1, 254); // 254 = 'W12345A'
                }
            }
        }
    }

    // Активные плагины — R + A (129)
    $plugins = Cot::$db->query(
        'SELECT DISTINCT pl_code FROM ' . Cot::$db->plugins . ' WHERE pl_active = 1'
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($plugins as $code) {
        cot_user_demo_admin_set_right($groupId, 'plug', $code, 129, 254); // 254 = 'W12345A'
    }
}

/**
 * =====================================================================
 * Возвращает плоский список прав для интерфейса настройки прав демо-группы.
 *
 * Список включает только корневые права модулей (auth_option='a') и права на плагины.
 * Категории структуры не включаются, так как они всегда доступны только для чтения.
 *
 * Ключи элементов: 'core:имя', 'module:имя', 'plug:имя'.
 * Значение — булево, указывающее, установлен ли бит R (1).
 *
 * @param int $groupId ID группы демо-администраторов.
 * @return array<string, bool> Ассоциативный массив разрешений.
 * =====================================================================
 */
function cot_user_demo_admin_get_permissions(int $groupId): array
{
    global $cot_modules;

    $map = [];

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
        $result['core:' . $code] = $map['core:' . $code] ?? false;
    }

    // Modules
    if (!empty($cot_modules)) {
        foreach (array_keys($cot_modules) as $code) {
            if (in_array($code, ['admin', 'users'], true)) {
                continue;
            }
            $result['module:' . $code] = $map['module:' . $code] ?? false;
        }
    }

    // Plugins
    $plugins = Cot::$db->query(
        'SELECT DISTINCT pl_code FROM ' . Cot::$db->plugins . ' WHERE pl_active = 1'
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($plugins as $code) {
        $result['plug:' . $code] = $map['plug:' . $code] ?? false;
    }

    ksort($result);
    return $result;
}

/**
 * =====================================================================
 * Устанавливает разрешение/запрет для одного элемента из UI настройки прав.
 *
 * Для модулей и ядра: разрешено = R (1), запрещено = 0.
 * Для плагинов: разрешено = R + A (129) — чтобы открывались админ-страницы плагинов,
 *               запрещено = 0.
 *
 * После изменения права сбрасывается кэш прав всех пользователей группы демо-администраторов.
 *
 * @param int    $groupId ID группы.
 * @param string $itemKey Ключ элемента, например 'module:page', 'plug:someplug', 'core:message'.
 * @param bool   $allowed True — разрешить чтение, false — запретить.
 * @return void
 * =====================================================================
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

    cot_user_demo_admin_set_right($groupId, $code, $option, $rights, 254); // 254 = 'W12345A'

    // Категории структуры — всегда только чтение
    if ($option === 'a' && !empty(Cot::$structure[$code]) && is_array(Cot::$structure[$code])) {
        foreach (array_keys(Cot::$structure[$code]) as $cat) {
            if ($cat === '' || $cat === 'all') {
                continue;
            }
            cot_user_demo_admin_set_right($groupId, $code, $cat, 1, 254); // 254 = 'W12345A'
        }
    }
    Cot::$db->update(Cot::$db->users, ['user_auth' => ''], 'user_maingrp = ?', [$groupId]);
    cot_auth_clear('all');
}

/**
 * =====================================================================
 * Возвращает текущий URL без параметров, которые могут содержать опасные действия.
 *
 * Используется для редиректа после блокировки опасного запроса.
 * Удаляются все параметры, перечисленные в cot_user_demo_admin_get_action_params().
 *
 * @return string Очищенный URL.
 * =====================================================================
 */
function cot_demo_admin_get_current_url_without_dangerous_params(): string
{
    $actionParams = cot_user_demo_admin_get_action_params();
    $urlParts = parse_url($_SERVER['REQUEST_URI']);
    $path = $urlParts['path'] ?? '';
    $queryParams = [];
    if (isset($urlParts['query'])) {
        parse_str($urlParts['query'], $queryParams);
    }
    foreach ($actionParams as $param) {
        unset($queryParams[$param]);
    }
    $newQuery = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
    $url = $path;
    if ($newQuery !== '') {
        $url .= '?' . $newQuery;
    }
    if (isset($urlParts['fragment'])) {
        $url .= '#' . $urlParts['fragment'];
    }
    return $url;
}