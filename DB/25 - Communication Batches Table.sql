/* ============================================================
   Communication Batches (Campaign Master)
   ============================================================
   - One row per bulk send campaign
   - Linked to communication_message_logs via batch_no
   - company_id = tenant isolation
   ============================================================ */

CREATE TABLE communication_batches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    /* Tenant */
    company_id BIGINT UNSIGNED NOT NULL COMMENT 'FK -> companies.id',

    /* Batch Identity */
    batch_no VARCHAR(40) NOT NULL COMMENT 'Human readable batch no e.g. BATCH-YYYYMMDD-0001',

    /* Scope & Channel */
    channel ENUM('SMS', 'EMAIL', 'BOTH') NOT NULL COMMENT 'Delivery channel(s)',
    target_group VARCHAR(50) NOT NULL COMMENT 'client_entrepreneur | cluster | division_admin | etc',

    /* Filter Snapshot (JSON, immutable) */
    filter_payload JSON NOT NULL COMMENT 'All filters used to generate recipients',

    /* Message Snapshot */
    message_subject VARCHAR(255) NULL,
    message_body TEXT NOT NULL,
    extra_message TEXT NULL,

    /* Batch Stats */
    total_recipients INT UNSIGNED DEFAULT 0,
    processed_count INT UNSIGNED DEFAULT 0,
    success_count INT UNSIGNED DEFAULT 0,
    failed_count INT UNSIGNED DEFAULT 0,

    /* Status */
    status ENUM('CREATED','RUNNING','COMPLETED','FAILED','CANCELLED')
        NOT NULL DEFAULT 'CREATED',

    /* Control */
    retry_limit TINYINT UNSIGNED DEFAULT 2,
    cancelled_at DATETIME NULL,

    /* Audit */
    created_by BIGINT UNSIGNED NOT NULL COMMENT 'currentUserId()',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    /* Keys */
    PRIMARY KEY (id),
    UNIQUE KEY uq_company_batch (company_id, batch_no),

    KEY idx_company_status (company_id, status),
    KEY idx_company_created (company_id, created_at),

    /* FK */
    CONSTRAINT fk_cb_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
