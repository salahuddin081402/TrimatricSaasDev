<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_district_admins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');

            // Tenure / audit
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('deactivated_at')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->unsignedBigInteger('deactivated_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // FKs
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('activated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deactivated_by')->references('id')->on('users')->onDelete('set null');

            // Uniques / indexes
            $table->unique(['company_id', 'district_id', 'user_id'], 'uq_cxta_user_area');
            $table->index(['company_id', 'district_id', 'status'], 'idx_cxta_area_status');
            $table->index(['company_id', 'user_id'], 'idx_cxta_user');
            $table->index('district_id', 'idx_cxta_dist');
            $table->index('status', 'idx_cxta_status');
            $table->index('created_by', 'idx_cxta_created_by');
            $table->index('updated_by', 'idx_cxta_updated_by');
        });

        // Optional CHECK (MySQL 8+)
        DB::unprepared("
            ALTER TABLE company_district_admins
            ADD CONSTRAINT chk_cxta_status CHECK (status IN (0,1))
        ");

        // Triggers
        DB::unprepared(<<<'SQL'
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
END
SQL);

        DB::unprepared(<<<'SQL'
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
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_company_district_admins_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_company_district_admins_bu');

        Schema::dropIfExists('company_district_admins');
    }
};
