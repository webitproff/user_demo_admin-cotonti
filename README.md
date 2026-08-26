# User Demo Admin Plugin Guide for Cotonti

**Version:** 5.0.0  
**Author:** webitproff  
**License:** BSD  
**Requirements:** Cotonti (current version), users module

---

## Table of Contents

1. [Plugin Overview and Purpose](#1-plugin-overview-and-purpose)  
2. [Plugin Business Logic](#2-plugin-business-logic)  
   - 2.1. Creating the “Demo Admin” Group  
   - 2.2. Assigning Access Rights  
   - 2.3. Write Operation Protection  
   - 2.4. Creating a Demo Admin User  
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

The **User Demo Admin** plugin is designed to create users with a special access level — a “demo administrator” or “read-only administrator”.

**Main goal** — to provide limited access to the Cotonti administration panel to trusted individuals (for audit, training, or demonstration) without the risk of accidental or intentional changes to settings, content, rights, or site structure.

A user in the **Demo Admin** group will be able to:

- log in to the administration panel;
- view almost all admin sections, including settings, user lists, pages, modules, and plugins;
- see the interface and data exactly as a full administrator sees them.

However, they **will not be able to**:

- save changes (configuration, content, rights);
- delete or edit records;
- install/uninstall extensions;
- perform any operations that alter the system state.

In fact, the plugin simulates a “view only” mode for the admin panel, similar to the demo mode in OpenCart or PrestaShop.

**Important:** the plugin does not change the Cotonti database structure. Only standard tables are used: `cot_groups`, `cot_auth`, `cot_groups_users`, `cot_users`.

---

## 2. Plugin Business Logic

### 2.1. Creating the “Demo Admin” Group

On the first plugin call (or during installation), the function `cot_user_demo_admin_ensure_group()` checks for the existence of a group with the specified alias (default `demo_admin`). If the group is not found, it is created in the `cot_groups` table with the following parameters:

- `grp_name` = “Demo Admin”
- `grp_title` = “Demo Admin”
- `grp_level` = 50 (high enough to appear as a privileged group)
- `grp_alias` = `demo_admin` (configurable)
- `grp_skiprights` = 0 (rights are used)
- `grp_pfs_maxfile` / `grp_pfs_maxtotal` = 0 (file upload is forbidden)

### 2.2. Assigning Access Rights

The plugin uses the standard Cotonti rights system based on bit masks in the `cot_auth` table. The following rights are set for the demo administrator group:

| Area                     | Code                | Rights (number) | Meaning                           |
|--------------------------|---------------------|-----------------|-----------------------------------|
| Admin panel              | `admin`             | 129             | R (read) + A (admin access)       |
| Users                    | `users`             | 1               | R (read only)                     |
| Messages                 | `message`           | 1               | R                                 |
| Structure                | `structure`         | 1               | R                                 |
| All modules              | (each)              | 1               | R                                 |
| All plugins              | `plug` / code       | 1               | R                                 |
| All structure categories | (module code) / category | 1           | R (for each category)             |

Bit masks:
- **R** = 1 (read)
- **W** = 2 (write)
- **A** = 128 (administrative access)

For `admin`, the value is set to 129 = 1+128, which gives access to the admin panel but **does not** grant write (W) or additional privileges (1-5).  
All other areas receive only R = 1.

**Rights for structure categories.** The plugin grants R rights not only to module root areas (`auth_option = 'a'`) but also to **every structure category** (for example, all categories of the `page`, `forums` modules, etc.). This is necessary so that the demo administrator can see content that is checked through category rights. Without this step, the user would not see even public pages and records.

For all rights, `auth_rights_lock` is set to 254, which blocks changing these rights through the standard editing interface (R, W, A, and additional bits are locked). This prevents accidental expansion of demo group rights by the demo user themselves.

### 2.3. Write Operation Protection

Since the standard Cotonti rights system cannot fully prevent writing when admin panel access exists (many scripts only check `cot_auth('admin', 'a', 'A')`), the plugin adds **an additional layer of protection** through a global hook.

In the file `user_demo_admin.global.php`, the following check is implemented:

- If the current user belongs to the Demo Admin group **and** is in the admin panel:
  - the `Cot::$usr['auth_write']` variable is forcibly set to `false`;
  - the incoming request is analyzed:
    - if the method is `POST` → considered a write attempt;
    - if the `a` parameter (action) is in the dangerous list (`update`, `save`, `add`, `edit`, `delete`, `install`, `uninstall`, `config`, `rights`, etc.) → write attempt;
    - if `m=config` or `m=rights` is requested → write attempt.
- When a write attempt is detected:
  - `$_POST` and `$_REQUEST` arrays are cleared;
  - a warning “Demo mode: changes are not saved...” is shown;
  - a redirect to the previous page (or admin home) is performed.

Thus, any POST requests and actions that usually lead to data changes are blocked before the main logic is executed.

### 2.4. Creating a Demo Admin User

In the plugin's admin interface (the “Create User” tab), an administrator can enter a name, email, and password. After data validation (length, format, uniqueness), the standard function `cot_add_user()` is called with the main group `$groupId` (the Demo Admin group ID). The created user immediately receives all rights of this group.

An activation email is not sent (`$sendemail = false`).

---

## 3. Demo Admin Interaction with the Site Interface

### 3.1. What Can Be Viewed

After logging into the admin panel, the demo administrator will see the standard left menu and can open the following sections:

- **Admin home page** (overview information).
- **Settings** (site configuration) — all tabs: general, security, performance, themes, etc. (in view mode).
- **Users** — user list, search, profile viewing (without edit buttons).
- **Pages** (if the page module is installed) — page list, category structure, content preview.
- **Extensions** — list of modules and plugins, their settings (view only; “Save” buttons will be inactive or their click will be blocked).
- **Site structure** — viewing categories and parameters.
- **Access rights** — rights group viewing interface (display may be possible, but saving is not).
- **Tools**, **Files**, **Cache**, and other sections available to a regular administrator.

Thanks to the set R rights on all modules and categories, the demo admin will not see “Access denied” messages when trying to open pages.

Below is a summary table of access:

| Zone                          | What is allowed                        | What is forbidden / what happens     |
|-------------------------------|----------------------------------------|--------------------------------------|
| **Frontend**                  | Read pages, lists, user profiles, categories | Edit, delete, add content (if no other groups) |
| **Admin login**               | Yes                                    | —                                    |
| **Viewing admin sections**    | Almost all sections (Configuration, Extensions, Structure, Users, Page, etc.) | —                                    |
| **“Save”, “Update”, “Add”, “Delete” buttons** | Sees buttons and forms                   | On click — “Demo mode...” warning + redirect, data is not saved |
| **Site and plugin configuration** | Can open and view                        | Saving is blocked                    |
| **Group rights**              | Can open                                | Changes are blocked                  |
| **Install/uninstall extensions** | Sees the list                            | Actions are blocked                  |
| **User Demo Admin plugin itself** | Can view demo user list                    | Creating new demo users and changing rights — only by a real administrator |

**Important:** the demo user **is not** a super administrator. They simply have the right to enter the admin panel and read.

### 3.2. Which Operations Are Forbidden

All operations that change the system state will be blocked:

- Saving any settings (configuration, themes, modules, plugins).
- Adding, editing, deleting users, pages, categories, files, etc.
- Installing, updating, removing extensions.
- Changing access rights.
- Clearing cache, executing SQL queries, and other dangerous actions.

Formally, the interface may contain “Save”, “Update”, “Delete” buttons, etc., but when attempting to click them (i.e., sending a POST request or an action classified as dangerous), protection will trigger.

### 3.3. Behavior When Attempting to Save

When a demo admin clicks the save button, the following happens:

1. The browser sends a request to the server (usually POST with parameters).
2. The plugin's global hook intercepts this request.
3. If the user is identified as a demo admin and the request contains dangerous signs (POST or `a` in the prohibited list), the following actions are performed:
   - `$_POST` and part of `$_REQUEST` are cleared;
   - a warning message is displayed (usually a popup notification);
   - a redirect to the previous page or admin home is performed.
4. The target script (e.g., saving configuration) does not execute, and the data is not changed.

---

## 4. Technical Installation and Removal Guide

### 4.1. Installation

1. Copy the `user_demo_admin` folder to the `plugins/` directory of your site.
2. Log in to the Cotonti administration panel with full rights.
3. Go to **Extensions** → **Plugins**.
4. Find the **User Demo Admin** plugin in the list.
5. Click the **Install** button.

After installation:
- The `cot_user_demo_admin_ensure_group()` function will be executed (if not already called), creating the group and rights.
- The plugin will appear in the admin panel under **Administration** → **User Demo Admin** (or via the link `admin.php?m=other&p=user_demo_admin`).

It is recommended to open the plugin, go to the “Rights” tab, and click “Save rights” — this ensures that rights are recreated for all current structure categories.

### 4.2. Removal

1. In **Extensions** → **Plugins**, find the installed **User Demo Admin** plugin.
2. Click the **Uninstall** button.

During removal, the `uninstall.php` script:
- finds the group by alias;
- moves all users of this group to the **Members** group (ID = 4);
- deletes all user-group links in `cot_groups_users`;
- deletes all group rights from `cot_auth`;
- deletes the group itself from `cot_groups`;
- clears the authorization cache (`cot_auth_clear('all')`).

**Important:** after plugin removal, users who belonged to the demo group lose admin access and become regular members.

Before removal, it is recommended to manually delete or move demo users.

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
- Includes the plugin functions file.
- Calls `cot_user_demo_admin_ensure_group()`, which creates the group and assigns all necessary rights.
- Outputs an error if the group cannot be created.

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
- Updates `user_maingrp` of all users in this group to the standard Members group (4).
- Deletes links in `cot_groups_users`, rights in `cot_auth`, and the group itself.
- Clears the rights cache.

### 4.5. Plugin File Structure

```
plugins/user_demo_admin/
├── user_demo_admin.setup.php          — registration and settings
├── user_demo_admin.global.php         — write protection + stub
├── user_demo_admin.admin.php          — main interface (tools)
├── inc/
│   └── user_demo_admin.functions.php  — all business logic
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

The **User Demo Admin** plugin provides fairly broad access to administrative information. Despite all measures taken, the following risks should be considered:

1. **Protection bypass through direct requests.**  
   Theoretically, knowing Cotonti's structure, a demo user could try to send a non-standard POST request that does not contain prohibited `a` parameters. Protection blocks most scenarios but does not guarantee 100% isolation.

2. **Confidential data leakage.**  
   The demo admin can see user emails, security settings, file paths, the list of installed extensions, and other sensitive information. Do not give such access to strangers.

3. **Performance impact.**  
   A user with admin access can open heavy pages (e.g., the entire user list with many records), which may load the server. Limit the number of demo users if necessary.

4. **Rights changes through the standard interface.**  
   The plugin locks group rights via `auth_rights_lock=254`, but if someone with full access accidentally removes the lock through direct database editing, the demo admin could elevate their rights. Regularly check the integrity of settings.

5. **Incompatibility with some third-party plugins.**  
   Some plugins may save via AJAX or alternative methods not intercepted by the standard `global` hook. In such cases, protection may not work. Test all critical extensions before granting demo access.

6. **The demo user password must be strong.**  
   Do not use `demo`, `123456`, etc. Generate a reliable password.

7. **Do not leave demo users for a long time.**  
   Delete or block such users after the demonstration or testing is complete.

8. **A super administrator can always change the Demo Admin group rights.**  
   Watch to ensure the group rights are not accidentally expanded.

**Recommendations:**
- Use the plugin only for a limited circle of trusted individuals.
- Do not share demo credentials publicly.
- Periodically check that the group and rights have not been changed.
- If unauthorized change attempts are detected, immediately remove the demo user.

---

## 6. Recommendations for Further Modification

The plugin can be extended and adapted for specific tasks. Consider the following directions:

1. **Finer control of prohibited actions.**  
   In `user_demo_admin.global.php`, additional parameters or URL patterns can be added for blocking. For example, deny access to certain sections while leaving view only.

2. **AJAX request support.**  
   For complete protection, also intercept `XMLHttpRequest` (the `X-Requested-With` header) and block POST requests sent via AJAX.

3. **Rights separation by modules.**  
   The “Rights” tab already allows targeted denial of reading individual modules. A function can be added to automatically hide forbidden menu items.

4. **Logging demo admin actions.**  
   It is useful to log all write attempts to track suspicious activity. Add a `cot_log()` call in the blocking part.

5. **Message customization.**  
   Replace the standard warning with a more informative one, e.g., indicating the reason and possible actions.

6. **Integration with other groups.**  
   Multiple demo groups with different rights sets can be created using the same mechanism.

7. **Add an English language file.**  
   For multilingual sites.

8. **Add a “Allowed admin sections” setting.**  
   Instead of full access, implement a whitelist of sections.

9. **Move the `$dangerousActions` list to plugin settings.**  
   So the administrator can easily add dangerous actions without editing code.

10. **Add a hook after demo user creation.**  
    For example, `usersaddsadmin.add.done` or a custom hook for additional actions.

11. **When creating a user, automatically add them to the Members group.**  
    For more predictable frontend behavior (rights inherited from Members + Demo Admin).

12. **Create a “You are in demo mode” banner.**  
    Display a warning in the admin header so the demo user clearly sees the restrictions.

13. **Support for extra fields when creating a user.**  
    If additional user fields are used, their processing should be added.

**Checklist when modifying write protection:**
- Saving configuration.
- Installing/uninstalling plugins.
- Editing rights.
- Working with structure and page.

---

## 7. Conclusions

The **User Demo Admin** plugin solves the task of providing limited access to the Cotonti administration panel with read-only rights. Thanks to the combination of standard rights (R on all objects, A on the admin panel) and additional write operation blocking through a global hook, it provides an acceptable level of security for demonstration purposes.

However, due to Cotonti's architectural features (many admin scripts only check for the A right), it is difficult to fully guarantee the impossibility of writing by standard means. Therefore, the plugin should be used with caution and only for trusted users.

If necessary, the plugin can be easily extended with additional checks, logging, or integration with other systems. The documentation and code structure allow developers to quickly adapt it to their needs.


___


# Руководство по плагину User Demo Admin для Cotonti

**Версия:** 5.0.0  
**Автор:** webitproff  
**Лицензия:** BSD  
**Требования:** Cotonti (актуальная версия), модуль users

---

## Оглавление

1. [Обзор плагина и его назначение](#1-обзор-плагина-и-его-назначение)  
2. [Бизнес-логика плагина](#2-бизнес-логика-плагина)  
   - 2.1. Создание группы «Demo Admin»  
   - 2.2. Назначение прав доступа  
   - 2.3. Защита от операций записи  
   - 2.4. Создание пользователя демо-администратора  
3. [Взаимодействие демо-админа с интерфейсом сайта](#3-взаимодействие-демо-админа-с-интерфейсом-сайта)  
   - 3.1. Что можно просматривать  
   - 3.2. Какие операции запрещены  
   - 3.3. Поведение при попытке сохранения  
4. [Техническая памятка по установке и удалению плагина](#4-техническая-памятка-по-установке-и-удалению-плагина)  
   - 4.1. Установка  
   - 4.2. Удаление  
   - 4.3. Файл установки (install.php)  
   - 4.4. Файл удаления (uninstall.php)  
   - 4.5. Структура файлов плагина  
5. [Предупреждения безопасности](#5-предупреждения-безопасности)  
6. [Рекомендации по дальнейшей модификации](#6-рекомендации-по-дальнейшей-модификации)  
7. [Выводы](#7-выводы)

---

## 1. Обзор плагина и его назначение

Плагин **User Demo Admin** предназначен для создания пользователей с особым уровнем доступа — «демонстрационный администратор» или «администратор только для чтения».

**Основная цель** — предоставить ограниченный доступ в административную панель Cotonti доверенным лицам (аудит, обучение, демонстрация) без риска случайного или намеренного изменения настроек, контента, прав или структуры сайта.

Пользователь из группы **Demo Admin** сможет:

- войти в административную панель;
- просматривать практически все разделы админки, включая настройки, списки пользователей, страницы, модули и плагины;
- видеть интерфейс и данные так, как их видит полноценный администратор.

При этом он **не сможет**:

- сохранять изменения (конфигурация, контент, права);
- удалять или редактировать записи;
- устанавливать/удалять расширения;
- выполнять любые операции, изменяющие состояние системы.

Фактически плагин имитирует режим «только просмотр» для админ-панели, аналогичный демо-режиму в OpenCart или PrestaShop.

**Важно:** плагин не изменяет структуру базы данных Cotonti. Используются только стандартные таблицы: `cot_groups`, `cot_auth`, `cot_groups_users`, `cot_users`.

---

## 2. Бизнес-логика плагина

### 2.1. Создание группы «Demo Admin»

При первом обращении к плагину (или при его установке) функция `cot_user_demo_admin_ensure_group()` проверяет наличие группы с заданным алиасом (по умолчанию `demo_admin`). Если группа не найдена, она создаётся в таблице `cot_groups` со следующими параметрами:

- `grp_name` = «Demo Admin»
- `grp_title` = «Demo Admin»
- `grp_level` = 50 (достаточно высокий, чтобы отображаться как привилегированная группа)
- `grp_alias` = `demo_admin` (настраивается в конфигурации)
- `grp_skiprights` = 0 (права используются)
- `grp_pfs_maxfile` / `grp_pfs_maxtotal` = 0 (загрузка файлов запрещена)

### 2.2. Назначение прав доступа

Плагин использует стандартную систему прав Cotonti, основанную на битовых масках в таблице `cot_auth`. Для группы демо-администратора устанавливаются следующие права:

| Область                  | Код                 | Права (число) | Значение                          |
|--------------------------|---------------------|---------------|-----------------------------------|
| Админ-панель             | `admin`             | 129           | R (чтение) + A (доступ в админку)|
| Пользователи             | `users`             | 1             | R (только чтение)                |
| Сообщения                | `message`           | 1             | R                                 |
| Структура                | `structure`         | 1             | R                                 |
| Все модули               | (каждый)            | 1             | R                                 |
| Все плагины              | `plug` / код        | 1             | R                                 |
| Все категории структуры  | (код модуля) / категория | 1         | R (для каждой категории)         |

Битовые маски:
- **R** = 1 (чтение)
- **W** = 2 (запись)
- **A** = 128 (административный доступ)

Для `admin` ставится 129 = 1+128, что даёт право на вход в админку, но **не даёт** права записи (W) и дополнительных привилегий (1-5).  
Все остальные области получают только R = 1.

**Права на категории структуры.** Плагин выдаёт право R не только на корневые разделы модулей (`auth_option = 'a'`), но и на **каждую категорию структуры** (например, все категории модуля `page`, `forums` и т.д.). Это необходимо, чтобы демо-администратор мог видеть материалы, доступ к которым проверяется через права категорий. Без этого шага пользователь не увидел бы даже публичные страницы и записи.

Для всех прав устанавливается `auth_rights_lock` = 254, что блокирует изменение этих прав через стандартный интерфейс редактирования (биты R, W, A и дополнительные заблокированы). Это предотвращает случайное расширение прав демо-группы самим демо-пользователем.

### 2.3. Защита от операций записи

Поскольку штатная система прав Cotonti не может полностью запретить запись при наличии доступа в админку (многие скрипты проверяют только `cot_auth('admin', 'a', 'A')`), плагин добавляет **дополнительный уровень защиты** через глобальный хук.

В файле `user_demo_admin.global.php` реализована проверка:

- Если текущий пользователь принадлежит к группе Demo Admin **и** находится в админке, то:
  - переменная `Cot::$usr['auth_write']` принудительно устанавливается в `false`;
  - анализируется входящий запрос:
    - если метод `POST` → считается попыткой записи;
    - если параметр `a` (действие) входит в список опасных (`update`, `save`, `add`, `edit`, `delete`, `install`, `uninstall`, `config`, `rights` и др.) → попытка записи;
    - если запрошен раздел `m=config` или `m=rights` → попытка записи.
- При обнаружении попытки записи:
  - очищаются массивы `$_POST` и `$_REQUEST`;
  - выводится предупреждение «Режим демонстрации: изменения не сохраняются...»;
  - выполняется перенаправление на предыдущую страницу (или на главную админки).

Таким образом, любые POST-запросы и действия, которые обычно приводят к изменению данных, блокируются ещё до выполнения основной логики.

### 2.4. Создание пользователя демо-администратора

В административном интерфейсе плагина (вкладка «Создать пользователя») администратор может ввести имя, email и пароль. После валидации данных (проверка длины, формата, уникальности) вызывается стандартная функция `cot_add_user()` с указанием основной группы `$groupId` (ID группы Demo Admin). Созданный пользователь сразу получает все права этой группы.

Письмо активации не отправляется (`$sendemail = false`).

---

## 3. Взаимодействие демо-админа с интерфейсом сайта

### 3.1. Что можно просматривать

После входа в админ-панель демо-администратор увидит стандартное левое меню и сможет открыть следующие разделы:

- **Главная страница админки** (обзорная информация).
- **Настройки** (конфигурация сайта) — все вкладки: основные, безопасность, производительность, темы и т.д. (в режиме просмотра).
- **Пользователи** — список пользователей, поиск, просмотр профилей (без кнопок редактирования).
- **Страницы** (если модуль page установлен) — список страниц, структура категорий, просмотр содержимого.
- **Расширения** — список модулей и плагинов, их настройки (только просмотр, кнопки «Сохранить» будут неактивны или их нажатие будет заблокировано).
- **Структура сайта** — просмотр категорий и параметров.
- **Права доступа** — интерфейс просмотра прав групп (с возможным отображением, но без сохранения).
- **Инструменты**, **Файлы**, **Кэш** и другие разделы, доступные обычному администратору.

Благодаря установленным правам R на все модули и категории, демо-админ не увидит сообщений «Нет доступа» при попытке открыть страницы.

Ниже приведена сводная таблица доступа:

| Зона                          | Что можно                              | Что нельзя / что происходит |
|-------------------------------|----------------------------------------|-----------------------------|
| **Фронтенд**                  | Читать страницы, списки, профили пользователей, категории | Редактировать, удалять, добавлять контент (если нет других групп) |
| **Вход в админку**            | Да                                     | — |
| **Просмотр разделов админки** | Почти все разделы (Configuration, Extensions, Structure, Users, Page и т.д.) | — |
| **Кнопки «Сохранить», «Обновить», «Добавить», «Удалить»** | Видит кнопки и формы                   | При нажатии — предупреждение «Режим демонстрации...» + редирект, данные не сохраняются |
| **Конфигурация сайта и плагинов** | Может открыть и смотреть               | Сохранение блокируется |
| **Права групп**               | Может открыть                          | Изменение блокируется |
| **Установка / удаление расширений** | Видит список                           | Действия блокируются |
| **Собственный плагин User Demo Admin** | Может смотреть список демо-пользователей | Создание новых демо-пользователей и изменение прав — только у настоящего администратора |

**Важно:** демо-пользователь **не является** супер-администратором. Он просто имеет право войти в админку и читать.

### 3.2. Какие операции запрещены

Все операции, которые изменяют состояние системы, будут заблокированы:

- Сохранение любых настроек (конфигурация, темы, модули, плагины).
- Добавление, редактирование, удаление пользователей, страниц, категорий, файлов и т.п.
- Установка, обновление, удаление расширений.
- Изменение прав доступа.
- Очистка кэша, выполнение SQL-запросов и других опасных действий.

Формально интерфейс может содержать кнопки «Сохранить», «Обновить», «Удалить» и т.д., но при попытке их нажать (т.е. отправить POST-запрос или действие, классифицированное как опасное) сработает защита.

### 3.3. Поведение при попытке сохранения

Когда демо-админ нажимает кнопку сохранения, происходит следующее:

1. Браузер отправляет запрос на сервер (обычно POST с параметрами).
2. Глобальный хук плагина перехватывает этот запрос.
3. Если пользователь определён как демо-админ, а запрос содержит опасные признаки (POST или `a` в списке запрещённых), выполняются действия:
   - очищаются `$_POST` и часть `$_REQUEST`;
   - выводится сообщение-предупреждение (обычно всплывающее уведомление);
   - происходит редирект на предыдущую страницу или главную админки.
4. Целевой скрипт (например, сохранение конфигурации) не выполняется, данные не изменяются.

---

## 4. Техническая памятка по установке и удалению плагина

### 4.1. Установка

1. Скопируйте папку `user_demo_admin` в директорию `plugins/` вашего сайта.
2. Войдите в административную панель Cotonti под учётной записью с полными правами.
3. Перейдите в раздел **Расширения** → **Плагины**.
4. Найдите плагин **User Demo Admin** в списке доступных.
5. Нажмите кнопку **Установить**.

После установки:
- Будет выполнена функция `cot_user_demo_admin_ensure_group()` (если она не была вызвана раньше), создающая группу и права.
- Плагин появится в админ-панели в разделе **Администрирование** → **User Demo Admin** (или через ссылку `admin.php?m=other&p=user_demo_admin`).

Рекомендуется после установки зайти в плагин, открыть вкладку «Права» и нажать «Сохранить права» — это гарантирует пересоздание прав на все текущие категории структуры.

### 4.2. Удаление

1. В разделе **Расширения** → **Плагины** найдите установленный плагин **User Demo Admin**.
2. Нажмите кнопку **Удалить**.

Во время удаления выполняется скрипт `uninstall.php`, который:
- находит группу по алиасу;
- переводит всех пользователей этой группы в основную группу **Members** (ID = 4);
- удаляет все связи пользователей с группой в таблице `cot_groups_users`;
- удаляет все права группы из таблицы `cot_auth`;
- удаляет саму группу из `cot_groups`;
- очищает кэш авторизации (`cot_auth_clear('all')`).

**Важно:** после удаления плагина пользователи, относившиеся к демо-группе, теряют доступ в админку и становятся обычными участниками.

Перед удалением рекомендуется вручную удалить или перевести демо-пользователей.

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
- Подключает файл функций плагина.
- Вызывает `cot_user_demo_admin_ensure_group()`, которая создаёт группу и назначает все необходимые права.
- Если группа не может быть создана, выводит сообщение об ошибке.

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
- Обновляет `user_maingrp` всех пользователей этой группы на стандартную группу Members (4).
- Удаляет связи в `cot_groups_users`, права в `cot_auth` и саму группу.
- Сбрасывает кэш прав.

### 4.5. Структура файлов плагина

```
plugins/user_demo_admin/
├── user_demo_admin.setup.php          — регистрация и настройки
├── user_demo_admin.global.php         — защита от записи + stub
├── user_demo_admin.admin.php          — основной интерфейс (tools)
├── inc/
│   └── user_demo_admin.functions.php  — вся бизнес-логика
├── lang/
│   └── user_demo_admin.ru.lang.php    — русский язык
├── tpl/
│   └── user_demo_admin.admin.tpl      — шаблон админки
└── setup/
    ├── user_demo_admin.install.php
    └── user_demo_admin.uninstall.php
```

---

## 5. Предупреждения безопасности

Плагин **User Demo Admin** предоставляет довольно широкий доступ к административной информации. Несмотря на все предпринятые меры, следует учитывать следующие риски:

1. **Обход защиты через прямые запросы.**  
   Теоретически, зная структуру Cotonti, демо-пользователь может попытаться отправить нестандартный POST-запрос, не содержащий запрещённых параметров `a`. Защита блокирует большинство сценариев, но не гарантирует 100% изоляцию.

2. **Утечка конфиденциальных данных.**  
   Демо-админ может видеть email пользователей, настройки безопасности, пути к файлам, список установленных расширений и другую чувствительную информацию. Не давайте такой доступ незнакомым людям.

3. **Влияние на производительность.**  
   Пользователь с доступом в админку может открывать тяжёлые страницы (например, список всех пользователей с большим количеством записей), что может нагружать сервер. При необходимости ограничьте количество демо-пользователей.

4. **Изменение прав через стандартный интерфейс.**  
   Плагин блокирует права группы через `auth_rights_lock=254`, но если кто-то с полным доступом случайно снимет блокировку через прямое редактирование БД, демо-админ может повысить свои права. Регулярно проверяйте целостность настроек.

5. **Несовместимость с некоторыми сторонними плагинами.**  
   Некоторые плагины могут выполнять сохранение через AJAX или альтернативные методы, не перехватываемые стандартным хуком `global`. В таких случаях защита может не сработать. Перед предоставлением демо-доступа протестируйте все критически важные расширения.

6. **Пароль демо-пользователя должен быть сложным.**  
   Не используйте `demo`, `123456` и т.п. Рекомендуется генерировать надёжный пароль.

7. **Не оставляйте демо-пользователей надолго.**  
   После завершения демонстрации или тестирования удалите или заблокируйте таких пользователей.

8. **Супер-администратор всегда может изменить права группы Demo Admin.**  
   Следите за тем, чтобы права группы не были случайно расширены.

**Рекомендации:**
- Используйте плагин только для ограниченного круга доверенных лиц.
- Не сообщайте демо-учётные данные публично.
- Периодически проверяйте, что группа и права не были изменены.
- При обнаружении попыток несанкционированного изменения – немедленно удалите демо-пользователя.

---

## 6. Рекомендации по дальнейшей модификации

Плагин может быть расширен и адаптирован под конкретные задачи. Рассмотрим возможные направления:

1. **Более тонкая настройка запрещённых действий.**  
   В файле `user_demo_admin.global.php` можно добавить дополнительные параметры или URL-шаблоны для блокировки. Например, запретить доступ к определённым разделам, оставив только просмотр.

2. **Поддержка AJAX-запросов.**  
   Для полной защиты следует перехватывать также `XMLHttpRequest` (заголовок `X-Requested-With`) и блокировать POST-запросы, отправленные через AJAX.

3. **Разделение прав по модулям.**  
   Через вкладку «Права» уже можно точечно запретить чтение отдельных модулей. Можно добавить функцию автоматического скрытия запрещённых пунктов меню.

4. **Логирование действий демо-админа.**  
   Полезно записывать в лог все попытки записи, чтобы отслеживать подозрительную активность. Для этого в блокирующей части можно добавить вызов `cot_log()`.

5. **Кастомизация сообщений.**  
   Заменить стандартное предупреждение на более информативное, например, с указанием причины и возможных действий.

6. **Интеграция с другими группами.**  
   Можно создать несколько демо-групп с разными наборами прав, используя тот же механизм.

7. **Добавить английский языковой файл**  
   Для мультиязычных сайтов.

8. **Добавить настройку «Разрешённые разделы админки»**  
   Вместо полного доступа можно реализовать whitelist разделов.

9. **Вынести список `$dangerousActions` в настройки плагина**  
   Чтобы администратор мог легко дополнять список опасных действий без правки кода.

10. **Добавить хук после создания демо-пользователя**  
   Например, `usersaddsadmin.add.done` или собственный хук для выполнения дополнительных действий.

11. **При создании пользователя автоматически добавлять его в группу Members**  
    Для более предсказуемого поведения на фронтенде (права наследуются от Members + Demo Admin).

12. **Сделать баннер «Вы находитесь в режиме демонстрации»**  
    Отображать предупреждение в шапке админки, чтобы демо-пользователь явно видел ограничения.

13. **Поддержка экстраполей при создании пользователя**  
    Если используются дополнительные поля пользователей, стоит добавить их обработку.

**Чек-лист при доработке защиты от записи:**
- Сохранение конфигурации.
- Установка/удаление плагинов.
- Редактирование прав.
- Работа с structure и page.

---

## 7. Выводы

Плагин **User Demo Admin** решает задачу предоставления ограниченного доступа к административной панели Cotonti с правами только на чтение. Благодаря комбинации стандартных прав (R на все объекты, A на админку) и дополнительной блокировки операций записи через глобальный хук, он обеспечивает приемлемый уровень безопасности для демонстрационных целей.

Тем не менее, из-за архитектурных особенностей Cotonti (многие админские скрипты проверяют лишь наличие права A) полностью гарантировать невозможность записи штатными средствами сложно. Поэтому плагин следует использовать с осторожностью и только для доверенных пользователей.

При необходимости плагин легко расширить, добавив дополнительные проверки, логирование или интеграцию с другими системами. Документация и структура кода позволяют разработчику быстро адаптировать его под свои нужды.

