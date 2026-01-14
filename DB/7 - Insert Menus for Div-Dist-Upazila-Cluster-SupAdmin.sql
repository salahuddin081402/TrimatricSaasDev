START TRANSACTION;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) MENUS: children under Global Setup (parent_id = 1)
INSERT INTO menus
(id, parent_id, name,     uri,                                          icon,                          menu_order, description,                      created_by, updated_by, created_at, updated_at)
VALUES
(14, 1, 'Division', 'superadmin.globalsetup.divisions.index',  'fa-solid fa-map',               3, 'Divisions module (index)',  1, 1, NOW(), NOW()),
(15, 1, 'District', 'superadmin.globalsetup.districts.index',  'fa-solid fa-map-location-dot',  4, 'Districts module (index)',  1, 1, NOW(), NOW()),
(16, 1, 'Upazila',  'superadmin.globalsetup.upazilas.index',   'fa-solid fa-location-dot',      5, 'Upazilas module (index)',   1, 1, NOW(), NOW()),
(17, 1, 'Cluster',  'superadmin.globalsetup.clusters.index',   'fa-solid fa-object-group',      6, 'Clusters module (index)',   1, 1, NOW(), NOW());

-- 2) ROLE → MENUS
INSERT INTO role_menu_mappings (role_id, menu_id, access_type, created_by, updated_by, created_at, updated_at) VALUES
(1, 14, 'all', 1, 1, NOW(), NOW()),
(1, 15, 'all', 1, 1, NOW(), NOW()),
(1, 16, 'all', 1, 1, NOW(), NOW()),
(1, 17, 'all', 1, 1, NOW(), NOW());

-- 3) MENU_ACTIONS
-- Division (14)
INSERT INTO menu_actions (menu_id, action_id, button_label, button_icon,           button_order, created_by, updated_by, created_at, updated_at) VALUES
(14, 1, 'View',   'fa-solid fa-eye',   1, 1, 1, NOW(), NOW()),
(14, 2, 'Create', 'fa-solid fa-plus',  2, 1, 1, NOW(), NOW()),
(14, 3, 'Edit',   'fa-solid fa-pen',   3, 1, 1, NOW(), NOW()),
(14, 4, 'Delete', 'fa-solid fa-trash', 4, 1, 1, NOW(), NOW());

-- District (15)
INSERT INTO menu_actions (menu_id, action_id, button_label, button_icon,           button_order, created_by, updated_by, created_at, updated_at) VALUES
(15, 1, 'View',   'fa-solid fa-eye',   1, 1, 1, NOW(), NOW()),
(15, 2, 'Create', 'fa-solid fa-plus',  2, 1, 1, NOW(), NOW()),
(15, 3, 'Edit',   'fa-solid fa-pen',   3, 1, 1, NOW(), NOW()),
(15, 4, 'Delete', 'fa-solid fa-trash', 4, 1, 1, NOW(), NOW());

-- Upazila (16)
INSERT INTO menu_actions (menu_id, action_id, button_label, button_icon,           button_order, created_by, updated_by, created_at, updated_at) VALUES
(16, 1, 'View',   'fa-solid fa-eye',   1, 1, 1, NOW(), NOW()),
(16, 2, 'Create', 'fa-solid fa-plus',  2, 1, 1, NOW(), NOW()),
(16, 3, 'Edit',   'fa-solid fa-pen',   3, 1, 1, NOW(), NOW()),
(16, 4, 'Delete', 'fa-solid fa-trash', 4, 1, 1, NOW(), NOW());

-- Cluster (17)
INSERT INTO menu_actions (menu_id, action_id, button_label, button_icon,           button_order, created_by, updated_by, created_at, updated_at) VALUES
(17, 1, 'View',   'fa-solid fa-eye',   1, 1, 1, NOW(), NOW()),
(17, 2, 'Create', 'fa-solid fa-plus',  2, 1, 1, NOW(), NOW()),
(17, 3, 'Edit',   'fa-solid fa-pen',   3, 1, 1, NOW(), NOW()),
(17, 4, 'Delete', 'fa-solid fa-trash', 4, 1, 1, NOW(), NOW());

-- 4) ROLE → MENU → ACTION PERMISSIONS
-- Division (14)
INSERT INTO role_menu_action_permissions (role_id, menu_id, action_id, allowed, created_by, updated_by, created_at, updated_at) VALUES
(1, 14, 1, 1, 1, 1, NOW(), NOW()),
(1, 14, 2, 1, 1, 1, NOW(), NOW()),
(1, 14, 3, 1, 1, 1, NOW(), NOW()),
(1, 14, 4, 1, 1, 1, NOW(), NOW());

-- District (15)
INSERT INTO role_menu_action_permissions (role_id, menu_id, action_id, allowed, created_by, updated_by, created_at, updated_at) VALUES
(1, 15, 1, 1, 1, 1, NOW(), NOW()),
(1, 15, 2, 1, 1, 1, NOW(), NOW()),
(1, 15, 3, 1, 1, 1, NOW(), NOW()),
(1, 15, 4, 1, 1, 1, NOW(), NOW());

-- Upazila (16)
INSERT INTO role_menu_action_permissions (role_id, menu_id, action_id, allowed, created_by, updated_by, created_at, updated_at) VALUES
(1, 16, 1, 1, 1, 1, NOW(), NOW()),
(1, 16, 2, 1, 1, 1, NOW(), NOW()),
(1, 16, 3, 1, 1, 1, NOW(), NOW()),
(1, 16, 4, 1, 1, 1, NOW(), NOW());

-- Cluster (17)
INSERT INTO role_menu_action_permissions (role_id, menu_id, action_id, allowed, created_by, updated_by, created_at, updated_at) VALUES
(1, 17, 1, 1, 1, 1, NOW(), NOW()),
(1, 17, 2, 1, 1, 1, NOW(), NOW()),
(1, 17, 3, 1, 1, 1, NOW(), NOW()),
(1, 17, 4, 1, 1, 1, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
