<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_actions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('action_id');

            $table->string('button_label', 100)->nullable()->comment('Optional: custom label for this menu-action button');
            $table->string('button_icon', 100)->nullable()->comment('Optional: icon for the button');
            $table->unsignedInteger('button_order')->nullable()->comment('Display order for actions in this menu');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Uniqueness & indexes
            $table->unique(['menu_id', 'action_id'], 'uq_menu_action');
            $table->index('menu_id');
            $table->index('action_id');
            $table->index('deleted_at');

            // FKs
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $table->foreign('action_id')->references('id')->on('actions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_actions');
    }
};
