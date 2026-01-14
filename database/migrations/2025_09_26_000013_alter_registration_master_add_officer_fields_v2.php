<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Add the composite FK with RESTRICT (no SET NULL because company_id is NOT NULL)
        //    Only if it doesn't already exist
        $fkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME = 'fk_reg_company_regkey'
        ");
        if (!$fkExists) {
            Schema::table('registration_master', function (Blueprint $table) {
                $table->foreign(['company_id', 'reg_key'], 'fk_reg_company_regkey')
                      ->references(['Company_id', 'reg_key'])
                      ->on('Company_Reg_Keys')
                      ->onDelete('restrict'); // <-- fixed here
            });
        }

        // 2) Add FK to Professions if missing
        $fkProfExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME = 'fk_reg_profession'
        ");
        if (!$fkProfExists) {
            Schema::table('registration_master', function (Blueprint $table) {
                $table->foreign('Profession', 'fk_reg_profession')
                      ->references('id')->on('Professions')
                      ->onDelete('set null');
            });
        }

        // 3) Add CHECK constraint for NID if missing (MySQL 8+)
        $chkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'registration_master'
              AND CONSTRAINT_TYPE = 'CHECK'
              AND CONSTRAINT_NAME = 'chk_reg_nid_len_digits'
        ");
        if (!$chkExists) {
            DB::statement("
                ALTER TABLE `registration_master`
                ADD CONSTRAINT `chk_reg_nid_len_digits`
                CHECK (
                    `NID` IS NULL
                    OR (
                        CHAR_LENGTH(`NID`) IN (10, 13, 17)
                        AND `NID` REGEXP '^[0-9]+$'
                    )
                )
            ");
        }
    }

    public function down(): void
    {
        // Drop CHECK (ignore error if not present)
        try {
            DB::statement("ALTER TABLE `registration_master` DROP CHECK `chk_reg_nid_len_digits`");
        } catch (\Throwable $e) {}

        // Drop FKs (ignore error if not present)
        Schema::table('registration_master', function (Blueprint $table) {
            try { $table->dropForeign('fk_reg_profession'); } catch (\Throwable $e) {}
            try { $table->dropForeign('fk_reg_company_regkey'); } catch (\Throwable $e) {}
        });
    }
};
