-- ============================================
-- Degrees master table (MySQL 8+)
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `degrees` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120)    NOT NULL,        -- Human-friendly full name
  `short_code`  VARCHAR(40)     NOT NULL,        -- Compact code/abbrev (unique)
  `level`       INT             NOT NULL,        -- Sorting level (lower = earlier)
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_degrees_name` (`name`),
  UNIQUE KEY `uq_degrees_short_code` (`short_code`),
  KEY `idx_degrees_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Seed data (ordered from SSC -> PhD)
-- Use INSERT ... ON DUPLICATE KEY UPDATE for idempotency
-- level: 10=Secondary, 12=Higher Secondary, 20-29=Diplomas,
--        30-39=Bachelor, 40=PG Diploma, 50-59=Master,
--        60=MPhil, 70+=Doctorate
-- ============================================

START TRANSACTION;

-- Secondary / Higher Secondary
INSERT INTO `degrees` (`name`,`short_code`,`level`,`is_active`) VALUES
('Secondary School Certificate','SSC',10,1)
ON DUPLICATE KEY UPDATE `level`=VALUES(`level`), `is_active`=VALUES(`is_active`);

INSERT INTO `degrees` (`name`,`short_code`,`level`,`is_active`) VALUES
('Higher Secondary Certificate','HSC',12,1)
ON DUPLICATE KEY UPDATE `level`=VALUES(`level`), `is_active`=VALUES(`is_active`);

-- Diplomas (common in Bangladesh)
INSERT INTO `degrees` (`name`,`short_code`,`level`,`is_active`) VALUES
('Diploma (Polytechnic)','DIP',20,1),
('Diploma in Engineering','DIP-ENG',21,1),
('Diploma in Architecture','DIP-ARCH',22,1),
('Diploma in Civil Engineering','DIP-CIV',23,1),
('Diploma in Electrical Engineering','DIP-EEE',24,1),
('Diploma in Mechanical Engineering','DIP-MECH',25,1),
('Diploma in Computer Technology','DIP-CT',26,1),
('Diploma in Nursing','DIP-NUR',27,1)
ON DUPLICATE KEY UPDATE `level`=VALUES(`level`), `is_active`=VALUES(`is_active`);

-- Bachelor (general & professional)
INSERT INTO `degrees` (`name`,`short_code`,`level`,`is_active`) VALUES
('Bachelor of Arts','BA',30,1),
('Bachelor of Arts (Honours)','BA-HONS',31,1),
('Bachelor of Science','BSc',32,1),
('Bachelor of Science (Honours)','BSc-HONS',33,1),
('Bachelor of Social Science','BSS',34,1),
('Bachelor of Social Science (Honours)','BSS-HONS',35,1),
('Bachelor of Commerce','BCom',36,1),
('Bachelor of Commerce (Honours)','BCom-HONS',37,1),
('Bachelor of Business Administration','BBA',38,1),
('Bachelor of Laws','LLB',39,1),
('Bachelor of Science in Engineering','BSc-Engg',39,1),
('Bachelor of Architecture','BArch',39,1),
('Bachelor of Pharmacy','BPharm',39,1),
('Bachelor of Medicine, Bachelor of Surgery','MBBS',39,1),
('Bachelor of Dental Surgery','BDS',39,1),
('Bachelor of Science in Agriculture','BSc-Ag',39,1),
('Bachelor of Fine Arts','BFA',39,1),
('Bachelor of Education','BEd',39,1),
('Bachelor of Science in Nursing','BSN',39,1),
('Bachelor of Textile Engineering','BTex',39,1),
('Bachelor of Tourism & Hospitality Management','BTHM',39,1),
('Bachelor of Social Work','BSW',39,1)
ON DUPLICATE KEY UPDATE `level`=VALUES(`level`), `is_active`=VALUES(`is_active`);

-- Postgraduate Diploma
INSERT INTO `degrees` (`name`,`short_code`,`level`,`is_active`) VALUES
('Postgraduate Diploma','PGD',40,1)
ON DUPLICATE KEY UPDATE `level`=VALUES(`level`), `is_active`=VALUES(`is_active`);

-- Masters (general & professional)
INSERT INTO `degrees` (`name`,`short_code`,`level`,`is_active`) VALUES
('Master of Arts','MA',50,1),
('Master of Science','MSc',50,1),
('Master of Social Science','MSS',50,1),
('Master of Commerce','MCom',50,1),
('Master of Business Administration','MBA',50,1),
('Master of Laws','LLM',50,1),
('Master of Engineering','MEngg',50,1),
('Master of Architecture','MArch',50,1),
('Master of Pharmacy','MPharm',50,1),
('Master of Public Health','MPH',50,1),
('Master of Education','MEd',50,1),
('Master of Fine Arts','MFA',50,1),
('Master of Science in Agriculture','MSc-Ag',50,1),
('Master of Science in Nursing','MSN',50,1),
('Master of Textile Engineering','MTex',50,1),
('Master of Public Administration','MPA',50,1),
('Master of Dental Surgery','MDS',50,1),
('Master of Surgery (Medical)','MS',50,1)
ON DUPLICATE KEY UPDATE `level`=VALUES(`level`), `is_active`=VALUES(`is_active`);

-- MPhil (pre-doctoral)
INSERT INTO `degrees` (`name`,`short_code`,`level`,`is_active`) VALUES
('Master of Philosophy','MPhil',60,1)
ON DUPLICATE KEY UPDATE `level`=VALUES(`level`), `is_active`=VALUES(`is_active`);

-- Doctorate / terminal
INSERT INTO `degrees` (`name`,`short_code`,`level`,`is_active`) VALUES
('Doctor of Philosophy','PhD',70,1),
('Doctor of Medicine (Postgraduate)','MD',70,1),
('Doctor of Pharmacy','PharmD',70,1),
('Doctor of Science','DSc',75,0)   -- uncommon; included but inactive by default
ON DUPLICATE KEY UPDATE `level`=VALUES(`level`), `is_active`=VALUES(`is_active`);

COMMIT;

SET FOREIGN_KEY_CHECKS=1;
