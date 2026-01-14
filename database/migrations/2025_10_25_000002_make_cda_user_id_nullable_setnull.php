<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_division_admins', function (Blueprint $table) {
            // 1) Drop existing FK (CASCADE)
            $table->dropForeign('company_division_admins_user_id_foreign'); // adjust if your FK name differs
        });

        Schema::table('company_division_admins', function (Blueprint $table) {
            // 2) Make column nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // 3) Recreate FK with ON DELETE SET NULL
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete()     // ON DELETE SET NULL
                  ->cascadeOnUpdate(); // keep UPDATE rule
        });
    }

    public function down(): void
    {
        Schema::table('company_division_admins', function (Blueprint $table) {
            // Drop SET NULL FK
            $table->dropForeign(['user_id']);
        });

        Schema::table('company_division_admins', function (Blueprint $table) {
            // Revert to NOT NULL and CASCADE
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }
};
