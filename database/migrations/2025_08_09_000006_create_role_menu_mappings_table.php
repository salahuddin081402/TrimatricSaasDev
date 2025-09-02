<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_menu_mappings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('menu_id');

            $table->string('access_type', 30)->nullable()->comment('view/edit/delete, etc.');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Uniqueness & indexes
            $table->unique(['role_id', 'menu_id'], 'uq_role_menu');
            $table->index('role_id', 'idx_role_menu_role_id');
            $table->index('menu_id', 'idx_role_menu_menu_id');
            $table->index('deleted_at', 'idx_role_menu_deleted_at');
            $table->index('created_by', 'idx_role_menu_created_by');
            $table->index('updated_by', 'idx_role_menu_updated_by');

            // FKs
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('menus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_mappings');
    }
};
