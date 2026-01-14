<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add company_id column + FK to all 6 parameter tables.
     * This is written to be safe even if some columns were already added
     * during a previous failed attempt.
     */
    public function up(): void
    {
        $tables = [
            'cr_project_types',
            'cr_project_subtypes',
            'cr_spaces',
            'cr_item_categories',
            'cr_item_subcategories',
            'cr_products',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue; // table missing – skip safely
            }

            if (!Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Add company_id after id (logical intent; physical position not critical)
                    $table->unsignedBigInteger('company_id')
                        ->nullable()
                        ->after('id');

                    // FK to companies.id
                    $table->foreign('company_id', "{$tableName}_company_id_foreign")
                        ->references('id')
                        ->on('companies')
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                });
            }
        }

        // NOTE (for later):
        // We are intentionally NOT touching existing UNIQUE indexes on code/name now
        // to avoid further migration failures and regressions.
        // If/when you want to change uniqueness to (company_id, code) per table,
        // we will do that in a separate, carefully tested migration.
    }

    /**
     * Best-effort rollback: drop FK + company_id if present.
     * Wrapped in try/catch to avoid crashes if constraints are missing.
     */
    public function down(): void
    {
        $tables = [
            'cr_project_types',
            'cr_project_subtypes',
            'cr_spaces',
            'cr_item_categories',
            'cr_item_subcategories',
            'cr_products',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Try dropping FK by explicit name first
                try {
                    $table->dropForeign("{$tableName}_company_id_foreign");
                } catch (\Throwable $e) {
                    // If that fails, try dropping by column signature
                    try {
                        $table->dropForeign([$tableName . '_company_id_foreign']);
                    } catch (\Throwable $e2) {
                        try {
                            $table->dropForeign(['company_id']);
                        } catch (\Throwable $e3) {
                            // Ignore – FK might not exist, that's acceptable in rollback
                        }
                    }
                }

                // Finally drop the column itself
                try {
                    $table->dropColumn('company_id');
                } catch (\Throwable $e) {
                    // Ignore – if column already gone, nothing to do
                }
            });
        }
    }
};
