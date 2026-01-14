/* =========================================================
   MULTI-TENANT CLUSTER SCHEMA (company-aware)
   - Safe re-run: drops triggers, then child tables, then parent.
   - Recreates tables with company_id and strong constraints.
   - Adds prudent triggers for numbering, short_code, and company checks.
   ========================================================= */

SET NAMES utf8mb4;
SET time_zone = '+00:00';

/* Drop ONLY cluster-related objects (keeps divisions/districts/upazilas intact) */

-- 1) Drop triggers (safe if absent)
DROP TRIGGER IF EXISTS trg_cluster_masters_bi;
DROP TRIGGER IF EXISTS trg_cluster_masters_bu;
-- legacy names, just in case
DROP TRIGGER IF EXISTS trg_clusters_bi;
DROP TRIGGER IF EXISTS trg_clusters_bu;

DROP TRIGGER IF EXISTS trg_cluster_upazila_mappings_bi;
DROP TRIGGER IF EXISTS trg_cluster_upazila_mappings_bu;

DROP TRIGGER IF EXISTS trg_cluster_members_bi;
DROP TRIGGER IF EXISTS trg_cluster_members_bu;

-- 2) Drop tables (children → parent)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS cluster_upazila_mappings;
DROP TABLE IF EXISTS cluster_members;
DROP TABLE IF EXISTS cluster_masters;
SET FOREIGN_KEY_CHECKS = 1;

-- 3) (Optional) Verify they’re gone
-- SHOW TABLES LIKE 'cluster_%';
-- SHOW TRIGGERS LIKE 'cluster_%';

/* ==================== 2) CREATE TABLES ==================== */

/* ---------- cluster_masters (parent) ----------
   - company_id: tenant boundary
   - cluster_no: auto 01..99 per (company_id, district_id)
   - short_code: DIST(5) + cluster_no(2) (unique within company)
*/
CREATE TABLE cluster_masters (
  id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Cluster ID',
  company_id             BIGINT UNSIGNED NOT NULL COMMENT 'FK → companies.id',
  district_id            BIGINT UNSIGNED NOT NULL COMMENT 'FK → districts.id',
  cluster_no             TINYINT  UNSIGNED NOT NULL COMMENT '01..99 per (company_id, district_id)',
  short_code             CHAR(7)  NOT NULL COMMENT 'DIST(5) + cluster_no(2), e.g. DHK0101 (unique per company)',
  cluster_name           VARCHAR(150) NOT NULL COMMENT 'Cluster name',
  cluster_supervisor_id  BIGINT UNSIGNED NULL COMMENT 'FK → users.id (nullable; must be same company)',
  status                 TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  created_by             BIGINT UNSIGNED NULL,
  updated_by             BIGINT UNSIGNED NULL,
  created_at             TIMESTAMP NULL DEFAULT NULL,
  updated_at             TIMESTAMP NULL DEFAULT NULL,

  CONSTRAINT fk_cm_company    FOREIGN KEY (company_id)  REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_cm_district   FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
  CONSTRAINT fk_cm_supervisor FOREIGN KEY (cluster_supervisor_id) REFERENCES users(id) ON DELETE SET NULL,

  /* Uniques scoped to company */
  UNIQUE KEY uq_cm_name_per_dist   (company_id, district_id, cluster_name),
  UNIQUE KEY uq_cm_no_per_dist     (company_id, district_id, cluster_no),
  UNIQUE KEY uq_cm_code_per_co     (company_id, short_code),

  /* Helpful indexes */
  INDEX idx_cm_company    (company_id),
  INDEX idx_cm_district   (district_id),
  INDEX idx_cm_supervisor (cluster_supervisor_id),
  INDEX idx_cm_status     (status),

  CHECK (cluster_no BETWEEN 1 AND 99)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Company-scoped clusters per district';


/* ---------- cluster_upazila_mappings (child of clusters) ----------
   - one upazila → at most one cluster **per company**
   - enforces cluster.company_id = mapping.company_id
   - enforces upazila.district_id = cluster.district_id
*/
CREATE TABLE cluster_upazila_mappings (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id       BIGINT UNSIGNED NOT NULL COMMENT 'FK → companies.id (must equal cluster’s company)',
  cluster_id       BIGINT UNSIGNED NOT NULL COMMENT 'FK → cluster_masters.id',
  upazila_id       BIGINT UNSIGNED NOT NULL COMMENT 'FK → upazilas.id',
  status           TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  created_by       BIGINT UNSIGNED NULL,
  updated_by       BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,

  CONSTRAINT fk_cum_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_cum_cluster FOREIGN KEY (cluster_id) REFERENCES cluster_masters(id) ON DELETE CASCADE,
  CONSTRAINT fk_cum_upazila FOREIGN KEY (upazila_id) REFERENCES upazilas(id) ON DELETE CASCADE,

  /* Prevent double assignment of same upazila within a company */
  UNIQUE KEY uq_cum_upazila_per_co (company_id, upazila_id),

  /* Also prevent accidental duplicates within the same cluster */
  UNIQUE KEY uq_cum_pair           (cluster_id, upazila_id),

  /* Helpful indexes */
  INDEX idx_cum_company  (company_id),
  INDEX idx_cum_cluster  (cluster_id),
  INDEX idx_cum_upazila  (upazila_id),
  INDEX idx_cum_status   (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cluster–Upazila mapping (company-scoped)';


/* ---------- cluster_members (child of clusters) ----------
   - user may belong to at most one cluster **per company**
   - enforces cluster.company_id = member.company_id = user.company_id
*/
CREATE TABLE cluster_members (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id  BIGINT UNSIGNED NOT NULL COMMENT 'FK → companies.id (must equal cluster’s and user’s company)',
  cluster_id  BIGINT UNSIGNED NOT NULL COMMENT 'FK → cluster_masters.id',
  user_id     BIGINT UNSIGNED NOT NULL COMMENT 'FK → users.id',
  status      TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active,0=Inactive',
  created_by  BIGINT UNSIGNED NULL,
  updated_by  BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,

  CONSTRAINT fk_cmbr_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_cmbr_cluster FOREIGN KEY (cluster_id) REFERENCES cluster_masters(id) ON DELETE CASCADE,
  CONSTRAINT fk_cmbr_user    FOREIGN KEY (user_id)    REFERENCES users(id)          ON DELETE CASCADE,

  /* Enforce “one cluster per user per company” */
  UNIQUE KEY uq_cmbr_user_per_co (company_id, user_id),

  /* Also block duplicate pair rows (defensive) */
  UNIQUE KEY uq_cmbr_pair        (cluster_id, user_id),

  /* Helpful indexes */
  INDEX idx_cmbr_company (company_id),
  INDEX idx_cmbr_cluster (cluster_id),
  INDEX idx_cmbr_user    (user_id),
  INDEX idx_cmbr_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cluster members (company-scoped, single-cluster per user per company)';


/* ==================== 3) TRIGGERS ==================== */
DELIMITER $$

/* ---- cluster_masters: BEFORE INSERT ----
   - Validate district exists, get its short_code
   - Ensure cluster_supervisor (if set) is same company
   - Auto-assign cluster_no per (company_id, district_id) when null/0
   - Build short_code = DIST(5) + LPAD(cluster_no, 2)
*/
CREATE TRIGGER trg_cluster_masters_bi
BEFORE INSERT ON cluster_masters
FOR EACH ROW
BEGIN
  DECLARE v_dist_code CHAR(5);
  DECLARE v_next TINYINT UNSIGNED;
  DECLARE v_user_co BIGINT UNSIGNED;

  /* District short code */
  SELECT short_code INTO v_dist_code
  FROM districts
  WHERE id = NEW.district_id
  LIMIT 1;

  IF v_dist_code IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid district_id for cluster';
  END IF;

  /* Supervisor must belong to same company (if provided) */
  IF NEW.cluster_supervisor_id IS NOT NULL THEN
    SELECT company_id INTO v_user_co FROM users WHERE id = NEW.cluster_supervisor_id LIMIT 1;
    IF v_user_co IS NULL OR v_user_co <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cluster_supervisor must belong to the same company';
    END IF;
  END IF;

  /* Auto-assign next cluster_no per (company_id, district_id) */
  IF NEW.cluster_no IS NULL OR NEW.cluster_no = 0 THEN
    SELECT COALESCE(MAX(cluster_no),0)+1 INTO v_next
    FROM cluster_masters
    WHERE company_id = NEW.company_id
      AND district_id = NEW.district_id;
    SET NEW.cluster_no = v_next;
  END IF;

  IF NEW.cluster_no < 1 OR NEW.cluster_no > 99 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cluster_no must be between 1 and 99';
  END IF;

  /* short_code within company */
  SET NEW.short_code = CONCAT(v_dist_code, LPAD(NEW.cluster_no, 2, '0'));
END$$


/* ---- cluster_masters: BEFORE UPDATE ----
   - Re-check supervisor company
   - Recompute short_code if district_id or cluster_no changed
   - Validate range and company consistency if company_id/district_id changes
*/
CREATE TRIGGER trg_cluster_masters_bu
BEFORE UPDATE ON cluster_masters
FOR EACH ROW
BEGIN
  DECLARE v_dist_code CHAR(5);
  DECLARE v_user_co BIGINT UNSIGNED;

  /* Supervisor must belong to same company (if provided) */
  IF NEW.cluster_supervisor_id IS NOT NULL THEN
    SELECT company_id INTO v_user_co FROM users WHERE id = NEW.cluster_supervisor_id LIMIT 1;
    IF v_user_co IS NULL OR v_user_co <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cluster_supervisor must belong to the same company';
    END IF;
  END IF;

  IF NEW.cluster_no < 1 OR NEW.cluster_no > 99 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cluster_no must be between 1 and 99';
  END IF;

  /* Recompute short_code when district or number changed */
  IF NEW.district_id <> OLD.district_id OR NEW.cluster_no <> OLD.cluster_no THEN
    SELECT short_code INTO v_dist_code
    FROM districts
    WHERE id = NEW.district_id
    LIMIT 1;

    IF v_dist_code IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid district_id for cluster (update)';
    END IF;

    SET NEW.short_code = CONCAT(v_dist_code, LPAD(NEW.cluster_no, 2, '0'));
  END IF;
END$$


/* ---- cluster_upazila_mappings: BEFORE INSERT ----
   - Enforce company match with cluster
   - Enforce upazila’s district = cluster’s district
*/
CREATE TRIGGER trg_cluster_upazila_mappings_bi
BEFORE INSERT ON cluster_upazila_mappings
FOR EACH ROW
BEGIN
  DECLARE v_cluster_co BIGINT UNSIGNED;
  DECLARE v_cluster_dist BIGINT UNSIGNED;
  DECLARE v_upa_dist BIGINT UNSIGNED;

  SELECT company_id, district_id INTO v_cluster_co, v_cluster_dist
  FROM cluster_masters
  WHERE id = NEW.cluster_id
  LIMIT 1;

  IF v_cluster_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid cluster_id in mapping';
  END IF;

  IF v_cluster_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mapping company_id must equal cluster company_id';
  END IF;

  SELECT district_id INTO v_upa_dist
  FROM upazilas
  WHERE id = NEW.upazila_id
  LIMIT 1;

  IF v_upa_dist IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid upazila_id in mapping';
  END IF;

  IF v_upa_dist <> v_cluster_dist THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Upazila must belong to the same district as the cluster';
  END IF;
END$$


/* ---- cluster_upazila_mappings: BEFORE UPDATE ----
   - Same validations as INSERT
*/
CREATE TRIGGER trg_cluster_upazila_mappings_bu
BEFORE UPDATE ON cluster_upazila_mappings
FOR EACH ROW
BEGIN
  DECLARE v_cluster_co BIGINT UNSIGNED;
  DECLARE v_cluster_dist BIGINT UNSIGNED;
  DECLARE v_upa_dist BIGINT UNSIGNED;

  SELECT company_id, district_id INTO v_cluster_co, v_cluster_dist
  FROM cluster_masters
  WHERE id = NEW.cluster_id
  LIMIT 1;

  IF v_cluster_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid cluster_id in mapping (update)';
  END IF;

  IF v_cluster_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mapping company_id must equal cluster company_id (update)';
  END IF;

  SELECT district_id INTO v_upa_dist
  FROM upazilas
  WHERE id = NEW.upazila_id
  LIMIT 1;

  IF v_upa_dist IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid upazila_id in mapping (update)';
  END IF;

  IF v_upa_dist <> v_cluster_dist THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Upazila must belong to the same district as the cluster (update)';
  END IF;
END$$


/* ---- cluster_members: BEFORE INSERT ----
   - Enforce company match with cluster and with user
*/
CREATE TRIGGER trg_cluster_members_bi
BEFORE INSERT ON cluster_members
FOR EACH ROW
BEGIN
  DECLARE v_cluster_co BIGINT UNSIGNED;
  DECLARE v_user_co BIGINT UNSIGNED;

  SELECT company_id INTO v_cluster_co
  FROM cluster_masters
  WHERE id = NEW.cluster_id
  LIMIT 1;

  IF v_cluster_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid cluster_id for member';
  END IF;

  IF v_cluster_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member company_id must equal cluster company_id';
  END IF;

  SELECT company_id INTO v_user_co
  FROM users
  WHERE id = NEW.user_id
  LIMIT 1;

  IF v_user_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid user_id for member';
  END IF;

  IF v_user_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member user must belong to the same company as the cluster';
  END IF;
END$$


/* ---- cluster_members: BEFORE UPDATE ----
   - Same validations as INSERT
*/
CREATE TRIGGER trg_cluster_members_bu
BEFORE UPDATE ON cluster_members
FOR EACH ROW
BEGIN
  DECLARE v_cluster_co BIGINT UNSIGNED;
  DECLARE v_user_co BIGINT UNSIGNED;

  SELECT company_id INTO v_cluster_co
  FROM cluster_masters
  WHERE id = NEW.cluster_id
  LIMIT 1;

  IF v_cluster_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid cluster_id for member (update)';
  END IF;

  IF v_cluster_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member company_id must equal cluster company_id (update)';
  END IF;

  SELECT company_id INTO v_user_co
  FROM users
  WHERE id = NEW.user_id
  LIMIT 1;

  IF v_user_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid user_id for member (update)';
  END IF;

  IF v_user_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member user must belong to the same company as the cluster (update)';
  END IF;
END$$

DELIMITER ;
