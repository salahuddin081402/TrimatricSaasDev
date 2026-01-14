/* =========================================================
   COMPANY AREA ADMINS (division & district)
   - Minimal rows (one per user+area), toggled by status
   - Tenure columns track last activation/deactivation
   - Enforce: at most one active per (company, area)
   - Enforce: user.company_id = mapping.company_id
   ========================================================= */

SET NAMES utf8mb4;
SET time_zone = '+00:00';

/* --------- Drop triggers (safe re-run) --------- */
DROP TRIGGER IF EXISTS trg_company_division_admins_bi;
DROP TRIGGER IF EXISTS trg_company_division_admins_bu;
DROP TRIGGER IF EXISTS trg_company_district_admins_bi;
DROP TRIGGER IF EXISTS trg_company_district_admins_bu;

/* =================== TABLES ==================== */

/* ---------- Division-level admins ---------- */
CREATE TABLE IF NOT EXISTS company_division_admins (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id      BIGINT UNSIGNED NOT NULL COMMENT 'FK → companies.id',
  division_id     BIGINT UNSIGNED NOT NULL COMMENT 'FK → divisions.id',
  user_id         BIGINT UNSIGNED NOT NULL COMMENT 'FK → users.id',
  status          TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',

  /* Tenure / audit (optional but handy) */
  activated_at    DATETIME NULL,
  deactivated_at  DATETIME NULL,
  activated_by    BIGINT UNSIGNED NULL COMMENT 'FK → users.id',
  deactivated_by  BIGINT UNSIGNED NULL COMMENT 'FK → users.id',
  created_by      BIGINT UNSIGNED NULL,
  updated_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,

  /* Keys / constraints */
  CONSTRAINT fk_cda_company  FOREIGN KEY (company_id)  REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_cda_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
  CONSTRAINT fk_cda_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_cda_activated_by   FOREIGN KEY (activated_by)   REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_cda_deactivated_by FOREIGN KEY (deactivated_by) REFERENCES users(id) ON DELETE SET NULL,

  /* One row per user+division+company */
  UNIQUE KEY uq_cda_user_area (company_id, division_id, user_id),

  /* Helpful indexes for queries & enforcement */
  INDEX idx_cda_area_status (company_id, division_id, status),
  INDEX idx_cda_user        (company_id, user_id),
  INDEX idx_cda_div         (division_id),
  INDEX idx_cda_status      (status),

  CHECK (status IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Division admins per company (only one active per division & company)';


/* ---------- District-level admins ---------- */
CREATE TABLE IF NOT EXISTS company_district_admins (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id      BIGINT UNSIGNED NOT NULL COMMENT 'FK → companies.id',
  district_id     BIGINT UNSIGNED NOT NULL COMMENT 'FK → districts.id',
  user_id         BIGINT UNSIGNED NOT NULL COMMENT 'FK → users.id',
  status          TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',

  /* Tenure / audit */
  activated_at    DATETIME NULL,
  deactivated_at  DATETIME NULL,
  activated_by    BIGINT UNSIGNED NULL COMMENT 'FK → users.id',
  deactivated_by  BIGINT UNSIGNED NULL COMMENT 'FK → users.id',
  created_by      BIGINT UNSIGNED NULL,
  updated_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,

  /* Keys / constraints */
  CONSTRAINT fk_cxta_company  FOREIGN KEY (company_id)  REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_cxta_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
  CONSTRAINT fk_cxta_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_cxta_activated_by   FOREIGN KEY (activated_by)   REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_cxta_deactivated_by FOREIGN KEY (deactivated_by) REFERENCES users(id) ON DELETE SET NULL,

  /* One row per user+district+company */
  UNIQUE KEY uq_cxta_user_area (company_id, district_id, user_id),

  /* Helpful indexes */
  INDEX idx_cxta_area_status (company_id, district_id, status),
  INDEX idx_cxta_user        (company_id, user_id),
  INDEX idx_cxta_dist        (district_id),
  INDEX idx_cxta_status      (status),

  CHECK (status IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='District admins per company (only one active per district & company)';


/* ================== TRIGGERS ================== */
DELIMITER $$

/* ---------- DIVISION ADMINS ---------- */
/* Before INSERT: user’s company must match; ensure single active per (company, division); set tenure timestamps */
CREATE TRIGGER trg_company_division_admins_bi
BEFORE INSERT ON company_division_admins
FOR EACH ROW
BEGIN
  DECLARE v_user_co BIGINT UNSIGNED;
  DECLARE v_conflict_id BIGINT UNSIGNED;

  /* user.company must equal mapping.company */
  SELECT company_id INTO v_user_co FROM users WHERE id = NEW.user_id LIMIT 1;
  IF v_user_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid user_id';
  END IF;
  IF v_user_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='User company_id must equal mapping company_id';
  END IF;

  IF NEW.status = 1 THEN
    /* another active already exists? */
    SELECT id INTO v_conflict_id
    FROM company_division_admins
    WHERE company_id = NEW.company_id
      AND division_id = NEW.division_id
      AND status = 1
    LIMIT 1;
    IF v_conflict_id IS NOT NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Another active Division Admin already exists for this (company, division)';
    END IF;

    /* tenure stamps */
    IF NEW.activated_at IS NULL THEN
      SET NEW.activated_at = NOW();
    END IF;
    SET NEW.deactivated_at = NULL;
  ELSE
    /* status=0 on insert → set deactivated_at if not given */
    IF NEW.deactivated_at IS NULL THEN
      SET NEW.deactivated_at = NOW();
    END IF;
  END IF;
END$$

/* Before UPDATE: re-check company match; enforce single active; maintain tenure */
CREATE TRIGGER trg_company_division_admins_bu
BEFORE UPDATE ON company_division_admins
FOR EACH ROW
BEGIN
  DECLARE v_user_co BIGINT UNSIGNED;
  DECLARE v_conflict_id BIGINT UNSIGNED;

  /* If user or company changed, re-check company match */
  IF NEW.user_id <> OLD.user_id OR NEW.company_id <> OLD.company_id THEN
    SELECT company_id INTO v_user_co FROM users WHERE id = NEW.user_id LIMIT 1;
    IF v_user_co IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid user_id (update)';
    END IF;
    IF v_user_co <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='User company_id must equal mapping company_id (update)';
    END IF;
  END IF;

  /* Going/remaining active must be unique within (company, division) */
  IF NEW.status = 1 THEN
    SELECT id INTO v_conflict_id
    FROM company_division_admins
    WHERE company_id = NEW.company_id
      AND division_id = NEW.division_id
      AND status = 1
      AND id <> NEW.id
    LIMIT 1;
    IF v_conflict_id IS NOT NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Another active Division Admin already exists for this (company, division) (update)';
    END IF;

    /* If (re)activating, ensure activated_at set and deactivated_at cleared */
    IF OLD.status = 0 AND NEW.status = 1 THEN
      IF NEW.activated_at IS NULL THEN
        SET NEW.activated_at = NOW();
      END IF;
      SET NEW.deactivated_at = NULL;
    END IF;
  ELSE
    /* Deactivating: set deactivated_at if missing */
    IF OLD.status = 1 AND NEW.status = 0 AND NEW.deactivated_at IS NULL THEN
      SET NEW.deactivated_at = NOW();
    END IF;
  END IF;
END$$


/* ---------- DISTRICT ADMINS ---------- */
/* Before INSERT */
CREATE TRIGGER trg_company_district_admins_bi
BEFORE INSERT ON company_district_admins
FOR EACH ROW
BEGIN
  DECLARE v_user_co BIGINT UNSIGNED;
  DECLARE v_conflict_id BIGINT UNSIGNED;

  SELECT company_id INTO v_user_co FROM users WHERE id = NEW.user_id LIMIT 1;
  IF v_user_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid user_id';
  END IF;
  IF v_user_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='User company_id must equal mapping company_id';
  END IF;

  IF NEW.status = 1 THEN
    SELECT id INTO v_conflict_id
    FROM company_district_admins
    WHERE company_id = NEW.company_id
      AND district_id = NEW.district_id
      AND status = 1
    LIMIT 1;
    IF v_conflict_id IS NOT NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Another active District Admin already exists for this (company, district)';
    END IF;

    IF NEW.activated_at IS NULL THEN
      SET NEW.activated_at = NOW();
    END IF;
    SET NEW.deactivated_at = NULL;
  ELSE
    IF NEW.deactivated_at IS NULL THEN
      SET NEW.deactivated_at = NOW();
    END IF;
  END IF;
END$$

/* Before UPDATE */
CREATE TRIGGER trg_company_district_admins_bu
BEFORE UPDATE ON company_district_admins
FOR EACH ROW
BEGIN
  DECLARE v_user_co BIGINT UNSIGNED;
  DECLARE v_conflict_id BIGINT UNSIGNED;

  IF NEW.user_id <> OLD.user_id OR NEW.company_id <> OLD.company_id THEN
    SELECT company_id INTO v_user_co FROM users WHERE id = NEW.user_id LIMIT 1;
    IF v_user_co IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid user_id (update)';
    END IF;
    IF v_user_co <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='User company_id must equal mapping company_id (update)';
    END IF;
  END IF;

  IF NEW.status = 1 THEN
    SELECT id INTO v_conflict_id
    FROM company_district_admins
    WHERE company_id = NEW.company_id
      AND district_id = NEW.district_id
      AND status = 1
      AND id <> NEW.id
    LIMIT 1;
    IF v_conflict_id IS NOT NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Another active District Admin already exists for this (company, district) (update)';
    END IF;

    IF OLD.status = 0 AND NEW.status = 1 THEN
      IF NEW.activated_at IS NULL THEN
        SET NEW.activated_at = NOW();
      END IF;
      SET NEW.deactivated_at = NULL;
    END IF;
  ELSE
    IF OLD.status = 1 AND NEW.status = 0 AND NEW.deactivated_at IS NULL THEN
      SET NEW.deactivated_at = NOW();
    END IF;
  END IF;
END$$

DELIMITER ;
