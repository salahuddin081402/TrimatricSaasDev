<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'entrepreneur' to the ENUM
        DB::statement("
            ALTER TABLE `registration_master`
            MODIFY `registration_type`
            ENUM('client','company_officer','professional','entrepreneur')
            NOT NULL
            COMMENT 'Set at start from UI selection'
        ");
    }

    public function down(): void
    {
        // Rollback to the original ENUM (will fail if rows contain 'entrepreneur')
        DB::statement("
            ALTER TABLE `registration_master`
            MODIFY `registration_type`
            ENUM('client','company_officer','professional')
            NOT NULL
            COMMENT 'Set at start from UI selection'
        ");
    }
};
