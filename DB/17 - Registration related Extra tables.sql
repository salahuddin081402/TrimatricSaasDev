/* ========================================================================
   Schema additions for tasking, skills, training, jobs, software expertise,
   and registration key/OTP — NO soft-deletes on new tables.
   All tables include: status, created_by, updated_by, created_at, updated_at.
   Engine/collation kept consistent with existing schema.
   ======================================================================== */

START TRANSACTION;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

/* ------------------------------------------------------------------------
   A) Tasks_Param
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Tasks_Param` (
  `Task_Param_ID`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Task_Param_Name`    VARCHAR(150)    NOT NULL,
  `Module`             VARCHAR(120)    NOT NULL,
  `Type`               ENUM('Inputter','Approver') NOT NULL,
  `Is_Client_Approval_Required` ENUM('Y','N') NOT NULL DEFAULT 'N',
  `status`             TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=Active,0=Inactive',
  `created_by`         BIGINT UNSIGNED NULL,
  `updated_by`         BIGINT UNSIGNED NULL,
  `created_at`         TIMESTAMP NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`Task_Param_ID`),
  UNIQUE KEY `uq_task_param_name_module` (`Task_Param_Name`,`Module`),
  KEY `idx_tasks_param_type` (`Type`),
  KEY `idx_tasks_param_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   B) skills (global list)
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `skills` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `skill`              VARCHAR(150)    NOT NULL,
  `status`             TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`         BIGINT UNSIGNED NULL,
  `updated_by`         BIGINT UNSIGNED NULL,
  `created_at`         TIMESTAMP NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_skill` (`skill`),
  KEY `idx_skill_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   C) Professions (global list)
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Professions` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profession`         VARCHAR(150)    NOT NULL,
  `status`             TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`         BIGINT UNSIGNED NULL,
  `updated_by`         BIGINT UNSIGNED NULL,
  `created_at`         TIMESTAMP NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profession` (`profession`),
  KEY `idx_profession_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   D) Software_List (global list)
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Software_List` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `software_name`      VARCHAR(150)    NOT NULL,
  `status`             TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`         BIGINT UNSIGNED NULL,
  `updated_by`         BIGINT UNSIGNED NULL,
  `created_at`         TIMESTAMP NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_software_name` (`software_name`),
  KEY `idx_software_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   K) Company_Reg_Keys (per-company registration keys)
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Company_Reg_Keys` (
  `Company_id`         BIGINT UNSIGNED NOT NULL COMMENT 'FK companies.id',
  `reg_key`            VARCHAR(255)    NOT NULL,
  `status`             TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`         BIGINT UNSIGNED NULL,
  `updated_by`         BIGINT UNSIGNED NULL,
  `created_at`         TIMESTAMP NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`Company_id`,`reg_key`),
  KEY `idx_crk_status` (`status`),
  CONSTRAINT `fk_crk_company`
    FOREIGN KEY (`Company_id`) REFERENCES `companies` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   E) Training_Category (scoped to company)
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Training_Category` (
  `Company_id`             BIGINT UNSIGNED NOT NULL COMMENT 'FK companies.id',
  `Training_Category_Id`   BIGINT UNSIGNED NOT NULL,
  `Training_Category_Name` VARCHAR(150)    NOT NULL,
  `status`                 TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`             BIGINT UNSIGNED NULL,
  `updated_by`             BIGINT UNSIGNED NULL,
  `created_at`             TIMESTAMP NULL DEFAULT NULL,
  `updated_at`             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`Company_id`,`Training_Category_Id`),
  UNIQUE KEY `uq_company_category_name` (`Company_id`,`Training_Category_Name`),
  KEY `idx_tc_status` (`status`),
  CONSTRAINT `fk_tc_company`
    FOREIGN KEY (`Company_id`) REFERENCES `companies` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   F) Training_list (scoped to company + category)
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Training_list` (
  `Company_id`           BIGINT UNSIGNED NOT NULL,
  `Training_Category_Id` BIGINT UNSIGNED NOT NULL,
  `Training_ID`          BIGINT UNSIGNED NOT NULL,
  `Training_Name`        VARCHAR(180)    NOT NULL,
  `Description`          VARCHAR(500)    NULL,
  `status`               TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`           BIGINT UNSIGNED NULL,
  `updated_by`           BIGINT UNSIGNED NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`Company_id`,`Training_Category_Id`,`Training_ID`),
  UNIQUE KEY `uq_company_training_name` (`Company_id`,`Training_Name`),
  KEY `idx_tl_status` (`status`),
  CONSTRAINT `fk_tl_company_category`
    FOREIGN KEY (`Company_id`,`Training_Category_Id`)
    REFERENCES `Training_Category` (`Company_id`,`Training_Category_Id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   G) Job_Experiences (child of registration_master)
      Note: user_id removed (redundant). Keep only (Company_id, registration_id).
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Job_Experiences` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Company_id`        BIGINT UNSIGNED NOT NULL,
  `registration_id`   BIGINT UNSIGNED NOT NULL,
  `Employer`          VARCHAR(180)    NOT NULL,
  `Job_title`         VARCHAR(150)    NOT NULL,
  `Joining_date`      DATE            NOT NULL,
  `End_date`          DATE            NULL,
  `is_present_job`    ENUM('Y','N')   NOT NULL DEFAULT 'N',
  `status`            TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`        BIGINT UNSIGNED NULL,
  `updated_by`        BIGINT UNSIGNED NULL,
  `created_at`        TIMESTAMP NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_je_company_reg` (`Company_id`,`registration_id`),
  KEY `idx_je_present` (`is_present_job`),
  KEY `idx_je_status` (`status`),
  CONSTRAINT `fk_je_company`
    FOREIGN KEY (`Company_id`)      REFERENCES `companies` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_je_registration`
    FOREIGN KEY (`registration_id`) REFERENCES `registration_master` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   H) Expertise_on_Softwares (per registration & software)
      Note: user_id removed (redundant).
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Expertise_on_Softwares` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Company_id`          BIGINT UNSIGNED NOT NULL,
  `registration_id`     BIGINT UNSIGNED NOT NULL,
  `expert_on_software`  BIGINT UNSIGNED NOT NULL COMMENT 'FK Software_List.id',
  `experience_in_years` DECIMAL(4,1)   NOT NULL DEFAULT 0.0,
  `status`              TINYINT(1)     NOT NULL DEFAULT 1,
  `created_by`          BIGINT UNSIGNED NULL,
  `updated_by`          BIGINT UNSIGNED NULL,
  `created_at`          TIMESTAMP NULL DEFAULT NULL,
  `updated_at`          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reg_software` (`registration_id`,`expert_on_software`),
  KEY `idx_eos_company` (`Company_id`),
  KEY `idx_eos_status` (`status`),
  CONSTRAINT `fk_eos_company`
    FOREIGN KEY (`Company_id`)         REFERENCES `companies` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_eos_registration`
    FOREIGN KEY (`registration_id`)    REFERENCES `registration_master` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_eos_software`
    FOREIGN KEY (`expert_on_software`) REFERENCES `Software_List` (`id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   I) Preffered_Area_of_Job (map registration -> Task Param)
      Note: spelling kept as requested.
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Preffered_Area_of_Job` (
  `Company_id`       BIGINT UNSIGNED NOT NULL,
  `registration_id`  BIGINT UNSIGNED NOT NULL,
  `Task_Param_ID`    BIGINT UNSIGNED NOT NULL,
  `status`           TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`       BIGINT UNSIGNED NULL,
  `updated_by`       BIGINT UNSIGNED NULL,
  `created_at`       TIMESTAMP NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`Company_id`,`registration_id`,`Task_Param_ID`),
  KEY `idx_paj_status` (`status`),
  CONSTRAINT `fk_paj_company`
    FOREIGN KEY (`Company_id`)      REFERENCES `companies` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_paj_registration`
    FOREIGN KEY (`registration_id`) REFERENCES `registration_master` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_paj_taskparam`
    FOREIGN KEY (`Task_Param_ID`)   REFERENCES `Tasks_Param` (`Task_Param_ID`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   J) Training_Required (map registration -> company training)
   ------------------------------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `Training_Required` (
  `Company_id`           BIGINT UNSIGNED NOT NULL,
  `registration_id`      BIGINT UNSIGNED NOT NULL,
  `Training_Category_Id` BIGINT UNSIGNED NOT NULL,
  `Training_ID`          BIGINT UNSIGNED NOT NULL,
  `status`               TINYINT(1)      NOT NULL DEFAULT 1,
  `created_by`           BIGINT UNSIGNED NULL,
  `updated_by`           BIGINT UNSIGNED NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`Company_id`,`registration_id`,`Training_Category_Id`,`Training_ID`),
  KEY `idx_tr_status` (`status`),
  CONSTRAINT `fk_tr_company`
    FOREIGN KEY (`Company_id`) REFERENCES `companies` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_tr_registration`
    FOREIGN KEY (`registration_id`) REFERENCES `registration_master` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_tr_training`
    FOREIGN KEY (`Company_id`,`Training_Category_Id`,`Training_ID`)
    REFERENCES `Training_list` (`Company_id`,`Training_Category_Id`,`Training_ID`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   L) Registration_OTP
   ------------------------------------------------------------------------ */
-- Registration_OTP: allow multiple historical rows but only one ACTIVE row per (Company_Id, reg_key, phone)
CREATE TABLE IF NOT EXISTS `Registration_OTP` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Company_Id`     BIGINT UNSIGNED NOT NULL,
  `reg_key`        VARCHAR(255)    NOT NULL,
  `phone`          VARCHAR(30)     NOT NULL,
  `OTP`            VARCHAR(10)     NOT NULL,
  `status`         TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=Active/Valid, 0=Used/Expired/Invalid',

  -- Helper column: becomes 1 only when status=1, otherwise NULL.
  -- NULLs do not collide under UNIQUE, so this enforces ONE active row per (Company_Id, reg_key, phone)
  `status_active`  TINYINT(1)
                   GENERATED ALWAYS AS (IF(`status` = 1, 1, NULL)) STORED
                   COMMENT 'Generated: 1 when active; NULL otherwise (for unique-on-active constraint)',

  `created_by`     BIGINT UNSIGNED NULL,
  `updated_by`     BIGINT UNSIGNED NULL,
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Lookups / filters
  KEY `idx_rotp_company` (`Company_Id`),
  KEY `idx_rotp_status` (`status`),
  KEY `idx_rotp_lookup` (`Company_Id`, `reg_key`, `phone`, `created_at`),

  -- Enforce: at most ONE active row per (Company_Id, reg_key, phone)
  UNIQUE KEY `uq_rotp_one_active` (`Company_Id`, `reg_key`, `phone`, `status_active`),

  -- FKs
  CONSTRAINT `fk_rotp_company`
    FOREIGN KEY (`Company_Id`) REFERENCES `companies` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_rotp_company_reg_key`
    FOREIGN KEY (`Company_Id`,`reg_key`) REFERENCES `Company_Reg_Keys` (`Company_id`,`reg_key`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------------------
   M) Alter existing table: registration_master
      - add Employee_ID, Reg_Key, Photo, Profession
      - wire FKs: (Company_id, Reg_Key) -> Company_Reg_Keys, Profession -> Professions.id
   ------------------------------------------------------------------------ */
/* === Add new columns (Employee_ID, Reg_Key, Photo, Profession, NID & NID Photos) + indexes & unique === */
/* ===== Final ALTER for registration_master ===== */
/* ===== Final ALTER for registration_master (applied) ===== */

ALTER TABLE `registration_master`
  /* New columns */
  ADD COLUMN `Employee_ID`             VARCHAR(60)  NULL AFTER `user_id`,
  ADD COLUMN `Reg_Key`                 VARCHAR(255) NULL AFTER `Employee_ID`,
  ADD COLUMN `Photo`                   VARCHAR(255) NULL AFTER `notes`,
  ADD COLUMN `Profession`              BIGINT UNSIGNED NULL AFTER `Photo`,
  ADD COLUMN `NID`                     VARCHAR(17)  NULL AFTER `Profession`,
  ADD COLUMN `NID_Photo_Front_Page`    VARCHAR(255) NULL AFTER `NID`,
  ADD COLUMN `NID_Photo_Back_Page`     VARCHAR(255) NULL AFTER `NID_Photo_Front_Page`,

  /* Indexes */
  ADD KEY `idx_reg_employee_id` (`Employee_ID`),
  ADD KEY `idx_reg_reg_key`     (`Reg_Key`),
  ADD KEY `idx_reg_profession`  (`Profession`),

  /* Uniqueness per company */
  ADD UNIQUE KEY `uq_reg_company_nid` (`company_id`, `NID`);

-- Foreign keys added in separate statements to ensure columns exist:
ALTER TABLE `registration_master`
  ADD CONSTRAINT `fk_reg_company_regkey`
    FOREIGN KEY (`company_id`, `Reg_Key`)
    REFERENCES `Company_Reg_Keys` (`Company_id`, `reg_key`)
    ON DELETE RESTRICT;   -- ← change explained below

ALTER TABLE `registration_master`
  ADD CONSTRAINT `fk_reg_profession`
    FOREIGN KEY (`Profession`) REFERENCES `Professions` (`id`)
    ON DELETE SET NULL;

-- Data quality: NID must be 10, 13, or 17 digits (or NULL)
ALTER TABLE `registration_master`
  ADD CONSTRAINT `chk_reg_nid_len_digits`
  CHECK (
    `NID` IS NULL
    OR (
      CHAR_LENGTH(`NID`) IN (10, 13, 17)
      AND `NID` REGEXP '^[0-9]+$'
    )
  );


/* ======================================================================
   Education_Background
   - Child of registration_master
   - One row per Education_Level per (Company_id, registration_id)
   - No soft delete; includes audit/status columns
   ====================================================================== */
/* ======================================================================
   Education_Background — child of registration_master
   - One row per Education_Level per (Company_id, registration_id)
   - No soft delete; includes audit/status columns
   - YEAR-not-in-future enforced via triggers (CURDATE() not allowed in CHECK)
   ====================================================================== */

START TRANSACTION;
SET NAMES utf8mb4;

-- Drop safely if you want to re-run this script


-- ==========================================================
-- Recreate `education_background` to use `degrees` as FK
-- Compatible with MySQL 8.x / MariaDB 10.4+
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop triggers first if they exist (to avoid dependency errors)
DROP TRIGGER IF EXISTS `trg_eb_year_not_future_bi`;
DROP TRIGGER IF EXISTS `trg_eb_year_not_future_bu`;

-- Drop old table if it exists
DROP TABLE IF EXISTS `education_background`;

-- ----------------------------------------------------------
-- CREATE TABLE
-- ----------------------------------------------------------
CREATE TABLE `education_background` (
  `id`                 BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Company_id`         BIGINT(20) UNSIGNED NOT NULL COMMENT 'FK companies.id',
  `registration_id`    BIGINT(20) UNSIGNED NOT NULL COMMENT 'FK registration_master.id',

  -- Replaces previous enum Education_Level
  `degree_id`          BIGINT(20) UNSIGNED NOT NULL COMMENT 'FK degrees.id',

  `Institution`        VARCHAR(180) NOT NULL,
  `Passing_Year`       YEAR(4)      NOT NULL,
  `Result_Type`        ENUM('Grade','Score') NOT NULL,
  `obtained_grade_or_score` VARCHAR(20) NOT NULL,
  `Out_of`             INT(10) UNSIGNED NOT NULL,

  `status`             TINYINT(4)   NOT NULL DEFAULT 1 COMMENT '1=Active,0=Inactive',
  `created_by`         BIGINT(20) UNSIGNED DEFAULT NULL,
  `updated_by`         BIGINT(20) UNSIGNED DEFAULT NULL,
  `created_at`         TIMESTAMP NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP NULL DEFAULT NULL,

  PRIMARY KEY (`id`),

  -- Uniqueness: one row per company/registration/degree
  UNIQUE KEY `uq_eb_company_reg_degree` (`Company_id`,`registration_id`,`degree_id`),

  -- Helpful secondary indexes
  KEY `idx_eb_company_reg` (`Company_id`,`registration_id`),
  KEY `idx_eb_status`      (`status`),
  KEY `idx_eb_year`        (`Passing_Year`),
  KEY `idx_eb_degree`      (`degree_id`),
  KEY `idx_eb_result_type` (`Result_Type`),

  -- Foreign Keys
  CONSTRAINT `fk_eb_company`
    FOREIGN KEY (`Company_id`) REFERENCES `companies` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_eb_registration`
    FOREIGN KEY (`registration_id`) REFERENCES `registration_master` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_eb_degree`
    FOREIGN KEY (`degree_id`) REFERENCES `degrees` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- TRIGGERS: guard Passing_Year against future years
-- ----------------------------------------------------------
DELIMITER $$

CREATE TRIGGER `trg_eb_year_not_future_bi`
BEFORE INSERT ON `education_background`
FOR EACH ROW
BEGIN
  IF NEW.Passing_Year > YEAR(CURDATE()) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Passing_Year cannot be in the future';
  END IF;
END$$

CREATE TRIGGER `trg_eb_year_not_future_bu`
BEFORE UPDATE ON `education_background`
FOR EACH ROW
BEGIN
  IF NEW.Passing_Year > YEAR(CURDATE()) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Passing_Year cannot be in the future';
  END IF;
END$$

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;


/* ======================================================================
   Person_skills — child of registration_master
   - Maps a person (registration) to a skill within a company
   - No soft delete; includes audit/status columns
   ====================================================================== */
CREATE TABLE IF NOT EXISTS `Person_skills` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `Company_id`       BIGINT UNSIGNED NOT NULL COMMENT 'FK companies.id',
  `registration_id`  BIGINT UNSIGNED NOT NULL COMMENT 'FK registration_master.id',
  `skill`            BIGINT UNSIGNED NOT NULL COMMENT 'FK skills.id',

  /* Project-standard audit fields */
  `status`           TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=Active,0=Inactive',
  `created_by`       BIGINT UNSIGNED NULL,
  `updated_by`       BIGINT UNSIGNED NULL,
  `created_at`       TIMESTAMP NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP NULL DEFAULT NULL,

  PRIMARY KEY (`id`),

  /* One row per (Company, Registration, Skill) */
  UNIQUE KEY `uq_ps_company_reg_skill` (`Company_id`,`registration_id`,`skill`),

  /* Helpful lookups */
  KEY `idx_ps_company_reg` (`Company_id`,`registration_id`),
  KEY `idx_ps_skill` (`skill`),
  KEY `idx_ps_status` (`status`),

  /* Foreign keys */
  CONSTRAINT `fk_ps_company`
    FOREIGN KEY (`Company_id`)      REFERENCES `companies` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_ps_registration`
    FOREIGN KEY (`registration_id`) REFERENCES `registration_master` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_ps_skill`
    FOREIGN KEY (`skill`)           REFERENCES `skills` (`id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
