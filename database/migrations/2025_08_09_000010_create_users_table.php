<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // SaaS references
            $table->unsignedBigInteger('company_id')->comment('FK to companies');
            $table->unsignedBigInteger('role_id')->comment('FK to roles');

            // User info
            $table->string('name', 150);
            $table->string('email', 150);
            $table->string('password', 255);

            // Laravel remember me token
            $table->string('remember_token', 100)
                  ->collation('utf8mb4_unicode_ci')
                  ->nullable()
                  ->comment('Token for Laravel remember me authentication');

            // Status
            $table->tinyInteger('status')
                  ->default(1)
                  ->comment('1=active, 0=inactive');

            // Audit columns
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['company_id', 'email'], 'uq_user_email');
            $table->index('company_id', 'idx_users_company_id');
            $table->index('role_id', 'idx_users_role_id');
            $table->index('status', 'idx_users_status');
            $table->index('deleted_at', 'idx_users_deleted_at');
            $table->index('created_by', 'idx_users_created_by');
            $table->index('updated_by', 'idx_users_updated_by');

            // FKs
            $table->foreign('company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('role_id')
                  ->references('id')->on('roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
