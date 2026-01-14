<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) Add the 'kind' column with default so existing rows auto-fill as 'UPAZILA'
        Schema::table('upazilas', function (Blueprint $table) {
            // Adjust the ->after('short_code') position if your platform ignores it (SQLite)
            $table->enum('kind', ['UPAZILA', 'CITY_CORPORATION', 'POUROSHAVA'])
                  ->default('UPAZILA')
                  ->after('short_code');
        });

        // 2) Safety backfill (for hosts that don't apply default to existing rows)
        DB::table('upazilas')->whereNull('kind')->update(['kind' => 'UPAZILA']);

        // 3) Add a composite index to speed up district/kind/name filtering for your TomSelect
        Schema::table('upazilas', function (Blueprint $table) {
            $table->index(['district_id', 'kind', 'name'], 'idx_upazilas_district_kind_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop helper index first (if it exists), then drop the column
        Schema::table('upazilas', function (Blueprint $table) {
            // Index name must match the one created in up()
            $table->dropIndex('idx_upazilas_district_kind_name');
            $table->dropColumn('kind');
        });
    }
};
