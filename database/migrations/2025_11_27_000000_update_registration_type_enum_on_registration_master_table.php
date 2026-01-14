<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE registration_master
            MODIFY COLUMN registration_type ENUM(
                'client',
                'company_officer',
                'professional',
                'entrepreneur',
                'enterprise_client'
            ) NOT NULL
            COMMENT 'Set at start from UI selection'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE registration_master
            MODIFY COLUMN registration_type ENUM(
                'client',
                'company_officer',
                'professional',
                'entrepreneur'
            ) NOT NULL
            COMMENT 'Set at start from UI selection'
        ");
    }
};
