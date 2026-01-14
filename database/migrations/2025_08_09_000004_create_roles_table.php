<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('FK to companies');
            $table->unsignedBigInteger('role_type_id')->comment('FK to role_types');
            $table->string('name', 100)->comment('Role name (e.g., Zonal Admin)');
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name'], 'uq_role_company_name');
            $table->index('company_id', 'idx_roles_company_id');
            $table->index('role_type_id', 'idx_roles_role_type_id');
            $table->index('deleted_at', 'idx_roles_deleted_at');
            $table->index('created_by', 'idx_roles_created_by');
            $table->index('updated_by', 'idx_roles_updated_by');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('role_type_id')->references('id')->on('role_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
