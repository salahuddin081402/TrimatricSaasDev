-- =========================================================
-- GEOGRAPHY SCHEMA: divisions, districts, upazilas
-- Rules:
--  • No soft delete (no deleted_at)
--  • Parent hard delete -> child hard delete (ON DELETE CASCADE)
--  • short_code:
--      division: CHAR(3)         e.g. DHK
--      district: CHAR(5)         DIV(3) + dist_no(2)     e.g. DHK01
--      upazila : CHAR(7)         DIST(5) + upa_no(2)     e.g. DHK0101
--  • dist_no / upa_no auto-assigned 1..99 per parent
-- =========================================================
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------- DROP in dependency order (safe re-run) ----------
DROP TRIGGER IF EXISTS trg_districts_bi;
DROP TRIGGER IF EXISTS trg_districts_bu;
DROP TRIGGER IF EXISTS trg_upazilas_bi;
DROP TRIGGER IF EXISTS trg_upazilas_bu;
DROP TRIGGER IF EXISTS trg_divisions_au;
DROP TRIGGER IF EXISTS trg_districts_au;

DROP TABLE IF EXISTS upazilas;
DROP TABLE IF EXISTS districts;
DROP TABLE IF EXISTS divisions;

-- ==================== TABLES ====================

-- ---------- Divisions ----------
CREATE TABLE divisions (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Division ID',
  name          VARCHAR(150) NOT NULL COMMENT 'Division name',
  short_code    CHAR(3)      NOT NULL COMMENT '3-letter code, e.g. DHK',
  status        TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  created_by    BIGINT UNSIGNED NULL,
  updated_by    BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_divisions_name (name),
  UNIQUE KEY uq_divisions_code (short_code),
  INDEX idx_divisions_status (status),
  INDEX idx_divisions_created_by (created_by),
  INDEX idx_divisions_updated_by (updated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bangladesh divisions';

-- ---------- Districts ----------
CREATE TABLE districts (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'District ID',
  division_id   BIGINT UNSIGNED NOT NULL COMMENT 'FK → divisions.id',
  name          VARCHAR(150) NOT NULL COMMENT 'District (Zila) name',
  dist_no       TINYINT UNSIGNED NOT NULL COMMENT '01..99 per division (auto-assigned)',
  short_code    CHAR(5)      NOT NULL COMMENT 'DIV(3) + dist_no(2), e.g. DHK01',
  status        TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  created_by    BIGINT UNSIGNED NULL,
  updated_by    BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_districts_division
    FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
  UNIQUE KEY uq_districts_name_per_div (division_id, name),
  UNIQUE KEY uq_districts_no_per_div (division_id, dist_no),
  UNIQUE KEY uq_districts_code (short_code),
  INDEX idx_districts_division (division_id),
  INDEX idx_districts_status (status),
  INDEX idx_districts_created_by (created_by),
  INDEX idx_districts_updated_by (updated_by),
  CHECK (dist_no BETWEEN 1 AND 99)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Districts (Zila)';

-- ---------- Upazilas ----------
CREATE TABLE upazilas (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Upazila ID',
  district_id   BIGINT UNSIGNED NOT NULL COMMENT 'FK → districts.id',
  name          VARCHAR(150) NOT NULL COMMENT 'Upazila/Thana name',
  upa_no        TINYINT UNSIGNED NOT NULL COMMENT '01..99 per district (auto-assigned)',
  short_code    CHAR(7)      NOT NULL COMMENT 'DIST(5) + upa_no(2), e.g. DHK0101',
  status        TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  created_by    BIGINT UNSIGNED NULL,
  updated_by    BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_upazilas_district
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
  UNIQUE KEY uq_upazilas_name_per_dist (district_id, name),
  UNIQUE KEY uq_upazilas_no_per_dist (district_id, upa_no),
  UNIQUE KEY uq_upazilas_code (short_code),
  INDEX idx_upazilas_district (district_id),
  INDEX idx_upazilas_status (status),
  INDEX idx_upazilas_created_by (created_by),
  INDEX idx_upazilas_updated_by (updated_by),
  CHECK (upa_no BETWEEN 1 AND 99)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Upazilas (sub-districts)';

-- ==================== TRIGGERS ====================
DELIMITER $$

-- ---- Districts: BEFORE INSERT ----
CREATE TRIGGER trg_districts_bi
BEFORE INSERT ON districts FOR EACH ROW
BEGIN
  DECLARE v_div_code CHAR(3);
  DECLARE v_next TINYINT UNSIGNED;

  SELECT short_code INTO v_div_code
  FROM divisions
  WHERE id = NEW.division_id
  LIMIT 1;

  IF v_div_code IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid division_id for district';
  END IF;

  IF NEW.dist_no IS NULL OR NEW.dist_no = 0 THEN
    SELECT COALESCE(MAX(dist_no),0)+1 INTO v_next
    FROM districts
    WHERE division_id = NEW.division_id;
    SET NEW.dist_no = v_next;
  END IF;

  IF NEW.dist_no < 1 OR NEW.dist_no > 99 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='dist_no must be between 1 and 99';
  END IF;

  SET NEW.short_code = CONCAT(v_div_code, LPAD(NEW.dist_no, 2, '0'));
END$$

-- ---- Districts: BEFORE UPDATE ----
CREATE TRIGGER trg_districts_bu
BEFORE UPDATE ON districts FOR EACH ROW
BEGIN
  DECLARE v_div_code CHAR(3);
  SELECT short_code INTO v_div_code FROM divisions WHERE id = NEW.division_id LIMIT 1;

  IF NEW.dist_no < 1 OR NEW.dist_no > 99 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='dist_no must be between 1 and 99';
  END IF;

  SET NEW.short_code = CONCAT(v_div_code, LPAD(NEW.dist_no, 2, '0'));
END$$

-- ---- Upazilas: BEFORE INSERT ----
CREATE TRIGGER trg_upazilas_bi
BEFORE INSERT ON upazilas FOR EACH ROW
BEGIN
  DECLARE v_dist_code CHAR(5);
  DECLARE v_next TINYINT UNSIGNED;

  SELECT short_code INTO v_dist_code
  FROM districts
  WHERE id = NEW.district_id
  LIMIT 1;

  IF v_dist_code IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid district_id for upazila';
  END IF;

  IF NEW.upa_no IS NULL OR NEW.upa_no = 0 THEN
    SELECT COALESCE(MAX(upa_no),0)+1 INTO v_next
    FROM upazilas
    WHERE district_id = NEW.district_id;
    SET NEW.upa_no = v_next;
  END IF;

  IF NEW.upa_no < 1 OR NEW.upa_no > 99 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='upa_no must be between 1 and 99';
  END IF;

  SET NEW.short_code = CONCAT(v_dist_code, LPAD(NEW.upa_no, 2, '0'));
END$$

-- ---- Upazilas: BEFORE UPDATE ----
CREATE TRIGGER trg_upazilas_bu
BEFORE UPDATE ON upazilas FOR EACH ROW
BEGIN
  DECLARE v_dist_code CHAR(5);
  SELECT short_code INTO v_dist_code FROM districts WHERE id = NEW.district_id LIMIT 1;

  IF NEW.upa_no < 1 OR NEW.upa_no > 99 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='upa_no must be between 1 and 99';
  END IF;

  SET NEW.short_code = CONCAT(v_dist_code, LPAD(NEW.upa_no, 2, '0'));
END$$

-- ---- Cascade rebuild of child short_codes if a parent code changes ----
CREATE TRIGGER trg_divisions_au
AFTER UPDATE ON divisions FOR EACH ROW
BEGIN
  IF NEW.short_code <> OLD.short_code THEN
    UPDATE districts
      SET short_code = CONCAT(NEW.short_code, LPAD(dist_no, 2, '0'))
    WHERE division_id = NEW.id;
  END IF;
END$$

CREATE TRIGGER trg_districts_au
AFTER UPDATE ON districts FOR EACH ROW
BEGIN
  IF NEW.short_code <> OLD.short_code THEN
    UPDATE upazilas
      SET short_code = CONCAT(NEW.short_code, LPAD(upa_no, 2, '0'))
    WHERE district_id = NEW.id;
  END IF;
END$$

DELIMITER ;
