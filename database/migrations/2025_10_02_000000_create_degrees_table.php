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
        Schema::create('degrees', function (Blueprint $table) {
            // (optional) ensure InnoDB on MySQL
            if (property_exists($table, 'engine')) {
                $table->engine = 'InnoDB';
            }

            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
            $table->string('name', 120);       // Full degree name (unique)
            $table->string('short_code', 40);  // Abbrev/code (unique)
            $table->integer('level');          // Sort order (lower = earlier level)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name', 'uq_degrees_name');
            $table->unique('short_code', 'uq_degrees_short_code');
            $table->index('level', 'idx_degrees_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('degrees');
    }
};
