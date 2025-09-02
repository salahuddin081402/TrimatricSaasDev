<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_menu_action_permissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('action_id');

            $table->boolean('allowed')->default(false)->comment('1=allowed, 0=not allowed');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Uniqueness & indexes
            $table->unique(['role_id', 'menu_id', 'action_id'], 'uq_role_menu_action');
            $table->index('role_id', 'idx_rma_role');
            $table->index('menu_id', 'idx_rma_menu');
            $table->index('action_id', 'idx_rma_action');
            $table->index('deleted_at');

            // FKs
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $table->foreign('action_id')->references('id')->on('actions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_action_permissions');
    }
};
