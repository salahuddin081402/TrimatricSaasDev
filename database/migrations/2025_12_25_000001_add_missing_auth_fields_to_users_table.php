<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Social login fields (nullable)
            $table->string('provider', 50)->nullable()->after('remember_token');
            $table->string('provider_id', 191)->nullable()->after('provider');

            // Optional but strongly recommended: prevent duplicate provider identities
            $table->unique(['provider', 'provider_id'], 'uq_users_provider_provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('uq_users_provider_provider_id');
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
