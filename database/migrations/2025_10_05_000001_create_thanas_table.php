<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thanas', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Foreign key to districts (you already have this table)
            $table->unsignedBigInteger('district_id');

            // Human-visible fields
            $table->string('name', 191);
            $table->unsignedSmallInteger('thana_no')->nullable();   // auto within district (01, 02, ...)
            $table->string('short_code', 16)->nullable();            // e.g., DIST_SHORT + thana_no (DHK0101)

            // Lifecycle / flags (mirrors your style)
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes & Constraints (defensive)
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete();

            // Prevent same-name reuse within a district
            $table->unique(['district_id', 'name'], 'uq_thanas_name_per_dist');

            // Prevent number reuse within a district
            $table->unique(['district_id', 'thana_no'], 'uq_thanas_no_per_dist');

            // Global uniqueness for short_code
            $table->unique('short_code', 'uq_thanas_code');

            // Useful lookups
            $table->index(['district_id'], 'ix_thanas_district');
            $table->index(['status'], 'ix_thanas_status');
        });

        // ---------- TRIGGERS ----------
        // BEFORE INSERT: compute thana_no (next per district) and short_code if not provided
        DB::unprepared(<<<'SQL'
        CREATE TRIGGER trg_thanas_bi
        BEFORE INSERT ON thanas
        FOR EACH ROW
        BEGIN
            DECLARE next_no INT;

            -- Assign thana_no if NULL
            IF NEW.thana_no IS NULL THEN
                SELECT COALESCE(MAX(t.thana_no), 0) + 1
                  INTO next_no
                  FROM thanas t
                 WHERE t.district_id = NEW.district_id;
                SET NEW.thana_no = next_no;
            END IF;

            -- Assign short_code if NULL => <district.short_code><2-digit thana_no>
            IF NEW.short_code IS NULL THEN
                SELECT d.short_code
                  INTO @dsc
                  FROM districts d
                 WHERE d.id = NEW.district_id
                 LIMIT 1;

                SET NEW.short_code = CONCAT(@dsc, LPAD(NEW.thana_no, 2, '0'));
            END IF;
        END
        SQL);

        // BEFORE UPDATE: if district or thana_no changes, recompute short_code (to keep it aligned)
        DB::unprepared(<<<'SQL'
        CREATE TRIGGER trg_thanas_bu
        BEFORE UPDATE ON thanas
        FOR EACH ROW
        BEGIN
            IF (NEW.district_id <> OLD.district_id) OR (NEW.thana_no <> OLD.thana_no) OR (NEW.short_code IS NULL) THEN
                SELECT d.short_code
                  INTO @dsc
                  FROM districts d
                 WHERE d.id = NEW.district_id
                 LIMIT 1;
                SET NEW.short_code = CONCAT(@dsc, LPAD(NEW.thana_no, 2, '0'));
            END IF;
        END
        SQL);

        // Optional: if you ever change a district's short_code, cascade-refresh all its thanas
        // (Matches how you handled upazilas in your DB.)
        DB::unprepared(<<<'SQL'
        CREATE TRIGGER trg_districts_au_refresh_thanas
        AFTER UPDATE ON districts
        FOR EACH ROW
        BEGIN
            IF NEW.short_code <> OLD.short_code THEN
                UPDATE thanas t
                   SET t.short_code = CONCAT(NEW.short_code, LPAD(t.thana_no, 2, '0'))
                 WHERE t.district_id = NEW.id;
            END IF;
        END
        SQL);
    }

    public function down(): void
    {
        // Drop triggers first (if your MySQL/MariaDB requires)
        DB::unprepared("DROP TRIGGER IF EXISTS trg_thanas_bi;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_thanas_bu;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_districts_au_refresh_thanas;");

        Schema::dropIfExists('thanas');
    }
};
