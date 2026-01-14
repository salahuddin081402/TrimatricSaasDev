<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id()->comment('Country ID');
            $table->string('name', 150)->unique()->comment('Country name');
            $table->string('short_code', 10)->nullable()->comment('Short Code. Say, BD for Bangladesh');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created this Country');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('User who last updated this Country');
            $table->timestamps();
            $table->softDeletes();

            $table->index('deleted_at', 'idx_country_deleted_at');
            $table->index('created_by', 'idx_country_created_by');
            $table->index('updated_by', 'idx_country_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
