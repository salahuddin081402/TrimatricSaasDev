<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id()->comment('Company/Tenant ID');

            // FK: countries must already be created by a prior migration
            $table->foreignId('country_id')
                  ->constrained('countries')
                  ->cascadeOnDelete()
                  ->comment('FK to countries.id');

            $table->string('name', 150)->comment('Company or tenant name');
            $table->string('slug', 190)->comment('URL-safe unique slug for company');

            $table->string('description', 255)->nullable()->comment('Details about the company');
            $table->string('address', 255)->nullable()->comment('Company address');
            $table->string('contact_no', 50)->nullable()->comment('Primary contact no');
            $table->string('logo', 255)->nullable()->comment('Public path to company logo');

            // 1=Active, 0=Inactive
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');

            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created this company');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('User who last updated this company');

            $table->timestamps();
            $table->softDeletes();

            // Constraints & indexes
            $table->unique('name', 'uq_companies_name');
            $table->unique('slug', 'uq_companies_slug');
            $table->index('status', 'idx_companies_status');
            $table->index('deleted_at', 'idx_companies_deleted_at');
            $table->index('created_by', 'idx_companies_created_by');
            $table->index('updated_by', 'idx_companies_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
