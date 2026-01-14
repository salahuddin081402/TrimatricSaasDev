<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE = 'registration_master';

    // Existing FK names from your dump
    private const FK_DIVISION = 'fk_reg_division';
    private const FK_DISTRICT = 'fk_reg_district';
    private const FK_UPAZILA  = 'fk_reg_upazila';

    // New FK / index names
    private const COL_THANA   = 'thana_id';
    private const FK_THANA    = 'fk_reg_thana';
    private const IDX_THANA   = 'idx_reg_thana_id';

    public function up(): void
    {
        // 0) Add thana_id if missing (nullable), placed after upazila_id
        if (!Schema::hasColumn(self::TABLE, self::COL_THANA)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unsignedBigInteger(self::COL_THANA)->nullable()->after('upazila_id');
            });
        }

        // 1) Ensure an index exists on thana_id (create if missing)
        if (!$this->indexExists(self::TABLE, self::IDX_THANA)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(self::COL_THANA, self::IDX_THANA);
            });
        }

        // 2) Drop existing geo FKs if they exist (so we can recreate with ON DELETE SET NULL)
        $this->dropForeignIfExists(self::TABLE, self::FK_DIVISION);
        $this->dropForeignIfExists(self::TABLE, self::FK_DISTRICT);
        $this->dropForeignIfExists(self::TABLE, self::FK_UPAZILA);

        // 3) Make all geo columns NULLable using raw SQL (no doctrine/dbal required)
        // Current types are BIGINT(20) UNSIGNED NOT NULL — we switch to NULL
        // Keep comments/defaults minimal to avoid engine-specific failures.
        $this->makeColumnNullable(self::TABLE, 'division_id');
        $this->makeColumnNullable(self::TABLE, 'district_id');
        $this->makeColumnNullable(self::TABLE, 'upazila_id');
        $this->makeColumnNullable(self::TABLE, self::COL_THANA); // safe whether newly added or already nullable

        // 4) Recreate FKs with ON DELETE SET NULL (idempotent add)
        Schema::table(self::TABLE, function (Blueprint $table) {
            // divisions
            $table->foreign('division_id', self::FK_DIVISION)
                  ->references('id')->on('divisions')
                  ->nullOnDelete(); // ON DELETE SET NULL

            // districts
            $table->foreign('district_id', self::FK_DISTRICT)
                  ->references('id')->on('districts')
                  ->nullOnDelete();

            // upazilas
            $table->foreign('upazila_id', self::FK_UPAZILA)
                  ->references('id')->on('upazilas')
                  ->nullOnDelete();

            // thanas (new)
            $table->foreign(self::COL_THANA, self::FK_THANA)
                  ->references('id')->on('thanas')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Reverse order: drop our thana FK & index/column, and drop the SET NULL FKs.
        $this->dropForeignIfExists(self::TABLE, self::FK_THANA);

        if ($this->indexExists(self::TABLE, self::IDX_THANA)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::IDX_THANA);
            });
        }

        if (Schema::hasColumn(self::TABLE, self::COL_THANA)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropColumn(self::COL_THANA);
            });
        }

        // Drop the other FKs we redefined
        $this->dropForeignIfExists(self::TABLE, self::FK_UPAZILA);
        $this->dropForeignIfExists(self::TABLE, self::FK_DISTRICT);
        $this->dropForeignIfExists(self::TABLE, self::FK_DIVISION);

        // (Optional) Recreate original FKs without ON DELETE action.
        // We keep columns nullable to avoid failing down() if NULLs exist.
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->foreign('division_id', self::FK_DIVISION)
                  ->references('id')->on('divisions');
            $table->foreign('district_id', self::FK_DISTRICT)
                  ->references('id')->on('districts');
            $table->foreign('upazila_id', self::FK_UPAZILA)
                  ->references('id')->on('upazilas');
        });
    }

    /* ======================= helpers (no DBAL needed) ======================= */

    /** Make BIGINT UNSIGNED column nullable via raw SQL (MySQL/MariaDB). */
    private function makeColumnNullable(string $table, string $column): void
    {
        if (!Schema::hasColumn($table, $column)) return;

        // Detect current type (assume BIGINT UNSIGNED from your dump)
        // If your column differs, adjust the type here.
        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` BIGINT(20) UNSIGNED NULL',
            $table,
            $column
        ));
    }

    /** Drop a foreign key if it exists (by constraint name). */
    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne("
            SELECT 1
              FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            LIMIT 1
        ", [$table, $constraint]);

        if ($exists) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraint));
        }
    }

    /** Check if a named index exists. */
    private function indexExists(string $table, string $index): bool
    {
        $exists = DB::selectOne("
            SELECT 1
              FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = ?
               AND INDEX_NAME   = ?
            LIMIT 1
        ", [$table, $index]);

        return (bool) $exists;
    }
};
