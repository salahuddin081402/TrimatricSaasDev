-- =====================================================================
-- TrimatricSaas — Super Admin Dataset (Reset + Seed, Index-only Actions)
-- - Deletes with FK safety (no TRUNCATE)
-- - Seeds GlobalSetup & UserManagement modules
-- - Actions only on INDEX menus: view, create, edit, delete
-- - Super Admin (user_id=1, role_id=1) has full permissions
-- =====================================================================

START TRANSACTION;

-- ---------- RESET (child -> parent) ----------
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM role_menu_action_permissions;
DELETE FROM menu_actions;
DELETE FROM role_menu_mappings;

DELETE FROM activity_logs;

DELETE FROM users;
DELETE FROM roles;
DELETE FROM role_types;

DELETE FROM menus;
DELETE FROM actions;

DELETE FROM companies;
DELETE FROM countries;

-- reset auto-increment counters
ALTER TABLE role_menu_action_permissions AUTO_INCREMENT = 1;
ALTER TABLE menu_actions                  AUTO_INCREMENT = 1;
ALTER TABLE role_menu_mappings            AUTO_INCREMENT = 1;

ALTER TABLE activity_logs                 AUTO_INCREMENT = 1;

ALTER TABLE users                         AUTO_INCREMENT = 1;
ALTER TABLE roles                         AUTO_INCREMENT = 1;
ALTER TABLE role_types                    AUTO_INCREMENT = 1;

ALTER TABLE menus                         AUTO_INCREMENT = 1;
ALTER TABLE actions                       AUTO_INCREMENT = 1;

ALTER TABLE companies                     AUTO_INCREMENT = 1;
ALTER TABLE countries                     AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------- VARS ----------
SET @now := NOW();
SET @dhaka_now := @now;  -- later app code will compute true local+Dhaka times
SET @user_id := 1;

-- =====================================================================
-- CORE ENTITIES (FK order)
-- =====================================================================

-- 1) Country
INSERT INTO countries (id, name, short_code, created_by, updated_by, created_at, updated_at)
VALUES (1, 'Bangladesh', 'BD', @user_id, @user_id, @now, @now);

-- 2) Company (Tenant)
INSERT INTO companies (id, country_id, name, description, address, contact_no, logo, created_by, updated_by, created_at, updated_at)
VALUES (1, 1, 'Trimatric Global', 'Primary tenant for Super Admin phase', 'Dhaka, Bangladesh', '+8801XXXXXXXXX', NULL, @user_id, @user_id, @now, @now);

INSERT INTO `companies` (`id`, `country_id`, `name`, `slug`, `description`, `address`, `contact_no`, `logo`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Trimatric Global', 'trimatric-global', 'Primary tenant for Super Admin phase', 'Dhaka, Bangladesh', '+8801XXXXXXXXX', 'assets/images/bangladesh/trimatric-global/logo/trimatric-global-logo-1756278501.png', 1, 1, 1, '2025-08-11 13:13:45', '2025-08-27 01:08:21', NULL),
(2, 1, 'ABC Limited', 'abc-limited', NULL, NULL, NULL, 'assets/images/bangladesh/abc-limited/logo/abc-limited-logo-1756270821.png', 1, 1, 1, '2025-08-26 23:00:21', '2025-08-26 23:00:21', NULL),
(3, 1, 'ARC House Limited', 'arc-house-limited', 'Architecture & Interior Design Farm', 'Dhanmondi', '+8801804753698', 'assets/images/bangladesh/arc-house-limited/logo/arc-house-limited-logo-1756278827.png', 1, 1, 1, '2025-08-27 01:13:47', '2025-08-27 01:14:24', NULL);

-- 3) Role Type
INSERT INTO role_types (id, name, description, created_by, updated_by, created_at, updated_at)
VALUES (1, 'Super Admin', 'Global Super Admin role type', @user_id, @user_id, @now, @now);

-- 4) Role
INSERT INTO roles (id, company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
VALUES (1, 1, 1, 'Super Admin', 'Super Admin for Trimatric Global', @user_id, @user_id, @now, @now);

-- 5) User (Super Admin)
-- Password hash for 'password' — replace later with your own hash
SET @pwd_hash := '$2y$10$wVnIhQ7bqgqfQkq3oF7mAui3pQ7R0Y0W2QeQ0g7M6bqf4e3H.0p8a';
INSERT INTO users (id, company_id, role_id, name, email, password, remember_token, status, created_by, updated_by, created_at, updated_at)
VALUES (1, 1, 1, 'Super Admin', 'admin@example.com', @pwd_hash, NULL, 1, @user_id, @user_id, @now, @now);

-- =====================================================================
-- ACTIONS (index pages only)
-- =====================================================================
INSERT INTO actions (id, name, display_name, description, created_by, updated_by, created_at, updated_at) VALUES
(1, 'view',   'View',   'View list/details on index page', @user_id, @user_id, @now, @now),
(2, 'create', 'Create', 'Open add/create page from index', @user_id, @user_id, @now, @now),
(3, 'edit',   'Edit',   'Open edit page from index',       @user_id, @user_id, @now, @now),
(4, 'delete', 'Delete', 'Delete via AJAX from index',      @user_id, @user_id, @now, @now);

-- =====================================================================
-- MENUS (hierarchy; route NAMES in uri for children)
-- Parents (no URI): Global Setup, User Management
-- Children (index pages): Countries, Companies, Role Types, Roles, Menus, Actions,
--                         Menu Actions, Role–Menu Map, Role–Menu–Action, Activity Logs, Users
-- =====================================================================

-- Parent containers
INSERT INTO menus (id, parent_id, name, uri, icon, menu_order, description, created_by, updated_by, created_at, updated_at) VALUES
(1, NULL, 'Global Setup',    NULL, 'fa-solid fa-sitemap',    1, 'Global configuration',       @user_id, @user_id, @now, @now),
(2, NULL, 'User Management', NULL, 'fa-solid fa-users-gear', 2, 'Users, roles & permissions', @user_id, @user_id, @now, @now);

-- Global Setup children
INSERT INTO menus (id, parent_id, name, uri, icon, menu_order, description, created_by, updated_by, created_at, updated_at) VALUES
(3, 1, 'Countries', 'superadmin.globalsetup.countries.index',  'fa-solid fa-flag',     1, 'Countries module (index)',  @user_id, @user_id, @now, @now),
(4, 1, 'Companies', 'superadmin.globalsetup.companies.index',  'fa-solid fa-building', 2, 'Companies module (index)',  @user_id, @user_id, @now, @now);

-- User Management children
INSERT INTO menus (id, parent_id, name, uri, icon, menu_order, description, created_by, updated_by, created_at, updated_at) VALUES
(5,  2, 'Role Types',                  'superadmin.usermanagement.roletypes.index',                   'fa-solid fa-layer-group',       1, 'Role types (index)',             @user_id, @user_id, @now, @now),
(6,  2, 'Roles',                       'superadmin.usermanagement.roles.index',                       'fa-solid fa-user-shield',       2, 'Roles (index)',                  @user_id, @user_id, @now, @now),
(7,  2, 'Menus',                       'superadmin.usermanagement.menus.index',                       'fa-solid fa-list',              3, 'Menus registry (index)',         @user_id, @user_id, @now, @now),
(8,  2, 'Actions',                     'superadmin.usermanagement.actions.index',                     'fa-solid fa-bolt',              4, 'Actions registry (index)',       @user_id, @user_id, @now, @now),
(9,  2, 'Menu Actions',                'superadmin.usermanagement.menu_actions.index',                'fa-solid fa-icons',             5, 'Actions per menu (index)',       @user_id, @user_id, @now, @now),
(10, 2, 'Role–Menu Map',               'superadmin.usermanagement.role_menu_mappings.index',          'fa-solid fa-link',              6, 'Roles to menus (index)',         @user_id, @user_id, @now, @now),
(11, 2, 'Role–Menu–Action',            'superadmin.usermanagement.role_menu_action_permissions.index','fa-solid fa-lock',              7, 'Action permissions (index)',     @user_id, @user_id, @now, @now),
(12, 2, 'Activity Logs',               'superadmin.usermanagement.activity_logs.index',               'fa-solid fa-clock-rotate-left', 8, 'Audit trail (index)',            @user_id, @user_id, @now, @now),
(13, 2, 'Users',                       'superadmin.usermanagement.users.index',                       'fa-solid fa-user',              9, 'Users (index)',                  @user_id, @user_id, @now, @now);

-- =====================================================================
-- MENU_ACTIONS (index-page buttons only)
-- For Activity Logs (12), we attach only 'view'
-- =====================================================================

-- Global Setup: Countries (3)
INSERT INTO menu_actions (menu_id, action_id, button_label, button_icon, button_order, created_by, updated_by, created_at, updated_at) VALUES
(3,1,'View','fa-solid fa-eye',1,@user_id,@user_id,@now,@now),
(3,2,'Create','fa-solid fa-plus',2,@user_id,@user_id,@now,@now),
(3,3,'Edit','fa-solid fa-pen',3,@user_id,@user_id,@now,@now),
(3,4,'Delete','fa-solid fa-trash',4,@user_id,@user_id,@now,@now);

-- Global Setup: Companies (4)
INSERT INTO menu_actions (menu_id, action_id, button_label, button_icon, button_order, created_by, updated_by, created_at, updated_at) VALUES
(4,1,'View','fa-solid fa-eye',1,@user_id,@user_id,@now,@now),
(4,2,'Create','fa-solid fa-plus',2,@user_id,@user_id,@now,@now),
(4,3,'Edit','fa-solid fa-pen',3,@user_id,@user_id,@now,@now),
(4,4,'Delete','fa-solid fa-trash',4,@user_id,@user_id,@now,@now);

-- User Management children (5..11,13): all four index actions
INSERT INTO menu_actions (menu_id, action_id, button_label, button_icon, button_order, created_by, updated_by, created_at, updated_at)
SELECT m.id, a.id,
       CASE a.name
            WHEN 'view'   THEN 'View'
            WHEN 'create' THEN 'Create'
            WHEN 'edit'   THEN 'Edit'
            WHEN 'delete' THEN 'Delete'
       END,
       CASE a.name
            WHEN 'view'   THEN 'fa-solid fa-eye'
            WHEN 'create' THEN 'fa-solid fa-plus'
            WHEN 'edit'   THEN 'fa-solid fa-pen'
            WHEN 'delete' THEN 'fa-solid fa-trash'
       END,
       CASE a.name
            WHEN 'view'   THEN 1
            WHEN 'create' THEN 2
            WHEN 'edit'   THEN 3
            WHEN 'delete' THEN 4
       END,
       @user_id, @user_id, @now, @now
FROM menus m
JOIN actions a ON a.name IN ('view','create','edit','delete')
WHERE m.id IN (5,6,7,8,9,10,11,13);

-- Activity Logs (12): 'view' only
INSERT INTO menu_actions (menu_id, action_id, button_label, button_icon, button_order, created_by, updated_by, created_at, updated_at)
SELECT 12, a.id, 'View', 'fa-solid fa-eye', 1, @user_id, @user_id, @now, @now
FROM actions a WHERE a.name='view';

-- =====================================================================
-- ROLE → MENUS (Super Admin mapped to all menus, including parents)
-- =====================================================================
INSERT INTO role_menu_mappings (role_id, menu_id, access_type, created_by, updated_by, created_at, updated_at)
SELECT 1, id, 'all', @user_id, @user_id, @now, @now FROM menus;

-- =====================================================================
-- ROLE → MENU → ACTION (Super Admin gets full set on index menus)
-- Parents (1,2) don’t need actions; children get their index actions.
-- Activity Logs menu (12) gets 'view' only.
-- =====================================================================
INSERT INTO role_menu_action_permissions (role_id, menu_id, action_id, allowed, created_by, updated_by, created_at, updated_at)
SELECT 1, m.id, a.id, 1, @user_id, @user_id, @now, @now
FROM menus m
JOIN actions a ON a.name IN ('view','create','edit','delete')
WHERE m.id IN (3,4,5,6,7,8,9,10,11,13);

INSERT INTO role_menu_action_permissions (role_id, menu_id, action_id, allowed, created_by, updated_by, created_at, updated_at)
SELECT 1, 12, a.id, 1, @user_id, @user_id, @now, @now
FROM actions a WHERE a.name='view';

-- =====================================================================
-- ACTIVITY LOGS (after company & user exist)
-- =====================================================================
INSERT INTO activity_logs (company_id, user_id, action, table_name, row_id, details, ip_address, time_local, time_dhaka, created_by, updated_by, created_at, updated_at) VALUES
(1, @user_id, 'add', 'countries', 1, JSON_OBJECT('seed','init','name','Bangladesh','short_code','BD'), '127.0.0.1', @now, @dhaka_now, @user_id, @user_id, @now, @now),
(1, @user_id, 'add', 'companies', 1, JSON_OBJECT('seed','init','name','Trimatric Global'), '127.0.0.1', @now, @dhaka_now, @user_id, @user_id, @now, @now),
(1, @user_id, 'add', 'role_types', 1, JSON_OBJECT('seed','init','name','Super Admin'), '127.0.0.1', @now, @dhaka_now, @user_id, @user_id, @now, @now),
(1, @user_id, 'add', 'roles', 1, JSON_OBJECT('seed','init','name','Super Admin','company_id',1), '127.0.0.1', @now, @dhaka_now, @user_id, @user_id, @now, @now),
(1, @user_id, 'add', 'users', 1, JSON_OBJECT('seed','init','name','Super Admin','email','admin@example.com'), '127.0.0.1', @now, @dhaka_now, @user_id, @user_id, @now, @now);

COMMIT;
