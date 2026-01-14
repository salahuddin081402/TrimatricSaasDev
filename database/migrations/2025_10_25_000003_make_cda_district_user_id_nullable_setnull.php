<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_district_admins', function (Blueprint $table) {
            // Drop existing FK (likely CASCADE). Change the name if yours differs.
            $table->dropForeign('company_district_admins_user_id_foreign');
        });

        Schema::table('company_district_admins', function (Blueprint $table) {
            // Make nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Re-add FK with ON DELETE SET NULL, ON UPDATE CASCADE
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('company_district_admins', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('company_district_admins', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }
};
