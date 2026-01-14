<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('Person_skills', function (Blueprint $table) {
            // Engine / charset
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            // PK
            $table->bigIncrements('id');

            // Parents
            $table->unsignedBigInteger('Company_id')->comment('FK companies.id');
            $table->unsignedBigInteger('registration_id')->comment('FK registration_master.id');
            $table->unsignedBigInteger('skill')->comment('FK skills.id');

            // Audit / status (no soft deletes)
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Uniqueness: one row per (Company, Registration, Skill)
            $table->unique(['Company_id', 'registration_id', 'skill'], 'uq_ps_company_reg_skill');

            // Helpful lookups
            $table->index(['Company_id', 'registration_id'], 'idx_ps_company_reg');
            $table->index(['skill'], 'idx_ps_skill');
            $table->index(['status'], 'idx_ps_status');

            // Foreign keys
            $table->foreign('Company_id', 'fk_ps_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('registration_id', 'fk_ps_registration')
                  ->references('id')->on('registration_master')
                  ->onDelete('cascade');

            $table->foreign('skill', 'fk_ps_skill')
                  ->references('id')->on('skills')
                  ->onDelete('restrict'); // change to 'cascade' if you prefer
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Person_skills');
    }
};
