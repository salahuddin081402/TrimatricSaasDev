<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // DIVISIONS: add one Division Admin
        Schema::table('divisions', function (Blueprint $table) {
            if (!Schema::hasColumn('divisions', 'division_admin_user_id')) {
                $table->foreignId('division_admin_user_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete(); // ON DELETE SET NULL
            }
        });

        // DISTRICTS: add one District Admin
        Schema::table('districts', function (Blueprint $table) {
            if (!Schema::hasColumn('districts', 'district_admin_user_id')) {
                $table->foreignId('district_admin_user_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete(); // ON DELETE SET NULL
            }
        });
    }

    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            if (Schema::hasColumn('districts', 'district_admin_user_id')) {
                $table->dropForeign(['district_admin_user_id']);
                $table->dropColumn('district_admin_user_id');
            }
        });

        Schema::table('divisions', function (Blueprint $table) {
            if (Schema::hasColumn('divisions', 'division_admin_user_id')) {
                $table->dropForeign(['division_admin_user_id']);
                $table->dropColumn('division_admin_user_id');
            }
        });
    }
};
