/* ========================================================================
   TRIMATRIC SAAS — JURISDICTION TEST DATA (Dhaka → first district → cluster)
   Purpose: Create/ensure a small, consistent lane of data so you can test
            filterbar + jurisdiction for roles: CEO → HO Admin → HOPM →
            Division Admin → District Admin → Cluster Admin → 3x Cluster Members

   Safe to re-run: uses lookups/variables; avoids duplicate inserts where possible
   ======================================================================== */

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET @now := NOW();

/* ------------------------------------------------------------------------
   Block 0 — Constants & helpers
   ------------------------------------------------------------------------ */
SET @company_id := 1;  -- Trimatric Global tenant
/* Classic Laravel 'password' hash so you can login later if you enable auth */
SET @pwd := '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

/* Role name → id (fetched from your roles table) */
SELECT id INTO @role_super_admin     FROM roles WHERE name='Super Admin' LIMIT 1;
SELECT id INTO @role_ceo             FROM roles WHERE name='CEO' LIMIT 1;
SELECT id INTO @role_ho_admin        FROM roles WHERE name='Head Office Admin' LIMIT 1;
SELECT id INTO @role_hopm            FROM roles WHERE name='Head Office Project Manager' LIMIT 1;
SELECT id INTO @role_div_admin       FROM roles WHERE name='Division Admin' LIMIT 1;
SELECT id INTO @role_dist_admin      FROM roles WHERE name='District Admin' LIMIT 1;
SELECT id INTO @role_cluster_admin   FROM roles WHERE name='Cluster Admin' LIMIT 1;
SELECT id INTO @role_cluster_member  FROM roles WHERE name='Cluster Member' LIMIT 1;
SELECT id INTO @role_client          FROM roles WHERE name='Client' LIMIT 1;
SELECT id INTO @role_professional    FROM roles WHERE name='Professional' LIMIT 1;

/* Hard-stop if any role is missing (optional but helpful) */
SELECT
  IF(@role_super_admin     IS NULL, 'MISSING: Super Admin', NULL),
  IF(@role_ceo             IS NULL, 'MISSING: CEO', NULL),
  IF(@role_ho_admin        IS NULL, 'MISSING: Head Office Admin', NULL),
  IF(@role_hopm            IS NULL, 'MISSING: Head Office Project Manager', NULL),
  IF(@role_div_admin       IS NULL, 'MISSING: Division Admin', NULL),
  IF(@role_dist_admin      IS NULL, 'MISSING: District Admin', NULL),
  IF(@role_cluster_admin   IS NULL, 'MISSING: Cluster Admin', NULL),
  IF(@role_cluster_member  IS NULL, 'MISSING: Cluster Member', NULL);

/* ------------------------------------------------------------------------
   Block 1 — Resolve GEO anchors (Dhaka division → first district → 3 upazilas)
   ------------------------------------------------------------------------ */
SELECT id INTO @div_dhaka
FROM divisions
WHERE name='Dhaka' OR short_code='DHK'
LIMIT 1;

-- "First district of Dhaka" = lowest id within that division
SELECT id, short_code, name
INTO @dist1_id, @dist1_code, @dist1_name
FROM districts
WHERE division_id=@div_dhaka
ORDER BY id ASC
LIMIT 1;

-- First three upazilas in that district
SELECT id INTO @upa1 FROM upazilas WHERE district_id=@dist1_id ORDER BY id ASC LIMIT 0,1;
SELECT id INTO @upa2 FROM upazilas WHERE district_id=@dist1_id ORDER BY id ASC LIMIT 1,1;
SELECT id INTO @upa3 FROM upazilas WHERE district_id=@dist1_id ORDER BY id ASC LIMIT 2,1;

/* ------------------------------------------------------------------------
   Block 2 — Ensure a test Cluster under that district (create if missing)
   ------------------------------------------------------------------------ */
-- Try to find any existing cluster for this (company, district)
SELECT id INTO @cluster_id
FROM cluster_masters
WHERE company_id=@company_id AND district_id=@dist1_id
ORDER BY id ASC
LIMIT 1;

-- If none, create one (cluster_no & short_code usually auto via trigger)
INSERT INTO cluster_masters (company_id, district_id, cluster_no, short_code, cluster_name, status, created_at)
SELECT @company_id, @dist1_id, 1, CONCAT(@dist1_code, '01'), CONCAT(@dist1_name, ' Cluster 01'), 1, @now
WHERE @cluster_id IS NULL;

-- Re-fetch cluster id to be sure
SELECT id INTO @cluster_id
FROM cluster_masters
WHERE company_id=@company_id AND district_id=@dist1_id
ORDER BY id ASC
LIMIT 1;

/* Map first 3 upazilas to this cluster (skip if already mapped) */
INSERT INTO cluster_upazila_mappings (company_id, cluster_id, upazila_id, status, created_at)
SELECT @company_id, @cluster_id, @upa1, 1, @now
WHERE @upa1 IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM cluster_upazila_mappings
    WHERE company_id=@company_id AND upazila_id=@upa1
  );

INSERT INTO cluster_upazila_mappings (company_id, cluster_id, upazila_id, status, created_at)
SELECT @company_id, @cluster_id, @upa2, 1, @now
WHERE @upa2 IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM cluster_upazila_mappings
    WHERE company_id=@company_id AND upazila_id=@upa2
  );

INSERT INTO cluster_upazila_mappings (company_id, cluster_id, upazila_id, status, created_at)
SELECT @company_id, @cluster_id, @upa3, 1, @now
WHERE @upa3 IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM cluster_upazila_mappings
    WHERE company_id=@company_id AND upazila_id=@upa3
  );

/* ------------------------------------------------------------------------
   Block 3 — Create USERS (CEO, HO Admin, HOPM, DivAdmin, DistAdmin, ClAdmin, 3x Members)
   (unique emails/phones; status=1)
   ------------------------------------------------------------------------ */
-- CEO
INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_ceo, 'CEO Test', 'ceo.test@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='ceo.test@example.com');
SELECT id INTO @u_ceo FROM users WHERE email='ceo.test@example.com' LIMIT 1;

-- Head Office Admin
INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_ho_admin, 'HO Admin Test', 'ho.admin.test@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='ho.admin.test@example.com');
SELECT id INTO @u_ho_admin FROM users WHERE email='ho.admin.test@example.com' LIMIT 1;

-- Head Office Project Manager
INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_hopm, 'HOPM Test', 'hopm.test@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='hopm.test@example.com');
SELECT id INTO @u_hopm FROM users WHERE email='hopm.test@example.com' LIMIT 1;

-- Division Admin (Dhaka)
INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_div_admin, 'Division Admin Dhaka', 'div.admin.dhk@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='div.admin.dhk@example.com');
SELECT id INTO @u_div_admin FROM users WHERE email='div.admin.dhk@example.com' LIMIT 1;

-- District Admin (first district of Dhaka)
INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_dist_admin, 'District Admin D1', 'dist.admin.dhk1@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='dist.admin.dhk1@example.com');
SELECT id INTO @u_dist_admin FROM users WHERE email='dist.admin.dhk1@example.com' LIMIT 1;

-- Cluster Admin (will be supervisor of @cluster_id)
INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_cluster_admin, 'Cluster Admin C1', 'cl.admin.c1@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='cl.admin.c1@example.com');
SELECT id INTO @u_cluster_admin FROM users WHERE email='cl.admin.c1@example.com' LIMIT 1;

-- 3x Cluster Members
INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_cluster_member, 'Cluster Member A', 'cm.a@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='cm.a@example.com');
SELECT id INTO @u_cmem_a FROM users WHERE email='cm.a@example.com' LIMIT 1;

INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_cluster_member, 'Cluster Member B', 'cm.b@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='cm.b@example.com');
SELECT id INTO @u_cmem_b FROM users WHERE email='cm.b@example.com' LIMIT 1;

INSERT INTO users (company_id, role_id, name, email, password, status, created_at)
SELECT @company_id, @role_cluster_member, 'Cluster Member C', 'cm.c@example.com', @pwd, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='cm.c@example.com');
SELECT id INTO @u_cmem_c FROM users WHERE email='cm.c@example.com' LIMIT 1;

/* ------------------------------------------------------------------------
   Block 4 — APPROVED registrations for all officers (per registration_master schema)
   (company_officer; approval_status='approved'; status=1)
   We’ll pin members to different upazilas from the same cluster for realism.
   ------------------------------------------------------------------------ */
-- CEO
INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_ceo,'company_officer','CEO Test','male',NULL,'+8801701000001','ceo.test@example.com',
 @div_dhaka,@dist1_id,COALESCE(@upa1,@upa2,@upa3),'P','Head Office','CEO seed',
 'approved',@u_ho_admin,@now,1, @u_ho_admin,@u_ho_admin,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_ceo);

-- HO Admin
INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_ho_admin,'company_officer','HO Admin Test','male',NULL,'+8801701000002','ho.admin.test@example.com',
 @div_dhaka,@dist1_id,COALESCE(@upa2,@upa1,@upa3),'P','Head Office','HO Admin seed',
 'approved',@u_ceo,@now,1, @u_ceo,@u_ceo,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_ho_admin);

-- HOPM
INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_hopm,'company_officer','HOPM Test','male',NULL,'+8801701000003','hopm.test@example.com',
 @div_dhaka,@dist1_id,COALESCE(@upa3,@upa2,@upa1),'P','Head Office','HOPM seed',
 'approved',@u_ho_admin,@now,1, @u_ho_admin,@u_ho_admin,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_hopm);

-- Division Admin (Dhaka)
INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_div_admin,'company_officer','Division Admin Dhaka','male',NULL,'+8801701000004','div.admin.dhk@example.com',
 @div_dhaka,@dist1_id,COALESCE(@upa1,@upa2,@upa3),'P','Dhaka Division','Div Admin seed',
 'approved',@u_hopm,@now,1, @u_hopm,@u_hopm,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_div_admin);

-- District Admin (district #1)
INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_dist_admin,'company_officer','District Admin D1','male',NULL,'+8801701000005','dist.admin.dhk1@example.com',
 @div_dhaka,@dist1_id,COALESCE(@upa2,@upa1,@upa3),'P','Dhaka District 1','Dist Admin seed',
 'approved',@u_div_admin,@now,1, @u_div_admin,@u_div_admin,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_dist_admin);

-- Cluster Admin (as supervisor of @cluster_id)
INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_cluster_admin,'company_officer','Cluster Admin C1','male',NULL,'+8801701000006','cl.admin.c1@example.com',
 @div_dhaka,@dist1_id,COALESCE(@upa3,@upa2,@upa1),'P','Cluster HQ','Cluster Admin seed',
 'approved',@u_dist_admin,@now,1, @u_dist_admin,@u_dist_admin,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_cluster_admin);

/* 3x Cluster Members */
INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_cmem_a,'company_officer','Cluster Member A','male',NULL,'+8801701000007','cm.a@example.com',
 @div_dhaka,@dist1_id,@upa1,'P','Cluster Area','Member A seed',
 'approved',@u_cluster_admin,@now,1, @u_cluster_admin,@u_cluster_admin,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_cmem_a);

INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_cmem_b,'company_officer','Cluster Member B','male',NULL,'+8801701000008','cm.b@example.com',
 @div_dhaka,@dist1_id,@upa2,'P','Cluster Area','Member B seed',
 'approved',@u_cluster_admin,@now,1, @u_cluster_admin,@u_cluster_admin,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_cmem_b);

INSERT INTO registration_master
(company_id,user_id,registration_type,full_name,gender,date_of_birth,phone,email,
 division_id,district_id,upazila_id,person_type,present_address,notes,
 approval_status,approved_by,approved_at,status,created_by,updated_by,created_at,updated_at)
SELECT
 @company_id,@u_cmem_c,'company_officer','Cluster Member C','male',NULL,'+8801701000009','cm.c@example.com',
 @div_dhaka,@dist1_id,@upa3,'P','Cluster Area','Member C seed',
 'approved',@u_cluster_admin,@now,1, @u_cluster_admin,@u_cluster_admin,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@u_cmem_c);

/* ------------------------------------------------------------------------
   Block 5 — Area admin mappings + cluster supervisor + members
   (enforces one active per area via your triggers/constraints)
   ------------------------------------------------------------------------ */
-- Division Admin mapping (Dhaka)
INSERT INTO company_division_admins
(company_id, division_id, user_id, status, activated_at, created_at)
SELECT @company_id, @div_dhaka, @u_div_admin, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM company_division_admins
  WHERE company_id=@company_id AND division_id=@div_dhaka AND user_id=@u_div_admin
);

-- District Admin mapping (first district)
INSERT INTO company_district_admins
(company_id, district_id, user_id, status, activated_at, created_at)
SELECT @company_id, @dist1_id, @u_dist_admin, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM company_district_admins
  WHERE company_id=@company_id AND district_id=@dist1_id AND user_id=@u_dist_admin
);

-- Make this user the Cluster Supervisor
UPDATE cluster_masters
SET cluster_supervisor_id = @u_cluster_admin, updated_at=@now
WHERE id=@cluster_id AND (cluster_supervisor_id IS NULL OR cluster_supervisor_id <> @u_cluster_admin);

-- Cluster members (one cluster per user per company enforced by unique)
INSERT INTO cluster_members (company_id, cluster_id, user_id, status, created_at)
SELECT @company_id, @cluster_id, @u_cmem_a, 1, @now
WHERE NOT EXISTS (
  SELECT 1 FROM cluster_members
  WHERE company_id=@company_id AND user_id=@u_cmem_a
);

INSERT INTO cluster_members (company_id, cluster_id, user_id, status, created_at)
SELECT @company_id, @cluster_id, @u_cmem_b, 1, @now
WHERE NOT EXISTS (
  SELECT 1 FROM cluster_members
  WHERE company_id=@company_id AND user_id=@u_cmem_b
);

INSERT INTO cluster_members (company_id, cluster_id, user_id, status, created_at)
SELECT @company_id, @cluster_id, @u_cmem_c, 1, @now
WHERE NOT EXISTS (
  SELECT 1 FROM cluster_members
  WHERE company_id=@company_id AND user_id=@u_cmem_c
);

/* ------------------------------------------------------------------------
   DONE — Quick sanity peek (optional)
   ------------------------------------------------------------------------ */
SELECT 'DIVISION' AS what, @div_dhaka AS id
UNION ALL SELECT 'DISTRICT', @dist1_id
UNION ALL SELECT 'CLUSTER', @cluster_id;

SELECT 'USERS', name, email, role_id FROM users
WHERE id IN (@u_ceo,@u_ho_admin,@u_hopm,@u_div_admin,@u_dist_admin,@u_cluster_admin,@u_cmem_a,@u_cmem_b,@u_cmem_c);

SELECT 'REGS', user_id, approval_status, status FROM registration_master
WHERE user_id IN (@u_ceo,@u_ho_admin,@u_hopm,@u_div_admin,@u_dist_admin,@u_cluster_admin,@u_cmem_a,@u_cmem_b,@u_cmem_c);

SELECT 'DIV_ADMIN', * FROM company_division_admins
WHERE company_id=@company_id AND division_id=@div_dhaka;

SELECT 'DIST_ADMIN', * FROM company_district_admins
WHERE company_id=@company_id AND district_id=@dist1_id;

SELECT 'CLUSTER_SUP', id, cluster_supervisor_id FROM cluster_masters WHERE id=@cluster_id;

SELECT 'MEMBERS', user_id FROM cluster_members WHERE company_id=@company_id AND cluster_id=@cluster_id;
