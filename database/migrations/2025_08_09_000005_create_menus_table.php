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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('For menu hierarchy');
            $table->string('name', 100)->unique()->comment('Menu name');
            $table->string('uri', 200)->nullable()->comment('Route/URL');
            $table->string('icon', 100)->nullable()->comment('Fontawesome or similar icon');
            $table->unsignedInteger('menu_order')->nullable()->comment('Display order');
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('parent_id', 'idx_menus_parent_id');
            $table->index('deleted_at', 'idx_menus_deleted_at');

            // Self FK
            $table->foreign('parent_id')
                  ->references('id')->on('menus')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
