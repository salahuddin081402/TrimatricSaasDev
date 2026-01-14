/* ============================================================
   Batch SMS / Email Delivery Log (Tenant-aware)
   Target DB  : MySQL 8+
   Engine     : InnoDB
   Charset    : utf8mb4
   Notes:
   - Includes company_id FK -> companies(id)
   - batch_no is NOT forced globally unique (safe for multi-tenant)
   - message_uid is globally unique per log row (professional audit key)
   ============================================================ */

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS communication_message_logs;

CREATE TABLE communication_message_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    /* Tenant */
    company_id BIGINT UNSIGNED NOT NULL COMMENT 'FK -> companies.id',

    /* Professional unique identifier for this log row */
    message_uid CHAR(36) NOT NULL DEFAULT (UUID()) COMMENT 'Unique per message attempt/log row',

    /* Batch Tracking */
    batch_no CHAR(36) NOT NULL COMMENT 'UUID batch number (same for one campaign/batch)',

    /* Message Type */
    message_type ENUM('SMS','EMAIL') NOT NULL COMMENT 'Delivery channel',

    /* Recipient Info */
    recipient_name VARCHAR(150) NULL,
    recipient_phone VARCHAR(20) NULL COMMENT 'Used for SMS',
    recipient_email VARCHAR(190) NULL COMMENT 'Used for Email',

    /* Message Content Snapshot */
    message_subject VARCHAR(255) NULL COMMENT 'Email subject / SMS short title',
    message_body TEXT NULL COMMENT 'Actual content sent',

    /* Delivery Result */
    status ENUM('SUCCESS','FAILED') NOT NULL DEFAULT 'SUCCESS',
    failure_reason TEXT NULL COMMENT 'Failure reason if status = FAILED',

    /* Gateway / Transport Info */
    gateway_name VARCHAR(100) NULL COMMENT 'e.g. MRAM, SMTP',
    gateway_response TEXT NULL COMMENT 'Raw gateway/mailer response (if any)',

    /* Audit */
    sent_at DATETIME NULL COMMENT 'Actual send time',
    sender_ip VARCHAR(45) NULL COMMENT 'IPv4/IPv6',
    created_by BIGINT UNSIGNED NULL COMMENT 'User ID who triggered the send/batch',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    /* Unique key (professional, safe for retries and re-sends) */
    UNIQUE KEY uq_company_message_uid (company_id, message_uid),

    /* Indexes for reporting & batch progress */
    KEY idx_company_batch (company_id, batch_no),
    KEY idx_company_type (company_id, message_type),
    KEY idx_company_status (company_id, status),
    KEY idx_company_sent_at (company_id, sent_at),
    KEY idx_company_phone (company_id, recipient_phone),
    KEY idx_company_email (company_id, recipient_email),

    CONSTRAINT fk_cml_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
