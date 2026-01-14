<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add phone (required by AuthController)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->after('password');
                // Controller validates unique:users => enforce uniqueness at DB level
                $table->unique('phone', 'uq_users_phone');
            }
        });

        // 2) Make email nullable (controller: nullable|email|unique:users)
        // If you have doctrine/dbal installed, you can use ->change().
        // Without doctrine/dbal, use raw SQL (works on MySQL/MariaDB).
        try {
            Schema::table('users', function (Blueprint $table) {
                // This requires doctrine/dbal in many Laravel setups
                $table->string('email', 150)->nullable()->change();
            });
        } catch (\Throwable $e) {
            // Fallback: raw ALTER (safe and direct)
            DB::statement("ALTER TABLE `users` MODIFY `email` VARCHAR(150) NULL");
        }
    }

    public function down(): void
    {
        // Revert email to NOT NULL (be careful: will fail if any rows have NULL email)
        try {
            DB::statement("ALTER TABLE `users` MODIFY `email` VARCHAR(150) NOT NULL");
        } catch (\Throwable $e) {}

        // Drop phone + unique
        Schema::table('users', function (Blueprint $table) {
            try { $table->dropUnique('uq_users_phone'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
