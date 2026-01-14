/* ===========================================
   Seed: Role Types & Roles (Company 1)
   Safe to re-run (INSERT IGNORE + unique keys)
   =========================================== */
START TRANSACTION;

SET @now := NOW();
SET @uid := 1;    -- Super Admin user id
SET @cid := 1;    -- Trimatric Global (company id)

/* ---------- Role Types ---------- */
INSERT IGNORE INTO role_types (name, description, created_by, updated_by, created_at, updated_at) VALUES
('Head Office',        'Head Office leadership & HR functions', @uid, @uid, @now, @now),
('Business Officers',  'Division/District/Cluster management',  @uid, @uid, @now, @now),
('Client',             'End customers / clients',               @uid, @uid, @now, @now),
('Professional',       'Independent professionals',             @uid, @uid, @now, @now);

/* ---------- Roles under Head Office ---------- */
INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'CEO', 'Chief Executive Officer', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Head Office';

INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'Head Office Admin', 'HR/Approvals & role assignments', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Head Office';

INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'Head Office Project Manager', 'PM overseeing all divisions', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Head Office';

/* ---------- Roles under Business Officers ---------- */
INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'Division Admin', 'Admin for a single division', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Business Officers';

INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'District Admin', 'Admin for a single district', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Business Officers';

INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'Cluster Admin', 'Admin for a single cluster', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Business Officers';

INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'Cluster Member', 'Member working inside a cluster', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Business Officers';

/* ---------- Roles under Client ---------- */
INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'Client', 'Registered client', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Client';

INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'Guest', 'Unregistered visitor', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Client';

/* ---------- Roles under Professional ---------- */
INSERT IGNORE INTO roles (company_id, role_type_id, name, description, created_by, updated_by, created_at, updated_at)
SELECT @cid, rt.id, 'Professional', 'Independent professional', @uid, @uid, @now, @now
FROM role_types rt WHERE rt.name='Professional';

COMMIT;
