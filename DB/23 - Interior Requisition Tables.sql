/* ============================================================
   Client Interior Requisition Module (FINAL DB SCRIPT)
   Transactional Tables + DB-level Integrity (TRIGGERS)
   (DOES NOT repeat your 7 parameter/setup tables)

   Target: MariaDB 10.4.x / MySQL 8+, InnoDB, utf8mb4

   Depends on existing tables:
     - companies(id)
     - users(id, company_id)
     - registration_master(id, company_id, user_id, registration_type, upazila_id, ...)
     - cr_project_types, cr_project_subtypes, cr_spaces
     - cr_item_categories, cr_item_subcategories, cr_products
     - cr_space_category_mappings(company_id, space_id, category_id, is_active)

   DB-enforced key rules (NOT app-only):
     - reg_id must belong to same company_id
     - client_user_id must match registration_master.user_id and belong to same company
     - registration_type must be in ('client','enterprise_client') for creation
     - referenced parameter rows must be (company_id IS NULL OR company_id = requisition.company_id)
     - subtype must belong to type
     - space must belong to subtype
     - subcategory must belong to category
     - product must belong to subcategory
     - selected categories per space must exist in cr_space_category_mappings(company_id, space_id, category_id, is_active=1)
     - project_total_sqft always auto-recomputed from space lines (no trust on app)

   ============================================================ */

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Drop triggers (safe)
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_irm_bi;
DROP TRIGGER IF EXISTS trg_irm_bu;

DROP TRIGGER IF EXISTS trg_irsl_bi;
DROP TRIGGER IF EXISTS trg_irsl_bu;
DROP TRIGGER IF EXISTS trg_irsl_ai;
DROP TRIGGER IF EXISTS trg_irsl_au;
DROP TRIGGER IF EXISTS trg_irsl_ad;

DROP TRIGGER IF EXISTS trg_ircl_bi;
DROP TRIGGER IF EXISTS trg_ircl_bu;

DROP TRIGGER IF EXISTS trg_irsl2_bi;
DROP TRIGGER IF EXISTS trg_irsl2_bu;

DROP TRIGGER IF EXISTS trg_irpl_bi;
DROP TRIGGER IF EXISTS trg_irpl_bu;

DROP TRIGGER IF EXISTS trg_ira_bi;

DROP TRIGGER IF EXISTS trg_irlog_bi;

-- ------------------------------------------------------------
-- Drop tables (FK-safe order)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS interior_requisition_attachments;
DROP TABLE IF EXISTS interior_requisition_product_lines;
DROP TABLE IF EXISTS interior_requisition_subcategory_lines;
DROP TABLE IF EXISTS interior_requisition_category_lines;
DROP TABLE IF EXISTS interior_requisition_space_lines;
DROP TABLE IF EXISTS interior_requisition_status_logs;
DROP TABLE IF EXISTS interior_requisition_master;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1) interior_requisition_master
-- ============================================================
CREATE TABLE interior_requisition_master (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id           BIGINT UNSIGNED NOT NULL,

    reg_id               BIGINT UNSIGNED NOT NULL,  -- registration_master.id
    client_user_id       BIGINT UNSIGNED NOT NULL,  -- must match registration_master.user_id

    -- Step-1 "Project Details" inputs (store only what user inputs)
    project_address      VARCHAR(600)    NULL,
    project_note         TEXT            NULL,

    -- computed from space lines (DB auto-updated)
    project_total_sqft   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,

    project_budget       DECIMAL(14,2)   NULL,
    project_eta          DATE            NULL,

    -- internal (future UI)
    cluster_member_remark TEXT           NULL,
    head_office_remark   TEXT            NULL,

    -- Step-1 selections
    project_type_id      BIGINT UNSIGNED NULL,
    project_subtype_id   BIGINT UNSIGNED NULL,

    -- lifecycle
    status               VARCHAR(20)     NOT NULL DEFAULT 'Draft',
    submitted_at         DATETIME        NULL,
    closed_at            DATETIME        NULL,

    -- audit
    created_by           BIGINT UNSIGNED NULL,
    updated_by           BIGINT UNSIGNED NULL,
    created_at           DATETIME        NULL,
    updated_at           DATETIME        NULL,
    deleted_at           DATETIME        NULL,

    PRIMARY KEY (id),

    KEY idx_irm_company_status (company_id, status),
    KEY idx_irm_company_reg    (company_id, reg_id),
    KEY idx_irm_company_user   (company_id, client_user_id),
    KEY idx_irm_company_type   (company_id, project_type_id),
    KEY idx_irm_company_subt   (company_id, project_subtype_id),

    CONSTRAINT fk_irm_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irm_reg
        FOREIGN KEY (reg_id) REFERENCES registration_master(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irm_user
        FOREIGN KEY (client_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irm_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_irm_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_irm_type
        FOREIGN KEY (project_type_id) REFERENCES cr_project_types(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irm_subtype
        FOREIGN KEY (project_subtype_id) REFERENCES cr_project_subtypes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_irm_status
        CHECK (status IN ('Draft','Submitted','InReview','Quoted','Approved','Closed','Declined'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2) Step-1: selected spaces (qty + sqft snapshot)
-- ============================================================
CREATE TABLE interior_requisition_space_lines (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id           BIGINT UNSIGNED NOT NULL,
    requisition_id       BIGINT UNSIGNED NOT NULL,

    space_id             BIGINT UNSIGNED NOT NULL,
    space_qty            INT UNSIGNED    NOT NULL DEFAULT 1,
    space_total_sqft     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,

    sort_order           INT UNSIGNED    NOT NULL DEFAULT 0,

    created_at           DATETIME NULL,
    updated_at           DATETIME NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_irsl_req_space (company_id, requisition_id, space_id),
    KEY idx_irsl_req_space       (requisition_id, space_id),
    KEY idx_irsl_company_req     (company_id, requisition_id),

    CONSTRAINT fk_irsl_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irsl_req
        FOREIGN KEY (requisition_id) REFERENCES interior_requisition_master(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_irsl_space
        FOREIGN KEY (space_id) REFERENCES cr_spaces(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_irsl_qty CHECK (space_qty >= 1),
    CONSTRAINT chk_irsl_sqft CHECK (space_total_sqft >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3) Step-2: selected categories per space
-- ============================================================
CREATE TABLE interior_requisition_category_lines (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id           BIGINT UNSIGNED NOT NULL,
    requisition_id       BIGINT UNSIGNED NOT NULL,
    space_id             BIGINT UNSIGNED NOT NULL,
    category_id          BIGINT UNSIGNED NOT NULL,

    sort_order           INT UNSIGNED    NOT NULL DEFAULT 0,

    created_at           DATETIME NULL,
    updated_at           DATETIME NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_ircl (company_id, requisition_id, space_id, category_id),
    KEY idx_ircl_req_space_cat (requisition_id, space_id, category_id),

    CONSTRAINT fk_ircl_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_ircl_req
        FOREIGN KEY (requisition_id) REFERENCES interior_requisition_master(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_ircl_space
        FOREIGN KEY (space_id) REFERENCES cr_spaces(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_ircl_category
        FOREIGN KEY (category_id) REFERENCES cr_item_categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4) Step-2: selected subcategories per space (optional)
-- ============================================================
CREATE TABLE interior_requisition_subcategory_lines (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id           BIGINT UNSIGNED NOT NULL,
    requisition_id       BIGINT UNSIGNED NOT NULL,
    space_id             BIGINT UNSIGNED NOT NULL,
    category_id          BIGINT UNSIGNED NOT NULL,
    subcategory_id       BIGINT UNSIGNED NOT NULL,

    sort_order           INT UNSIGNED    NOT NULL DEFAULT 0,

    created_at           DATETIME NULL,
    updated_at           DATETIME NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_irsl2 (company_id, requisition_id, space_id, subcategory_id),
    KEY idx_irsl2_req_space_cat_subcat (requisition_id, space_id, category_id, subcategory_id),

    CONSTRAINT fk_irsl2_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irsl2_req
        FOREIGN KEY (requisition_id) REFERENCES interior_requisition_master(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_irsl2_space
        FOREIGN KEY (space_id) REFERENCES cr_spaces(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irsl2_category
        FOREIGN KEY (category_id) REFERENCES cr_item_categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irsl2_subcategory
        FOREIGN KEY (subcategory_id) REFERENCES cr_item_subcategories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5) Step-2: product lines per space with qty
-- ============================================================
CREATE TABLE interior_requisition_product_lines (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id           BIGINT UNSIGNED NOT NULL,
    requisition_id       BIGINT UNSIGNED NOT NULL,
    space_id             BIGINT UNSIGNED NOT NULL,

    category_id          BIGINT UNSIGNED NOT NULL,
    subcategory_id       BIGINT UNSIGNED NOT NULL,
    product_id           BIGINT UNSIGNED NOT NULL,

    qty                 INT UNSIGNED    NOT NULL DEFAULT 1,

    created_at           DATETIME NULL,
    updated_at           DATETIME NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_irpl (company_id, requisition_id, space_id, product_id),
    KEY idx_irpl_req_space_subcat_product (requisition_id, space_id, subcategory_id, product_id),

    CONSTRAINT fk_irpl_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irpl_req
        FOREIGN KEY (requisition_id) REFERENCES interior_requisition_master(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_irpl_space
        FOREIGN KEY (space_id) REFERENCES cr_spaces(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irpl_category
        FOREIGN KEY (category_id) REFERENCES cr_item_categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irpl_subcategory
        FOREIGN KEY (subcategory_id) REFERENCES cr_item_subcategories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irpl_product
        FOREIGN KEY (product_id) REFERENCES cr_products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_irpl_qty CHECK (qty >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6) Attachments (multi-image)
-- ============================================================
CREATE TABLE interior_requisition_attachments (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id           BIGINT UNSIGNED NOT NULL,
    requisition_id       BIGINT UNSIGNED NOT NULL,

    uploaded_by_user_id  BIGINT UNSIGNED NULL,
    uploaded_by_reg_id   BIGINT UNSIGNED NULL,

    step_no              TINYINT UNSIGNED NULL,  -- 1 or 2
    note                 VARCHAR(300) NULL,

    original_name        VARCHAR(255) NULL,
    file_path            VARCHAR(255) NOT NULL,  -- relative path only
    mime_type            VARCHAR(80)  NULL,
    file_size_kb         INT UNSIGNED NULL,

    sort_order           INT UNSIGNED NOT NULL DEFAULT 0,
    created_at           DATETIME NULL,

    PRIMARY KEY (id),

    KEY idx_ira_company_req (company_id, requisition_id),
    KEY idx_ira_req (requisition_id),

    CONSTRAINT fk_ira_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_ira_req
        FOREIGN KEY (requisition_id) REFERENCES interior_requisition_master(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_ira_uploaded_user
        FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_ira_uploaded_reg
        FOREIGN KEY (uploaded_by_reg_id) REFERENCES registration_master(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_ira_step CHECK (step_no IS NULL OR step_no IN (1,2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7) Status logs (audit)
-- ============================================================
CREATE TABLE interior_requisition_status_logs (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id           BIGINT UNSIGNED NOT NULL,
    requisition_id       BIGINT UNSIGNED NOT NULL,

    from_status          VARCHAR(20) NULL,
    to_status            VARCHAR(20) NOT NULL,
    note                VARCHAR(500) NULL,

    changed_by_user_id   BIGINT UNSIGNED NULL,
    changed_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_irlog_company_req (company_id, requisition_id),
    KEY idx_irlog_company_to  (company_id, to_status),

    CONSTRAINT fk_irlog_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_irlog_req
        FOREIGN KEY (requisition_id) REFERENCES interior_requisition_master(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_irlog_changed_by
        FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_irlog_status
        CHECK (
          (from_status IS NULL OR from_status IN ('Draft','Submitted','InReview','Quoted','Approved','Closed','Declined'))
          AND (to_status IN ('Draft','Submitted','InReview','Quoted','Approved','Closed','Declined'))
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TRIGGERS: HARD DB-LEVEL INTEGRITY (NO NEW TABLES)
-- ============================================================

DELIMITER $$

/* ---------- Master: validate tenant + reg/user + parameter ownership ---------- */
CREATE TRIGGER trg_irm_bi
BEFORE INSERT ON interior_requisition_master
FOR EACH ROW
BEGIN
  DECLARE v_reg_company BIGINT UNSIGNED;
  DECLARE v_reg_user BIGINT UNSIGNED;
  DECLARE v_reg_type VARCHAR(50);
  DECLARE v_user_company BIGINT UNSIGNED;

  -- registration must exist and belong to same company
  SELECT company_id, user_id, registration_type
    INTO v_reg_company, v_reg_user, v_reg_type
  FROM registration_master
  WHERE id = NEW.reg_id
  LIMIT 1;

  IF v_reg_company IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid reg_id';
  END IF;

  IF v_reg_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'reg_id must belong to same company';
  END IF;

  IF v_reg_user <> NEW.client_user_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'client_user_id must match registration_master.user_id';
  END IF;

  -- user must belong to same company
  SELECT company_id INTO v_user_company
  FROM users WHERE id = NEW.client_user_id LIMIT 1;

  IF v_user_company IS NULL OR v_user_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'client_user_id must belong to same company';
  END IF;

  -- only client/enterprise_client can create
  IF v_reg_type NOT IN ('client','enterprise_client') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only client or enterprise_client can create requisition';
  END IF;

  -- enforce parameter ownership (type/subtype)
  IF NEW.project_type_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1 FROM cr_project_types t
      WHERE t.id = NEW.project_type_id
        AND (t.company_id IS NULL OR t.company_id = NEW.company_id)
        AND t.is_active = 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid project_type_id for this company';
    END IF;
  END IF;

  IF NEW.project_subtype_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1 FROM cr_project_subtypes s
      WHERE s.id = NEW.project_subtype_id
        AND (s.company_id IS NULL OR s.company_id = NEW.company_id)
        AND s.is_active = 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid project_subtype_id for this company';
    END IF;
  END IF;

  IF NEW.project_type_id IS NOT NULL AND NEW.project_subtype_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1 FROM cr_project_subtypes s
      WHERE s.id = NEW.project_subtype_id
        AND s.project_type_id = NEW.project_type_id
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'project_subtype_id does not belong to project_type_id';
    END IF;
  END IF;

  -- DB is source of truth for total sqft
  SET NEW.project_total_sqft = 0.00;
END$$

CREATE TRIGGER trg_irm_bu
BEFORE UPDATE ON interior_requisition_master
FOR EACH ROW
BEGIN
  DECLARE v_reg_company BIGINT UNSIGNED;
  DECLARE v_reg_user BIGINT UNSIGNED;
  DECLARE v_user_company BIGINT UNSIGNED;

  -- lock edits if closed/declined (hard DB lock)
  IF OLD.status IN ('Closed','Declined') THEN
    -- allow only no-op updates (block anything)
    IF NOT (
      NEW.status = OLD.status
      AND IFNULL(NEW.project_address,'') = IFNULL(OLD.project_address,'')
      AND IFNULL(NEW.project_note,'') = IFNULL(OLD.project_note,'')
      AND IFNULL(NEW.project_budget,0) = IFNULL(OLD.project_budget,0)
      AND IFNULL(NEW.project_eta,'0000-00-00') = IFNULL(OLD.project_eta,'0000-00-00')
      AND IFNULL(NEW.head_office_remark,'') = IFNULL(OLD.head_office_remark,'')
      AND IFNULL(NEW.project_type_id,0) = IFNULL(OLD.project_type_id,0)
      AND IFNULL(NEW.project_subtype_id,0) = IFNULL(OLD.project_subtype_id,0)
      AND IFNULL(NEW.submitted_at,'0000-00-00 00:00:00') = IFNULL(OLD.submitted_at,'0000-00-00 00:00:00')
      AND IFNULL(NEW.closed_at,'0000-00-00 00:00:00') = IFNULL(OLD.closed_at,'0000-00-00 00:00:00')
      AND IFNULL(NEW.created_by,0) = IFNULL(OLD.created_by,0)
      AND IFNULL(NEW.updated_by,0) = IFNULL(OLD.updated_by,0)
      AND IFNULL(NEW.created_at,'0000-00-00 00:00:00') = IFNULL(OLD.created_at,'0000-00-00 00:00:00')
      AND IFNULL(NEW.updated_at,'0000-00-00 00:00:00') = IFNULL(OLD.updated_at,'0000-00-00 00:00:00')
      AND IFNULL(NEW.deleted_at,'0000-00-00 00:00:00') = IFNULL(OLD.deleted_at,'0000-00-00 00:00:00')
      AND IFNULL(NEW.project_total_sqft,0) = IFNULL(OLD.project_total_sqft,0)
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Requisition is locked (Closed/Declined)';
    END IF;
  END IF;

  -- prevent company/reg/user reassignment
  IF NEW.company_id <> OLD.company_id OR NEW.reg_id <> OLD.reg_id OR NEW.client_user_id <> OLD.client_user_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot change company_id/reg_id/client_user_id';
  END IF;

  -- re-validate reg/user consistency
  SELECT company_id, user_id INTO v_reg_company, v_reg_user
  FROM registration_master WHERE id = NEW.reg_id LIMIT 1;

  IF v_reg_company IS NULL OR v_reg_company <> NEW.company_id OR v_reg_user <> NEW.client_user_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid reg/user/company binding';
  END IF;

  SELECT company_id INTO v_user_company
  FROM users WHERE id = NEW.client_user_id LIMIT 1;

  IF v_user_company IS NULL OR v_user_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'client_user_id must belong to same company';
  END IF;

  -- enforce parameter ownership (type/subtype) on update
  IF NEW.project_type_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1 FROM cr_project_types t
      WHERE t.id = NEW.project_type_id
        AND (t.company_id IS NULL OR t.company_id = NEW.company_id)
        AND t.is_active = 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid project_type_id for this company';
    END IF;
  END IF;

  IF NEW.project_subtype_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1 FROM cr_project_subtypes s
      WHERE s.id = NEW.project_subtype_id
        AND (s.company_id IS NULL OR s.company_id = NEW.company_id)
        AND s.is_active = 1
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid project_subtype_id for this company';
    END IF;
  END IF;

  IF NEW.project_type_id IS NOT NULL AND NEW.project_subtype_id IS NOT NULL THEN
    IF NOT EXISTS (
      SELECT 1 FROM cr_project_subtypes s
      WHERE s.id = NEW.project_subtype_id
        AND s.project_type_id = NEW.project_type_id
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'project_subtype_id does not belong to project_type_id';
    END IF;
  END IF;

  -- never trust app to set the total; it is maintained by triggers on space lines
  SET NEW.project_total_sqft = OLD.project_total_sqft;
END$$


/* ---------- Space lines: tenant binding + space ownership + auto total sqft ---------- */
CREATE TRIGGER trg_irsl_bi
BEFORE INSERT ON interior_requisition_space_lines
FOR EACH ROW
BEGIN
  DECLARE v_req_company BIGINT UNSIGNED;

  SELECT company_id INTO v_req_company
  FROM interior_requisition_master
  WHERE id = NEW.requisition_id
  LIMIT 1;

  IF v_req_company IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid requisition_id';
  END IF;

  IF v_req_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Space line company_id must match requisition company_id';
  END IF;

  -- space must be allowed for this company (global or tenant) and active
  IF NOT EXISTS (
    SELECT 1 FROM cr_spaces sp
    WHERE sp.id = NEW.space_id
      AND (sp.company_id IS NULL OR sp.company_id = NEW.company_id)
      AND sp.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid space_id for this company';
  END IF;

  -- must match requisition subtype if set
  IF EXISTS (
    SELECT 1 FROM interior_requisition_master m
    WHERE m.id = NEW.requisition_id
      AND m.project_subtype_id IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM cr_spaces sp
        WHERE sp.id = NEW.space_id
          AND sp.project_subtype_id = m.project_subtype_id
      )
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'space_id does not belong to requisition project_subtype_id';
  END IF;
END$$

CREATE TRIGGER trg_irsl_bu
BEFORE UPDATE ON interior_requisition_space_lines
FOR EACH ROW
BEGIN
  DECLARE v_req_company BIGINT UNSIGNED;

  IF NEW.company_id <> OLD.company_id OR NEW.requisition_id <> OLD.requisition_id OR NEW.space_id <> OLD.space_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot change company_id/requisition_id/space_id on space line';
  END IF;

  SELECT company_id INTO v_req_company
  FROM interior_requisition_master
  WHERE id = NEW.requisition_id
  LIMIT 1;

  IF v_req_company IS NULL OR v_req_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Space line company_id must match requisition company_id';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM cr_spaces sp
    WHERE sp.id = NEW.space_id
      AND (sp.company_id IS NULL OR sp.company_id = NEW.company_id)
      AND sp.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid space_id for this company (update)';
  END IF;

  IF EXISTS (
    SELECT 1 FROM interior_requisition_master m
    WHERE m.id = NEW.requisition_id
      AND m.project_subtype_id IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM cr_spaces sp
        WHERE sp.id = NEW.space_id
          AND sp.project_subtype_id = m.project_subtype_id
      )
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'space_id does not belong to requisition project_subtype_id (update)';
  END IF;
END$$

CREATE TRIGGER trg_irsl_ai
AFTER INSERT ON interior_requisition_space_lines
FOR EACH ROW
BEGIN
  UPDATE interior_requisition_master m
  SET m.project_total_sqft = (
    SELECT COALESCE(SUM(space_total_sqft),0.00)
    FROM interior_requisition_space_lines
    WHERE company_id = NEW.company_id AND requisition_id = NEW.requisition_id
  ),
  m.updated_at = COALESCE(m.updated_at, CURRENT_TIMESTAMP)
  WHERE m.id = NEW.requisition_id AND m.company_id = NEW.company_id;
END$$

CREATE TRIGGER trg_irsl_au
AFTER UPDATE ON interior_requisition_space_lines
FOR EACH ROW
BEGIN
  UPDATE interior_requisition_master m
  SET m.project_total_sqft = (
    SELECT COALESCE(SUM(space_total_sqft),0.00)
    FROM interior_requisition_space_lines
    WHERE company_id = NEW.company_id AND requisition_id = NEW.requisition_id
  ),
  m.updated_at = COALESCE(m.updated_at, CURRENT_TIMESTAMP)
  WHERE m.id = NEW.requisition_id AND m.company_id = NEW.company_id;
END$$

CREATE TRIGGER trg_irsl_ad
AFTER DELETE ON interior_requisition_space_lines
FOR EACH ROW
BEGIN
  UPDATE interior_requisition_master m
  SET m.project_total_sqft = (
    SELECT COALESCE(SUM(space_total_sqft),0.00)
    FROM interior_requisition_space_lines
    WHERE company_id = OLD.company_id AND requisition_id = OLD.requisition_id
  ),
  m.updated_at = COALESCE(m.updated_at, CURRENT_TIMESTAMP)
  WHERE m.id = OLD.requisition_id AND m.company_id = OLD.company_id;
END$$


/* ---------- Category lines: enforce mapping + ownership ---------- */
CREATE TRIGGER trg_ircl_bi
BEFORE INSERT ON interior_requisition_category_lines
FOR EACH ROW
BEGIN
  DECLARE v_req_company BIGINT UNSIGNED;

  SELECT company_id INTO v_req_company
  FROM interior_requisition_master
  WHERE id = NEW.requisition_id
  LIMIT 1;

  IF v_req_company IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid requisition_id (category line)';
  END IF;

  IF v_req_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category line company_id must match requisition company_id';
  END IF;

  -- space must exist in requisition space_lines
  IF NOT EXISTS (
    SELECT 1 FROM interior_requisition_space_lines sl
    WHERE sl.company_id = NEW.company_id
      AND sl.requisition_id = NEW.requisition_id
      AND sl.space_id = NEW.space_id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category line space_id not selected in Step-1';
  END IF;

  -- category must be allowed for this company (global or tenant) and active
  IF NOT EXISTS (
    SELECT 1 FROM cr_item_categories c
    WHERE c.id = NEW.category_id
      AND (c.company_id IS NULL OR c.company_id = NEW.company_id)
      AND c.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid category_id for this company';
  END IF;

  -- must be allowed by mapping
  IF NOT EXISTS (
    SELECT 1 FROM cr_space_category_mappings m
    WHERE m.company_id = NEW.company_id
      AND m.space_id = NEW.space_id
      AND m.category_id = NEW.category_id
      AND m.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category not allowed for this space (mapping)';
  END IF;
END$$

CREATE TRIGGER trg_ircl_bu
BEFORE UPDATE ON interior_requisition_category_lines
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category lines are immutable; delete+insert instead';
END$$


/* ---------- Subcategory lines: enforce ownership + subcategory belongs to category ---------- */
CREATE TRIGGER trg_irsl2_bi
BEFORE INSERT ON interior_requisition_subcategory_lines
FOR EACH ROW
BEGIN
  DECLARE v_req_company BIGINT UNSIGNED;

  SELECT company_id INTO v_req_company
  FROM interior_requisition_master
  WHERE id = NEW.requisition_id
  LIMIT 1;

  IF v_req_company IS NULL OR v_req_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid requisition/company (subcategory line)';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM interior_requisition_space_lines sl
    WHERE sl.company_id = NEW.company_id
      AND sl.requisition_id = NEW.requisition_id
      AND sl.space_id = NEW.space_id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Subcategory line space_id not selected in Step-1';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM interior_requisition_category_lines cl
    WHERE cl.company_id = NEW.company_id
      AND cl.requisition_id = NEW.requisition_id
      AND cl.space_id = NEW.space_id
      AND cl.category_id = NEW.category_id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Subcategory line category_id not selected for this space';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM cr_item_subcategories s
    WHERE s.id = NEW.subcategory_id
      AND s.category_id = NEW.category_id
      AND (s.company_id IS NULL OR s.company_id = NEW.company_id)
      AND s.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid subcategory_id for this category/company';
  END IF;
END$$

CREATE TRIGGER trg_irsl2_bu
BEFORE UPDATE ON interior_requisition_subcategory_lines
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Subcategory lines are immutable; delete+insert instead';
END$$


/* ---------- Product lines: enforce full hierarchy + ownership ---------- */
CREATE TRIGGER trg_irpl_bi
BEFORE INSERT ON interior_requisition_product_lines
FOR EACH ROW
BEGIN
  DECLARE v_req_company BIGINT UNSIGNED;

  SELECT company_id INTO v_req_company
  FROM interior_requisition_master
  WHERE id = NEW.requisition_id
  LIMIT 1;

  IF v_req_company IS NULL OR v_req_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid requisition/company (product line)';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM interior_requisition_space_lines sl
    WHERE sl.company_id = NEW.company_id
      AND sl.requisition_id = NEW.requisition_id
      AND sl.space_id = NEW.space_id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product line space_id not selected in Step-1';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM interior_requisition_category_lines cl
    WHERE cl.company_id = NEW.company_id
      AND cl.requisition_id = NEW.requisition_id
      AND cl.space_id = NEW.space_id
      AND cl.category_id = NEW.category_id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product line category_id not selected for this space';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM cr_item_subcategories s
    WHERE s.id = NEW.subcategory_id
      AND s.category_id = NEW.category_id
      AND (s.company_id IS NULL OR s.company_id = NEW.company_id)
      AND s.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid subcategory/category/company';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM cr_products p
    WHERE p.id = NEW.product_id
      AND p.subcategory_id = NEW.subcategory_id
      AND (p.company_id IS NULL OR p.company_id = NEW.company_id)
      AND p.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid product/subcategory/company';
  END IF;

  IF NEW.qty IS NULL OR NEW.qty < 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'qty must be >= 1';
  END IF;
END$$

CREATE TRIGGER trg_irpl_bu
BEFORE UPDATE ON interior_requisition_product_lines
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product lines are immutable; delete+insert instead';
END$$


/* ---------- Attachments: enforce company binding + uploader company consistency ---------- */
CREATE TRIGGER trg_ira_bi
BEFORE INSERT ON interior_requisition_attachments
FOR EACH ROW
BEGIN
  DECLARE v_req_company BIGINT UNSIGNED;
  DECLARE v_user_company BIGINT UNSIGNED;
  DECLARE v_reg_company BIGINT UNSIGNED;

  SELECT company_id INTO v_req_company
  FROM interior_requisition_master
  WHERE id = NEW.requisition_id
  LIMIT 1;

  IF v_req_company IS NULL OR v_req_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Attachment company_id must match requisition company_id';
  END IF;

  IF NEW.uploaded_by_user_id IS NOT NULL THEN
    SELECT company_id INTO v_user_company
    FROM users WHERE id = NEW.uploaded_by_user_id LIMIT 1;
    IF v_user_company IS NULL OR v_user_company <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'uploaded_by_user_id must belong to same company';
    END IF;
  END IF;

  IF NEW.uploaded_by_reg_id IS NOT NULL THEN
    SELECT company_id INTO v_reg_company
    FROM registration_master WHERE id = NEW.uploaded_by_reg_id LIMIT 1;
    IF v_reg_company IS NULL OR v_reg_company <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'uploaded_by_reg_id must belong to same company';
    END IF;
  END IF;

  IF NEW.file_path IS NULL OR NEW.file_path = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'file_path is required (relative path only)';
  END IF;

  IF NEW.step_no IS NOT NULL AND NEW.step_no NOT IN (1,2) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'step_no must be 1 or 2';
  END IF;
END$$


/* ---------- Status logs: enforce company binding + allowed statuses ---------- */
CREATE TRIGGER trg_irlog_bi
BEFORE INSERT ON interior_requisition_status_logs
FOR EACH ROW
BEGIN
  DECLARE v_req_company BIGINT UNSIGNED;
  DECLARE v_user_company BIGINT UNSIGNED;

  SELECT company_id INTO v_req_company
  FROM interior_requisition_master
  WHERE id = NEW.requisition_id
  LIMIT 1;

  IF v_req_company IS NULL OR v_req_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Status log company_id must match requisition company_id';
  END IF;

  IF NEW.changed_by_user_id IS NOT NULL THEN
    SELECT company_id INTO v_user_company
    FROM users WHERE id = NEW.changed_by_user_id LIMIT 1;
    IF v_user_company IS NULL OR v_user_company <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'changed_by_user_id must belong to same company';
    END IF;
  END IF;

  IF NEW.to_status NOT IN ('Draft','Submitted','InReview','Quoted','Approved','Closed','Declined') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid to_status';
  END IF;

  IF NEW.from_status IS NOT NULL AND NEW.from_status NOT IN ('Draft','Submitted','InReview','Quoted','Approved','Closed','Declined') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid from_status';
  END IF;
END$$

DELIMITER ;

-- End
