<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Birth_Certificate_Photo column to registration_master.
     * Stores the image path when a user does not provide NID.
     */
    public function up(): void
    {
        Schema::table('registration_master', function (Blueprint $table) {
            // Add after NID back image for logical grouping with identity docs
            $table->string('Birth_Certificate_Photo', 255)
                  ->nullable()
                  ->after('NID_Photo_Back_Page')
                  ->comment('Image path of birth certificate (required if NID not provided)');
        });
    }

    /**
     * Rollback column addition.
     */
    public function down(): void
    {
        Schema::table('registration_master', function (Blueprint $table) {
            $table->dropColumn('Birth_Certificate_Photo');
        });
    }
};
