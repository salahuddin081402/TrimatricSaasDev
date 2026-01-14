<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * File path:
 *   database/migrations/2025_12_21_000001_create_interior_requisition_transactional_tables.php
 *
 * Notes:
 * - Designed for MariaDB 10.4.x / MySQL 8+.
 * - Creates transactional tables for Client Interior Requisition module (does NOT create the 7 parameter tables).
 * - Enforces DB-level integrity using triggers (no app-only trust for key rules and total sqft computation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interior_requisition_master', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');

            $table->unsignedBigInteger('reg_id');         // registration_master.id
            $table->unsignedBigInteger('client_user_id'); // must match registration_master.user_id

            // Step-1 "Project Details" inputs
            $table->string('project_address', 600)->nullable();
            $table->text('project_note')->nullable();

            // Computed from space lines (DB maintained)
            $table->decimal('project_total_sqft', 12, 2)->default(0.00);

            $table->decimal('project_budget', 14, 2)->nullable();
            $table->date('project_eta')->nullable();

            // Internal (future UI)
            $table->text('head_office_remark')->nullable();

            // Step-1 selections
            $table->unsignedBigInteger('project_type_id')->nullable();
            $table->unsignedBigInteger('project_subtype_id')->nullable();

            // Lifecycle
            $table->string('status', 20)->default('Draft');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('closed_at')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();      // nullable by default
            $table->softDeletes();     // deleted_at (nullable)

            // Indexes
            $table->index(['company_id', 'status'], 'idx_irm_company_status');
            $table->index(['company_id', 'reg_id'], 'idx_irm_company_reg');
            $table->index(['company_id', 'client_user_id'], 'idx_irm_company_user');
            $table->index(['company_id', 'project_type_id'], 'idx_irm_company_type');
            $table->index(['company_id', 'project_subtype_id'], 'idx_irm_company_subt');

            // FKs
            $table->foreign('company_id', 'fk_irm_company')
                ->references('id')->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('reg_id', 'fk_irm_reg')
                ->references('id')->on('registration_master')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('client_user_id', 'fk_irm_user')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('created_by', 'fk_irm_created_by')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('updated_by', 'fk_irm_updated_by')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('project_type_id', 'fk_irm_type')
                ->references('id')->on('cr_project_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('project_subtype_id', 'fk_irm_subtype')
                ->references('id')->on('cr_project_subtypes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // CHECK constraints (MySQL 8+ enforces; MariaDB 10.4 enforces)
        DB::statement("ALTER TABLE interior_requisition_master
            ADD CONSTRAINT chk_irm_status
            CHECK (status IN ('Draft','Submitted','InReview','Quoted','Approved','Closed','Declined'))");

        Schema::create('interior_requisition_space_lines', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('requisition_id');

            $table->unsignedBigInteger('space_id');
            $table->unsignedInteger('space_qty')->default(1);
            $table->decimal('space_total_sqft', 12, 2)->default(0.00);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['company_id', 'requisition_id', 'space_id'], 'uq_irsl_req_space');
            $table->index(['requisition_id', 'space_id'], 'idx_irsl_req_space');
            $table->index(['company_id', 'requisition_id'], 'idx_irsl_company_req');

            $table->foreign('company_id', 'fk_irsl_company')
                ->references('id')->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('requisition_id', 'fk_irsl_req')
                ->references('id')->on('interior_requisition_master')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('space_id', 'fk_irsl_space')
                ->references('id')->on('cr_spaces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement("ALTER TABLE interior_requisition_space_lines
            ADD CONSTRAINT chk_irsl_qty CHECK (space_qty >= 1),
            ADD CONSTRAINT chk_irsl_sqft CHECK (space_total_sqft >= 0)");

        Schema::create('interior_requisition_category_lines', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('space_id');
            $table->unsignedBigInteger('category_id');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['company_id', 'requisition_id', 'space_id', 'category_id'], 'uq_ircl');
            $table->index(['requisition_id', 'space_id', 'category_id'], 'idx_ircl_req_space_cat');

            $table->foreign('company_id', 'fk_ircl_company')
                ->references('id')->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('requisition_id', 'fk_ircl_req')
                ->references('id')->on('interior_requisition_master')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('space_id', 'fk_ircl_space')
                ->references('id')->on('cr_spaces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('category_id', 'fk_ircl_category')
                ->references('id')->on('cr_item_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('interior_requisition_subcategory_lines', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('space_id');

            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['company_id', 'requisition_id', 'space_id', 'subcategory_id'], 'uq_irsl2');
            $table->index(['requisition_id', 'space_id', 'category_id', 'subcategory_id'], 'idx_irsl2_req_space_cat_subcat');

            $table->foreign('company_id', 'fk_irsl2_company')
                ->references('id')->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('requisition_id', 'fk_irsl2_req')
                ->references('id')->on('interior_requisition_master')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('space_id', 'fk_irsl2_space')
                ->references('id')->on('cr_spaces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('category_id', 'fk_irsl2_category')
                ->references('id')->on('cr_item_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('subcategory_id', 'fk_irsl2_subcategory')
                ->references('id')->on('cr_item_subcategories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('interior_requisition_product_lines', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('space_id');

            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->unsignedBigInteger('product_id');

            $table->unsignedInteger('qty')->default(1);

            $table->timestamps();

            $table->unique(['company_id', 'requisition_id', 'space_id', 'product_id'], 'uq_irpl');
            $table->index(['requisition_id', 'space_id', 'subcategory_id', 'product_id'], 'idx_irpl_req_space_subcat_product');

            $table->foreign('company_id', 'fk_irpl_company')
                ->references('id')->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('requisition_id', 'fk_irpl_req')
                ->references('id')->on('interior_requisition_master')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('space_id', 'fk_irpl_space')
                ->references('id')->on('cr_spaces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('category_id', 'fk_irpl_category')
                ->references('id')->on('cr_item_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('subcategory_id', 'fk_irpl_subcategory')
                ->references('id')->on('cr_item_subcategories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('product_id', 'fk_irpl_product')
                ->references('id')->on('cr_products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement("ALTER TABLE interior_requisition_product_lines
            ADD CONSTRAINT chk_irpl_qty CHECK (qty >= 1)");

        Schema::create('interior_requisition_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('requisition_id');

            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();
            $table->unsignedBigInteger('uploaded_by_reg_id')->nullable();

            $table->unsignedTinyInteger('step_no')->nullable(); // 1 or 2
            $table->string('note', 300)->nullable();

            $table->string('original_name', 255)->nullable();
            $table->string('file_path', 255); // relative path only
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('file_size_kb')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->dateTime('created_at')->nullable(); // one timestamp only (per your script)

            $table->index(['company_id', 'requisition_id'], 'idx_ira_company_req');
            $table->index(['requisition_id'], 'idx_ira_req');

            $table->foreign('company_id', 'fk_ira_company')
                ->references('id')->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('requisition_id', 'fk_ira_req')
                ->references('id')->on('interior_requisition_master')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('uploaded_by_user_id', 'fk_ira_uploaded_user')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('uploaded_by_reg_id', 'fk_ira_uploaded_reg')
                ->references('id')->on('registration_master')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE interior_requisition_attachments
            ADD CONSTRAINT chk_ira_step CHECK (step_no IS NULL OR step_no IN (1,2))");

        Schema::create('interior_requisition_status_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('requisition_id');

            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('note', 500)->nullable();

            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->dateTime('changed_at')->useCurrent();

            $table->index(['company_id', 'requisition_id'], 'idx_irlog_company_req');
            $table->index(['company_id', 'to_status'], 'idx_irlog_company_to');

            $table->foreign('company_id', 'fk_irlog_company')
                ->references('id')->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('requisition_id', 'fk_irlog_req')
                ->references('id')->on('interior_requisition_master')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('changed_by_user_id', 'fk_irlog_changed_by')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE interior_requisition_status_logs
            ADD CONSTRAINT chk_irlog_status
            CHECK (
              (from_status IS NULL OR from_status IN ('Draft','Submitted','InReview','Quoted','Approved','Closed','Declined'))
              AND (to_status IN ('Draft','Submitted','InReview','Quoted','Approved','Closed','Declined'))
            )");

        // ------------------------------------------------------------
        // TRIGGERS (DB-level integrity; no app-only trust)
        // ------------------------------------------------------------
        $this->dropTriggersIfExist();

        // Master triggers
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_irm_bi
BEFORE INSERT ON interior_requisition_master
FOR EACH ROW
BEGIN
  DECLARE v_reg_company BIGINT UNSIGNED;
  DECLARE v_reg_user BIGINT UNSIGNED;
  DECLARE v_reg_type VARCHAR(50);
  DECLARE v_user_company BIGINT UNSIGNED;

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

  SELECT company_id INTO v_user_company
  FROM users WHERE id = NEW.client_user_id LIMIT 1;

  IF v_user_company IS NULL OR v_user_company <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'client_user_id must belong to same company';
  END IF;

  IF v_reg_type NOT IN ('client','enterprise_client') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only client or enterprise_client can create requisition';
  END IF;

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

  SET NEW.project_total_sqft = 0.00;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_irm_bu
BEFORE UPDATE ON interior_requisition_master
FOR EACH ROW
BEGIN
  DECLARE v_reg_company BIGINT UNSIGNED;
  DECLARE v_reg_user BIGINT UNSIGNED;
  DECLARE v_user_company BIGINT UNSIGNED;

  IF OLD.status IN ('Closed','Declined') THEN
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

  IF NEW.company_id <> OLD.company_id OR NEW.reg_id <> OLD.reg_id OR NEW.client_user_id <> OLD.client_user_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot change company_id/reg_id/client_user_id';
  END IF;

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

  SET NEW.project_total_sqft = OLD.project_total_sqft;
END
SQL);

        // Space line triggers (tenant binding + space ownership + auto recompute total sqft)
        DB::unprepared(<<<'SQL'
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

  IF NOT EXISTS (
    SELECT 1 FROM cr_spaces sp
    WHERE sp.id = NEW.space_id
      AND (sp.company_id IS NULL OR sp.company_id = NEW.company_id)
      AND sp.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid space_id for this company';
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
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'space_id does not belong to requisition project_subtype_id';
  END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
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
END
SQL);

        DB::unprepared(<<<'SQL'
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
END
SQL);

        DB::unprepared(<<<'SQL'
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
END
SQL);

        DB::unprepared(<<<'SQL'
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
END
SQL);

        // Category line triggers (mapping enforcement + immutability)
        DB::unprepared(<<<'SQL'
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

  IF NOT EXISTS (
    SELECT 1 FROM interior_requisition_space_lines sl
    WHERE sl.company_id = NEW.company_id
      AND sl.requisition_id = NEW.requisition_id
      AND sl.space_id = NEW.space_id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category line space_id not selected in Step-1';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM cr_item_categories c
    WHERE c.id = NEW.category_id
      AND (c.company_id IS NULL OR c.company_id = NEW.company_id)
      AND c.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid category_id for this company';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM cr_space_category_mappings m
    WHERE m.company_id = NEW.company_id
      AND m.space_id = NEW.space_id
      AND m.category_id = NEW.category_id
      AND m.is_active = 1
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category not allowed for this space (mapping)';
  END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_ircl_bu
BEFORE UPDATE ON interior_requisition_category_lines
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category lines are immutable; delete+insert instead';
END
SQL);

        // Subcategory line triggers (ownership + immutability)
        DB::unprepared(<<<'SQL'
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
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_irsl2_bu
BEFORE UPDATE ON interior_requisition_subcategory_lines
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Subcategory lines are immutable; delete+insert instead';
END
SQL);

        // Product line triggers (full hierarchy + immutability)
        DB::unprepared(<<<'SQL'
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
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_irpl_bu
BEFORE UPDATE ON interior_requisition_product_lines
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product lines are immutable; delete+insert instead';
END
SQL);

        // Attachment trigger
        DB::unprepared(<<<'SQL'
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
END
SQL);

        // Status log trigger
        DB::unprepared(<<<'SQL'
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
END
SQL);
    }

    public function down(): void
    {
        $this->dropTriggersIfExist();

        Schema::dropIfExists('interior_requisition_status_logs');
        Schema::dropIfExists('interior_requisition_attachments');
        Schema::dropIfExists('interior_requisition_product_lines');
        Schema::dropIfExists('interior_requisition_subcategory_lines');
        Schema::dropIfExists('interior_requisition_category_lines');
        Schema::dropIfExists('interior_requisition_space_lines');
        Schema::dropIfExists('interior_requisition_master');
    }

    private function dropTriggersIfExist(): void
    {
        // Use unprepared so it works in both MySQL and MariaDB.
        DB::unprepared("DROP TRIGGER IF EXISTS trg_irm_bi");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_irm_bu");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_irsl_bi");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_irsl_bu");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_irsl_ai");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_irsl_au");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_irsl_ad");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_ircl_bi");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_ircl_bu");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_irsl2_bi");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_irsl2_bu");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_irpl_bi");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_irpl_bu");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_ira_bi");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_irlog_bi");
    }
};
