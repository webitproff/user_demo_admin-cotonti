# User Demo Admin Plugin Guide for Cotonti

**Version:** 5.3.1  
**Date:** Aug 29, 2026
**Author:** **[webitproff](https://github.com/webitproff)**  
**License:** BSD  
**Requirements:** Cotonti **v.1.+**, users module

**[Source and updates](https://github.com/webitproff/user_demo_admin-cotonti)**

**[ReadMeMore](https://abuyfile.com/ru/market/cotonti/plugs/user-demo-admin)**

**[Support](https://abuyfile.com/ru/forums/cotonti/custom/plugs)**

**[Authorization Management API](https://github.com/Cotonti/Cotonti/blob/master/system/auth.php)**    

**[Group constants](https://github.com/Cotonti/Cotonti/blob/f43f1fc38ba4e02027786dad9dac1435c7c52b30/system/functions.php#L32)**

**[User and Authorization Subsystem](https://github.com/Cotonti/Cotonti/blob/f43f1fc38ba4e02027786dad9dac1435c7c52b30/system/functions.php#L1709)**

---
[![Version](https://img.shields.io/badge/version-5.1.1-green.svg)](https://github.com/webitproff/user_demo_admin-cotonti/releases)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti-1.0-orange.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.5-purple.svg)](https://www.php.net/releases/8_5_6.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-blue.svg)](https://www.mysql.com/)
[![Bootstrap v5.3.8](https://img.shields.io/badge/Bootstrap-v5.3.8-blueviolet.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://github.com/webitproff/user_demo_admin-cotonti/blob/main/LICENSE)

---
> The "User Demo Admin" plugin is a completely new and experimental extension for CMF Cotonti. Use it at your own risk. The plugin has not been fully tested to make bold statements. For the most part, the plugin solves my problems (as a developer), but I recommend that you use it with extreme caution!
---

<img width="1168" height="784" alt="User Demo Admin для Cotonti" src="https://github.com/user-attachments/assets/4597c6ed-e9ab-437b-be2b-155c157cb433" />

---

## Table of Contents

1. [Plugin Overview and Purpose](#1-plugin-overview-and-purpose)  
2. [Plugin Business Logic](#2-plugin-business-logic)  
   - 2.1. Creating the “Demo Admin” Group  
   - 2.2. Assigning Access Rights  
   - 2.3. Write Operation Protection  
   - 2.4. Creating a Demo Admin User  
   - 2.5. Managing Permissions via Admin UI  
3. [Demo Admin Interaction with the Site Interface](#3-demo-admin-interaction-with-the-site-interface)  
   - 3.1. What Can Be Viewed  
   - 3.2. Which Operations Are Forbidden  
   - 3.3. Behavior When Attempting to Save  
4. [Technical Installation and Removal Guide](#4-technical-installation-and-removal-guide)  
   - 4.1. Installation  
   - 4.2. Removal  
   - 4.3. Installation File (install.php)  
   - 4.4. Removal File (uninstall.php)  
   - 4.5. Plugin File Structure  
5. [Security Warnings](#5-security-warnings)  
6. [Recommendations for Further Modification](#6-recommendations-for-further-modification)  
7. [Conclusions](#7-conclusions)

---

## 1. Plugin Overview and Purpose

The **User Demo Admin** plugin creates a special user group with **read-only** access to the Cotonti administration panel. It is intended for demonstrations, training, or auditing, allowing trusted users to explore the admin interface without risking accidental changes.

Key features:

- Creates a “Demo Admin” group with limited rights.
- Automatically grants read‑only permissions to all active modules, plugins, and structure categories.
- Provides an admin interface to:
  - View the list of demo administrators.
  - Create new demo admin users.
  - Customize which modules/plugins the demo admins can see.
- Blocks all write operations for demo admins via a global hook (POST requests and dangerous actions are intercepted).

**Note:** The plugin does not alter the database schema. It uses standard Cotonti tables (`cot_groups`, `cot_auth`, `cot_groups_users`, `cot_users`).

---

## 2. Plugin Business Logic

### 2.1. Creating the “Demo Admin” Group

The group is created (or ensured) by the function `cot_user_demo_admin_ensure_group()`. It:

1. Looks for a group with the configured alias (default `demo_admin`).
2. If **not found**:
   - Creates a new group in `cot_groups` with:
     - `grp_name` = “Demo Admin”
     - `grp_title` = “Demo Admin”
     - `grp_level` = 50
     - `grp_alias` = `demo_admin` (configurable)
     - `grp_skiprights` = 0
     - `grp_pfs_maxfile` / `grp_pfs_maxtotal` = 0 (upload forbidden)
   - Calls `cot_user_demo_admin_ensure_all_read()` to set initial read-only rights for **all modules, plugins, and categories**.
3. If the group **already exists**:
   - Only ensures that the `admin` and `users` rights are present and correct:
     - `admin` → rights = **129** (R + A, to allow admin panel access)
     - `users` → rights = **1** (R, to view user lists)
   - **Does not** reset the rights of other modules/plugins/categories. This allows an administrator to later customize permissions via the plugin’s “Rights” tab without having them overwritten on every page load.

All rights are stored with `auth_rights_lock = 254`, preventing accidental modification through the standard Cotonti rights editor.

### 2.2. Assigning Access Rights

The plugin uses Cotonti’s standard bit‑mask system in the `cot_auth` table. The following rights are set for the Demo Admin group:

| Area                     | Code                | Rights (number) | Meaning                          |
|--------------------------|---------------------|-----------------|----------------------------------|
| Admin panel              | `admin`             | 129             | Read + Admin access (R + A)      |
| Users                    | `users`             | 1               | Read only (R)                    |
| Core (messages, structure) | `message`, `structure` | 1            | Read only (R)                    |
| All modules (except admin, users) | module code | 1            | Read only (R)                    |
| All plugins (active)     | `plug` / code       | **129**         | Read + Admin (R + A) – allows viewing plugin admin pages |
| Structure categories     | module code / category | 1            | Read only (R) – allows viewing content |

> **Why plugins get 129 instead of 1?**  
> In Cotonti, accessing a plugin’s admin page (`admin/other?p=plugin`) requires both **R** and **A** rights. Giving only **R** would still lead to “Access denied” on those pages. Hence the plugin sets **129** for all active plugins.  
> Modules, on the other hand, often only require **R** to view their admin pages (e.g., `admin.php?m=page`), so they receive **1**.

All structure categories are assigned **R = 1** to ensure that content visibility checks (which often involve category rights) pass for the demo admin.

### 2.3. Write Operation Protection

Because Cotonti’s standard rights system alone cannot fully prevent writes when a user has admin panel access, the plugin adds an additional layer of protection via the global hook `user_demo_admin.global.php`.

For any user belonging to the Demo Admin group:

- **All POST requests are blocked** except those related to login/logout (`m=login` or `a=logout`).  
  When such a request is detected:
  - `$_POST` is cleared.
  - A warning message “Режим демонстрации: изменения не сохраняются…” is shown.
  - The user is redirected back (to the previous page or to the admin home).
- **Dangerous GET actions** (list of `a` values like `update`, `save`, `delete`, `install`, `config`, etc.) are also blocked with a redirect.
- **Direct access to the User Demo Admin plugin page** (`m=other & p=user_demo_admin`) is denied for demo admins (they cannot manage the group itself).

This ensures that even if a demo admin tries to submit a form manually, the request never reaches the target script.

### 2.4. Creating a Demo Admin User

In the plugin’s admin interface, under the “Create User” tab, a real administrator can enter a name, email, and password. After validation (length, format, uniqueness), the user is created using `cot_add_user()` with the main group set to the Demo Admin group ID. No activation email is sent (`$sendemail = false`).

The new user automatically inherits all rights of the Demo Admin group.

### 2.5. Managing Permissions via Admin UI

The plugin provides a “Rights” tab where administrators can customize which modules/plugins the demo admins can view:

- A list of all **core codes** (message, structure), **active modules** (except admin and users), and **active plugins** is displayed with radio buttons for **Allow** / **Deny**.
- Saving the form updates the `cot_auth` entries:
  - For modules/core: allowed = **1** (R), denied = **0**.
  - For plugins: allowed = **129** (R + A), denied = **0**.
- When a module’s root permission is changed, all its structure categories are automatically kept at **R = 1** (to prevent breaking frontend viewing).
- The rights cache is cleared after saving.

**Important:** The initial read-only rights for all modules/plugins are set **only when the group is first created**. After that, administrators can freely change permissions via this tab without them being reset on every page load.

---

## 3. Demo Admin Interaction with the Site Interface

### 3.1. What Can Be Viewed

A demo admin can log into the admin panel and view almost all sections:

- **Home page** of the admin panel.
- **Configuration** – all tabs (view only).
- **Users** – user list, profiles.
- **Pages** (if installed) – page list, categories, content preview.
- **Extensions** – list of modules and plugins, their settings (read only).
- **Structure** – categories and parameters.
- **Tools**, **Files**, **Cache**, and other sections available to a regular administrator.

Thanks to the R rights on all modules and categories, the demo admin will not see “Access denied” when opening pages.

| Zone                      | Allowed                                      | Forbidden / What happens                  |
|---------------------------|----------------------------------------------|-------------------------------------------|
| **Admin login**           | Yes                                          | –                                         |
| **Viewing admin sections**| Almost all sections                          | –                                         |
| **Buttons (Save, Update, Delete, etc.)** | Visible, but clicking triggers block | Data is not saved; warning + redirect    |
| **POST requests**         | Only login/logout                            | Any other POST is blocked                 |
| **Dangerous actions (GET)** | Not allowed (update, save, delete, etc.)   | Redirect to admin home                    |
| **User Demo Admin plugin page** | Forbidden (for demo admins)             | Access denied (error 930)                 |

### 3.2. Which Operations Are Forbidden

All operations that modify the system state are blocked:

- Saving any settings (configuration, themes, modules, plugins).
- Adding, editing, deleting users, pages, categories, files, etc.
- Installing, updating, removing extensions.
- Changing access rights.
- Clearing cache, executing SQL queries, and other dangerous actions.

Even if the interface shows “Save”, “Update”, or “Delete” buttons, the corresponding requests are intercepted and never executed.

### 3.3. Behavior When Attempting to Save

1. The demo admin clicks a “Save” button.
2. The browser sends a **POST** request.
3. The global hook detects the POST and the user is a demo admin (and the request is not login/logout).
4. The plugin:
   - Clears `$_POST`.
   - Shows a warning: “Режим демонстрации: изменения не сохраняются. Вы можете только просматривать интерфейс.”
   - Redirects back to the previous page (or admin home).
5. The target script does not execute, and no data is changed.

For **GET** requests with dangerous action parameters (e.g., `a=delete`), the same warning and redirect occur.

---

## 4. Technical Installation and Removal Guide

### 4.1. Installation

1. Copy the `user_demo_admin` folder to the `plugins/` directory.
2. Log in to the Cotonti administration panel with full rights.
3. Go to **Extensions** → **Plugins**.
4. Find **User Demo Admin** and click **Install**.

After installation:

- The group and initial rights are created if missing.
- The plugin appears under **Administration** → **User Demo Admin** (link: `admin.php?m=other&p=user_demo_admin`).
- You can immediately start creating demo users or customizing permissions in the “Rights” tab.

### 4.2. Removal

1. In **Extensions** → **Plugins**, locate **User Demo Admin**.
2. Click **Uninstall**.

The uninstall script:

- Finds the group by alias.
- Moves all users of that group to the **Members** group (ID = 4).
- Deletes all user–group links in `cot_groups_users`.
- Deletes all group rights from `cot_auth`.
- Deletes the group itself from `cot_groups`.
- Clears the authorization cache (`cot_auth_clear('all')`).

**Note:** After removal, demo users become regular members and lose admin access.

### 4.3. Installation File (install.php)

```php
defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('user_demo_admin', 'plug', 'functions');

$groupId = cot_user_demo_admin_ensure_group();

if (!$groupId) {
    cot_error('Failed to create Demo Admin group');
}
```

**What it does:**

- Includes the plugin’s functions.
- Calls `cot_user_demo_admin_ensure_group()` – creates the group (if needed) and sets initial rights.
- Displays an error if the group cannot be created.

### 4.4. Removal File (uninstall.php)

```php
defined('COT_CODE') or die('Wrong URL');

$alias = Cot::$cfg['plugin']['user_demo_admin']['group_alias'] ?? 'demo_admin';

$group = Cot::$db->query(
    'SELECT grp_id FROM ' . Cot::$db->groups . ' WHERE grp_alias = ? LIMIT 1',
    [$alias]
)->fetch();

if ($group) {
    $groupId = (int) $group['grp_id'];

    Cot::$db->update(
        Cot::$db->users,
        ['user_maingrp' => COT_GROUP_MEMBERS],
        'user_maingrp = ?',
        [$groupId]
    );

    Cot::$db->delete(Cot::$db->groups_users, 'gru_groupid = ?', [$groupId]);
    Cot::$db->delete(Cot::$db->auth, 'auth_groupid = ?', [$groupId]);
    Cot::$db->delete(Cot::$db->groups, 'grp_id = ?', [$groupId]);

    cot_auth_clear('all');
}
```

**What it does:**

- Finds the group ID by alias.
- Moves all users of this group to the standard Members group (ID 4).
- Deletes links, rights, and the group itself.
- Clears the rights cache.

### 4.5. Plugin File Structure

```
plugins/user_demo_admin/
├── user_demo_admin.setup.php          — registration and settings
├── user_demo_admin.global.php         — write protection
├── user_demo_admin.admin.php          — admin interface (tabs: list, create, rights)
├── inc/
│   └── user_demo_admin.functions.php  — business logic
├── lang/
│   └── user_demo_admin.ru.lang.php    — Russian language file
├── tpl/
│   └── user_demo_admin.admin.tpl      — admin template
└── setup/
    ├── user_demo_admin.install.php
    └── user_demo_admin.uninstall.php
```

---

## 5. Security Warnings

1. **Protection bypass via unusual requests.**  
   The plugin blocks all POST requests (except login/logout) and known dangerous GET actions. However, a determined attacker might find a way to trigger a write via an unlisted GET parameter or a direct script call. The global hook covers most cases, but 100% isolation cannot be guaranteed.

2. **Confidential data exposure.**  
   Demo admins can see user emails, configuration settings, file paths, installed extensions, and other sensitive information. Grant access only to trusted individuals.

3. **Performance impact.**  
   Users with admin access can open heavy pages (e.g., large user lists), which may load the server. Limit the number of demo users if necessary.

4. **Rights lock.**  
   All rights have `auth_rights_lock = 254`, which prevents modification via the standard UI. However, someone with database access could change this value. Periodically verify the group’s rights.

5. **Incompatibility with third‑party plugins.**  
   Some plugins may use AJAX or other methods not intercepted by the global hook. Test critical extensions before granting demo access.

6. **Strong passwords.**  
   Always use strong passwords for demo accounts.

7. **Remove demo users when no longer needed.**  
   Delete or block demo accounts after the demonstration or testing is complete.

---

## 6. Recommendations for Further Modification

1. **Finer control over blocked actions.**  
   Extend the `$dangerousActions` list or add URL pattern checks in `user_demo_admin.global.php`.

2. **AJAX protection.**  
   Intercept `XMLHttpRequest` (header `X-Requested-With`) and block POST AJAX requests similarly.

3. **Logging.**  
   Add `cot_log()` calls in the blocking part to record attempted writes.

4. **Custom messages.**  
   Replace the default warning with a more informative one.

5. **English language file.**  
   Create `user_demo_admin.en.lang.php` for multilingual sites.

6. **Whitelist of allowed admin sections.**  
   Instead of granting access to all modules, implement a list of sections that demo admins can view.

7. **Move dangerous action list to plugin settings.**  
   Allow administrators to edit the list of dangerous actions from the admin panel.

8. **Add a banner in the admin header.**  
   Display a persistent “Demo mode” notice for demo admins.

9. **Extra fields support.**  
   If additional user fields are used, extend the creation form and `cot_add_user()` call accordingly.

10. **Hook after user creation.**  
    Trigger a custom hook after a demo user is created for additional processing.

---

## 7. Conclusions

The **User Demo Admin** plugin provides a reliable way to grant read‑only access to the Cotonti admin panel. It combines standard rights (R on most objects, A on the admin panel) with an extra layer of write protection via a global hook. The admin interface allows flexible management of demo users and permissions.



___



# Руководство по плагину User Demo Admin для Cotonti

**Версия:** 5.1.1  
**Автор:** webitproff  
**Лицензия:** BSD  
**Требования:** Cotonti (актуальная версия), модуль users

> Плагин «User Demo Admin» — это совершенно новое и экспериментальное расширение для CMF Cotonti. Используйте его на свой страх и риск. Плагин не был полностью протестирован, чтобы делать смелые заявления. По большей части плагин решает мои задачи (как разработчика), но я рекомендую использовать его с особой осторожностью!

<img width="1168" height="784" alt="User Demo Admin для Cotonti" src="https://github.com/user-attachments/assets/4597c6ed-e9ab-437b-be2b-155c157cb433" />

---

## Содержание

1. [Обзор и назначение плагина](#1-plugin-overview-and-purpose-ru)  
2. [Бизнес-логика плагина](#2-plugin-business-logic-ru)  
   - 2.1. [Создание группы «Демо-администратор»](#21-creating-the-demo-admin-group-ru)  
   - 2.2. [Назначение прав доступа](#22-assigning-access-rights-ru)  
   - 2.3. [Защита от операций записи](#23-write-operation-protection-ru)  
   - 2.4. [Создание пользователя демо-администратора](#24-creating-a-demo-admin-user-ru)  
   - 2.5. [Управление правами через интерфейс администратора](#25-managing-permissions-via-admin-ui-ru)  
3. [Взаимодействие демо-администратора с интерфейсом сайта](#3-demo-admin-interaction-with-the-site-interface-ru)  
   - 3.1. [Что можно просматривать](#31-what-can-be-viewed-ru)  
   - 3.2. [Какие операции запрещены](#32-which-operations-are-forbidden-ru)  
   - 3.3. [Поведение при попытке сохранения](#33-behavior-when-attempting-to-save-ru)  
4. [Техническое руководство по установке и удалению](#4-technical-installation-and-removal-guide-ru)  
   - 4.1. [Установка](#41-installation-ru)  
   - 4.2. [Удаление](#42-removal-ru)  
   - 4.3. [Файл установки (install.php)](#43-installation-file-installphp-ru)  
   - 4.4. [Файл удаления (uninstall.php)](#44-removal-file-uninstallphp-ru)  
   - 4.5. [Структура файлов плагина](#45-plugin-file-structure-ru)  
5. [Предупреждения безопасности](#5-security-warnings-ru)  
6. [Рекомендации по дальнейшей модификации](#6-recommendations-for-further-modification-ru)  
7. [Выводы](#7-conclusions-ru)

---

<a name="1-plugin-overview-and-purpose-ru"></a>
## 1. Обзор и назначение плагина

Плагин **User Demo Admin** создаёт специальную группу пользователей с доступом **только для чтения** к панели администрирования Cotonti. Он предназначен для демонстраций, обучения или аудита, позволяя доверенным пользователям изучать интерфейс администратора без риска случайных изменений.

Ключевые возможности:

- Создаёт группу «Демо-администратор» с ограниченными правами.
- Автоматически предоставляет права только на чтение всем активным модулям, плагинам и категориям структуры.
- Предоставляет административный интерфейс для:
  - просмотра списка демо-администраторов;
  - создания новых пользователей демо-администраторов;
  - настройки того, какие модули/плагины могут видеть демо-администраторы.
- Блокирует все операции записи для демо-администраторов через глобальный хук (перехватываются POST-запросы и опасные действия).

**Примечание:** Плагин не изменяет схему базы данных. Он использует стандартные таблицы Cotonti (`cot_groups`, `cot_auth`, `cot_groups_users`, `cot_users`).

---

<a name="2-plugin-business-logic-ru"></a>
## 2. Бизнес-логика плагина

<a name="21-creating-the-demo-admin-group-ru"></a>
### 2.1. Создание группы «Демо-администратор»

Группа создаётся (или проверяется) функцией `cot_user_demo_admin_ensure_group()`. Она:

1. Ищет группу с заданным в настройках алиасом (по умолчанию `demo_admin`).
2. Если группа **не найдена**:
   - Создаёт новую группу в `cot_groups` со следующими параметрами:
     - `grp_name` = «Demo Admin»
     - `grp_title` = «Demo Admin»
     - `grp_level` = 50
     - `grp_alias` = `demo_admin` (настраивается)
     - `grp_skiprights` = 0
     - `grp_pfs_maxfile` / `grp_pfs_maxtotal` = 0 (загрузка запрещена)
   - Вызывает `cot_user_demo_admin_ensure_all_read()` для установки начальных прав только на чтение для **всех модулей, плагинов и категорий**.
3. Если группа **уже существует**:
   - Только проверяет, что права `admin` и `users` присутствуют и корректны:
     - `admin` → права = **129** (R + A, чтобы разрешить доступ в админ-панель)
     - `users` → права = **1** (R, чтобы просматривать списки пользователей)
   - **Не сбрасывает** права остальных модулей/плагинов/категорий. Это позволяет администратору впоследствии настраивать права через вкладку «Права» плагина, не опасаясь, что они будут перезаписаны при каждой загрузке страницы.

Все права сохраняются с `auth_rights_lock = 254`, что предотвращает случайное изменение через стандартный редактор прав Cotonti.

<a name="22-assigning-access-rights-ru"></a>
### 2.2. Назначение прав доступа

Плагин использует стандартную систему битовых масок Cotonti в таблице `cot_auth`. Для группы демо-администратора устанавливаются следующие права:

| Область                     | Код                | Права (число) | Значение                          |
|-----------------------------|--------------------|---------------|-----------------------------------|
| Панель администратора       | `admin`            | 129           | Чтение + доступ администратора (R + A) |
| Пользователи                | `users`            | 1             | Только чтение (R)                 |
| Ядро (сообщения, структура) | `message`, `structure` | 1          | Только чтение (R)                 |
| Все модули (кроме admin, users) | код модуля      | 1             | Только чтение (R)                 |
| Все активные плагины        | `plug` / код       | **129**       | Чтение + доступ администратора (R + A) – позволяет просматривать страницы администрирования плагинов |
| Категории структуры         | код модуля / категория | 1         | Только чтение (R) – позволяет просматривать контент |

> **Почему плагины получают 129, а не 1?**  
> В Cotonti доступ к странице администрирования плагина (`admin/other?p=plugin`) требует наличия прав **R** и **A**. Если дать только **R**, то на таких страницах всё равно будет «Доступ запрещён». Поэтому плагин устанавливает **129** для всех активных плагинов.  
> Модули, с другой стороны, часто требуют только **R** для просмотра своих административных страниц (например, `admin.php?m=page`), поэтому они получают **1**.

Всем категориям структуры назначается **R = 1**, чтобы гарантировать, что проверки видимости контента (которые часто включают права категорий) проходили успешно для демо-администратора.

<a name="23-write-operation-protection-ru"></a>
### 2.3. Защита от операций записи

Поскольку стандартная система прав Cotonti не может полностью предотвратить запись, когда у пользователя есть доступ к админ-панели, плагин добавляет дополнительный уровень защиты через глобальный хук `user_demo_admin.global.php`.

Для любого пользователя, принадлежащего к группе демо-администратора:

- **Все POST-запросы блокируются**, кроме связанных с входом/выходом (`m=login` или `a=logout`).  
  При обнаружении такого запроса:
  - `$_POST` очищается.
  - Показывается предупреждение «Режим демонстрации: изменения не сохраняются…».
  - Пользователь перенаправляется обратно (на предыдущую страницу или на главную админ-панели).
- **Опасные GET-действия** (список значений `a`, таких как `update`, `save`, `delete`, `install`, `config` и т.д.) также блокируются с перенаправлением.
- **Прямой доступ к странице плагина User Demo Admin** (`m=other & p=user_demo_admin`) запрещён для демо-администраторов (они не могут управлять самой группой).

Это гарантирует, что даже если демо-администратор попытается вручную отправить форму, запрос никогда не достигнет целевого скрипта.

<a name="24-creating-a-demo-admin-user-ru"></a>
### 2.4. Создание пользователя демо-администратора

В административном интерфейсе плагина, на вкладке «Создать пользователя», настоящий администратор может ввести имя, email и пароль. После проверки (длина, формат, уникальность) пользователь создаётся с помощью `cot_add_user()` с основной группой, установленной на ID группы демо-администратора. Письмо для активации не отправляется (`$sendemail = false`).

Новый пользователь автоматически наследует все права группы демо-администратора.

<a name="25-managing-permissions-via-admin-ui-ru"></a>
### 2.5. Управление правами через интерфейс администратора

Плагин предоставляет вкладку «Права», где администраторы могут настраивать, какие модули/плагины могут просматривать демо-администраторы:

- Отображается список всех **кодов ядра** (message, structure), **активных модулей** (кроме admin и users) и **активных плагинов** с радиокнопками **Разрешено** / **Запрещено**.
- При сохранении формы обновляются записи в `cot_auth`:
  - Для модулей/ядра: разрешено = **1** (R), запрещено = **0**.
  - Для плагинов: разрешено = **129** (R + A), запрещено = **0**.
- При изменении корневого права модуля все его категории структуры автоматически сохраняют **R = 1** (чтобы не сломать просмотр на фронтенде).
- После сохранения очищается кеш прав.

**Важно:** Начальные права только на чтение для всех модулей/плагинов устанавливаются **только при первом создании группы**. После этого администраторы могут свободно менять права через эту вкладку, и они не будут сбрасываться при каждой загрузке страницы.

---

<a name="3-demo-admin-interaction-with-the-site-interface-ru"></a>
## 3. Взаимодействие демо-администратора с интерфейсом сайта

<a name="31-what-can-be-viewed-ru"></a>
### 3.1. Что можно просматривать

Демо-администратор может войти в админ-панель и просматривать почти все разделы:

- **Главная страница** админ-панели.
- **Конфигурация** – все вкладки (только просмотр).
- **Пользователи** – список пользователей, профили.
- **Страницы** (если установлены) – список страниц, категории, предпросмотр контента.
- **Расширения** – список модулей и плагинов, их настройки (только чтение).
- **Структура** – категории и параметры.
- **Инструменты**, **Файлы**, **Кеш** и другие разделы, доступные обычному администратору.

Благодаря правам R на все модули и категории, демо-администратор не увидит сообщений «Доступ запрещён» при открытии страниц.

| Зона                      | Разрешено                                   | Запрещено / Что происходит                |
|---------------------------|---------------------------------------------|-------------------------------------------|
| **Вход в админку**        | Да                                          | –                                         |
| **Просмотр разделов админки** | Почти все разделы                        | –                                         |
| **Кнопки (Сохранить, Обновить, Удалить и т.д.)** | Видны, но при нажатии срабатывает блокировка | Данные не сохраняются; предупреждение + перенаправление |
| **POST-запросы**          | Только вход/выход                           | Любой другой POST блокируется            |
| **Опасные действия (GET)** | Не разрешены (update, save, delete и т.д.) | Перенаправление на главную админки        |
| **Страница плагина User Demo Admin** | Запрещена (для демо-админов)          | Доступ запрещён (ошибка 930)             |

<a name="32-which-operations-are-forbidden-ru"></a>
### 3.2. Какие операции запрещены

Все операции, изменяющие состояние системы, блокируются:

- Сохранение любых настроек (конфигурация, темы, модули, плагины).
- Добавление, редактирование, удаление пользователей, страниц, категорий, файлов и т.д.
- Установка, обновление, удаление расширений.
- Изменение прав доступа.
- Очистка кеша, выполнение SQL-запросов и другие опасные действия.

Даже если интерфейс показывает кнопки «Сохранить», «Обновить» или «Удалить», соответствующие запросы перехватываются и никогда не выполняются.

<a name="33-behavior-when-attempting-to-save-ru"></a>
### 3.3. Поведение при попытке сохранения

1. Демо-администратор нажимает кнопку «Сохранить».
2. Браузер отправляет **POST**-запрос.
3. Глобальный хук обнаруживает POST и то, что пользователь — демо-администратор (и запрос не является входом/выходом).
4. Плагин:
   - Очищает `$_POST`.
   - Показывает предупреждение: «Режим демонстрации: изменения не сохраняются. Вы можете только просматривать интерфейс.»
   - Перенаправляет обратно на предыдущую страницу (или на главную админки).
5. Целевой скрипт не выполняется, и данные не изменяются.

Для **GET**-запросов с опасными параметрами действия (например, `a=delete`) происходит аналогичное предупреждение и перенаправление.

---

<a name="4-technical-installation-and-removal-guide-ru"></a>
## 4. Техническое руководство по установке и удалению

<a name="41-installation-ru"></a>
### 4.1. Установка

1. Скопируйте папку `user_demo_admin` в директорию `plugins/`.
2. Войдите в панель администрирования Cotonti с полными правами.
3. Перейдите в **Расширения** → **Плагины**.
4. Найдите **User Demo Admin** и нажмите **Установить**.

После установки:

- Группа и начальные права создаются, если их нет.
- Плагин появляется в разделе **Администрирование** → **User Demo Admin** (ссылка: `admin.php?m=other&p=user_demo_admin`).
- Можно сразу начинать создавать демо-пользователей или настраивать права на вкладке «Права».

<a name="42-removal-ru"></a>
### 4.2. Удаление

1. В **Расширения** → **Плагины** найдите установленный **User Demo Admin**.
2. Нажмите **Удалить**.

Скрипт удаления:

- Находит группу по алиасу.
- Переводит всех пользователей этой группы в группу **Members** (ID = 4).
- Удаляет все связи пользователь-группа в `cot_groups_users`.
- Удаляет все права группы из `cot_auth`.
- Удаляет саму группу из `cot_groups`.
- Очищает кеш авторизации (`cot_auth_clear('all')`).

**Примечание:** После удаления демо-пользователи становятся обычными участниками и теряют доступ к админке.

<a name="43-installation-file-installphp-ru"></a>
### 4.3. Файл установки (install.php)

```php
defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('user_demo_admin', 'plug', 'functions');

$groupId = cot_user_demo_admin_ensure_group();

if (!$groupId) {
    cot_error('Failed to create Demo Admin group');
}
```

**Что делает:**

- Подключает функции плагина.
- Вызывает `cot_user_demo_admin_ensure_group()` — создаёт группу (если нужно) и устанавливает начальные права.
- Выводит ошибку, если группа не может быть создана.

<a name="44-removal-file-uninstallphp-ru"></a>
### 4.4. Файл удаления (uninstall.php)

```php
defined('COT_CODE') or die('Wrong URL');

$alias = Cot::$cfg['plugin']['user_demo_admin']['group_alias'] ?? 'demo_admin';

$group = Cot::$db->query(
    'SELECT grp_id FROM ' . Cot::$db->groups . ' WHERE grp_alias = ? LIMIT 1',
    [$alias]
)->fetch();

if ($group) {
    $groupId = (int) $group['grp_id'];

    Cot::$db->update(
        Cot::$db->users,
        ['user_maingrp' => COT_GROUP_MEMBERS],
        'user_maingrp = ?',
        [$groupId]
    );

    Cot::$db->delete(Cot::$db->groups_users, 'gru_groupid = ?', [$groupId]);
    Cot::$db->delete(Cot::$db->auth, 'auth_groupid = ?', [$groupId]);
    Cot::$db->delete(Cot::$db->groups, 'grp_id = ?', [$groupId]);

    cot_auth_clear('all');
}
```

**Что делает:**

- Находит ID группы по алиасу.
- Переводит всех пользователей этой группы в стандартную группу Members (ID 4).
- Удаляет связи, права и саму группу.
- Очищает кеш прав.

<a name="45-plugin-file-structure-ru"></a>
### 4.5. Структура файлов плагина

```
plugins/user_demo_admin/
├── user_demo_admin.setup.php          — регистрация и настройки
├── user_demo_admin.global.php         — защита от записи
├── user_demo_admin.admin.php          — административный интерфейс (вкладки: список, создание, права)
├── inc/
│   └── user_demo_admin.functions.php  — бизнес-логика
├── lang/
│   └── user_demo_admin.ru.lang.php    — русский языковой файл
├── tpl/
│   └── user_demo_admin.admin.tpl      — шаблон админки
└── setup/
    ├── user_demo_admin.install.php
    └── user_demo_admin.uninstall.php
```

---

<a name="5-security-warnings-ru"></a>
## 5. Предупреждения безопасности

1. **Обход защиты через нестандартные запросы.**  
   Плагин блокирует все POST-запросы (кроме входа/выхода) и известные опасные GET-действия. Однако целеустремлённый злоумышленник может найти способ вызвать запись через неучтённый GET-параметр или прямой вызов скрипта. Глобальный хук покрывает большинство случаев, но 100% изоляция не гарантируется.

2. **Раскрытие конфиденциальных данных.**  
   Демо-администраторы могут видеть email пользователей, параметры конфигурации, пути к файлам, установленные расширения и другую чувствительную информацию. Предоставляйте доступ только доверенным лицам.

3. **Влияние на производительность.**  
   Пользователи с доступом к админке могут открывать тяжёлые страницы (например, большие списки пользователей), что может нагрузить сервер. При необходимости ограничьте количество демо-пользователей.

4. **Блокировка прав.**  
   Все права имеют `auth_rights_lock = 254`, что предотвращает изменение через стандартный интерфейс. Однако тот, у кого есть доступ к базе данных, может изменить это значение. Периодически проверяйте права группы.

5. **Несовместимость со сторонними плагинами.**  
   Некоторые плагины могут использовать AJAX или другие методы, не перехватываемые глобальным хуком. Протестируйте критические расширения перед предоставлением демо-доступа.

6. **Надёжные пароли.**  
   Всегда используйте стойкие пароли для демо-аккаунтов.

7. **Удаляйте демо-пользователей, когда они больше не нужны.**  
   Удалите или заблокируйте демо-аккаунты после завершения демонстрации или тестирования.

---

<a name="6-recommendations-for-further-modification-ru"></a>
## 6. Рекомендации по дальнейшей модификации

1. **Более тонкий контроль над блокируемыми действиями.**  
   Расширьте список `$dangerousActions` или добавьте проверки шаблонов URL в `user_demo_admin.global.php`.

2. **Защита от AJAX.**  
   Перехватывайте заголовок `X-Requested-With` (XMLHttpRequest) и аналогично блокируйте POST AJAX-запросы.

3. **Ведение журнала.**  
   Добавьте вызовы `cot_log()` в блокирующую часть для записи попыток записи.

4. **Настраиваемые сообщения.**  
   Замените стандартное предупреждение на более информативное.

5. **Английский языковой файл.**  
   Создайте `user_demo_admin.en.lang.php` для мультиязычных сайтов.

6. **Белый список разрешённых разделов админки.**  
   Вместо предоставления доступа ко всем модулям, реализуйте список разделов, которые демо-администраторы могут просматривать.

7. **Перенос списка опасных действий в настройки плагина.**  
   Позвольте администраторам редактировать список опасных действий из панели администрирования.

8. **Баннер в шапке админки.**  
   Отображайте постоянное уведомление «Демо-режим» для демо-администраторов.

9. **Поддержка дополнительных полей.**  
   Если используются дополнительные поля пользователей, расширьте форму создания и вызов `cot_add_user()` соответствующим образом.

10. **Хук после создания пользователя.**  
    Запускайте пользовательский хук после создания демо-пользователя для дополнительной обработки.

---

<a name="7-conclusions-ru"></a>
## 7. Выводы

Плагин **User Demo Admin** предоставляет надёжный способ предоставить доступ только для чтения к панели администрирования Cotonti. Он сочетает стандартные права (R на большинстве объектов, A на панели администратора) с дополнительным уровнем защиты от записи через глобальный хук. Административный интерфейс позволяет гибко управлять демо-пользователями и правами.


как же он меня заебал........ 
