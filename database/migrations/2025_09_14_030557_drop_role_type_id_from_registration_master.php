<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('registration_master', function (Blueprint $table) {
            // Drop FK if it exists (named in earlier scripts as fk_reg_role_type)
            try {
                $table->dropForeign('fk_reg_role_type');
            } catch (\Throwable $e) {
                // ignore if missing
            }

            // Drop index if it exists (named earlier as idx_reg_role_type_id)
            try {
                $table->dropIndex('idx_reg_role_type_id');
            } catch (\Throwable $e) {
                // ignore if missing
            }

            // Finally drop the column
            if (Schema::hasColumn('registration_master', 'role_type_id')) {
                $table->dropColumn('role_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('registration_master', function (Blueprint $table) {
            // Recreate the column (nullable to allow rollback on non-empty tables)
            if (!Schema::hasColumn('registration_master', 'role_type_id')) {
                $table->unsignedBigInteger('role_type_id')
                      ->nullable()
                      ->after('registration_type')
                      ->comment('FK role_types.id (restored by rollback)');
            }
        });

        // Recreate index & FK (use same names as before)
        Schema::table('registration_master', function (Blueprint $table) {
            try {
                $table->index('role_type_id', 'idx_reg_role_type_id');
            } catch (\Throwable $e) {}

            try {
                $table->foreign('role_type_id', 'fk_reg_role_type')
                      ->references('id')->on('role_types')
                      ->restrictOnDelete();
            } catch (\Throwable $e) {}
        });
    }
};
