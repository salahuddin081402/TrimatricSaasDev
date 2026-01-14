<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Job_Experiences', function (Blueprint $table) {
            $table->engine  = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('Company_id');
            $table->unsignedBigInteger('registration_id');

            $table->string('Employer', 180);
            $table->string('Job_title', 150);
            $table->date('Joining_date');
            $table->date('End_date')->nullable();
            $table->enum('is_present_job', ['Y', 'N'])->default('N');

            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['Company_id', 'registration_id'], 'idx_je_company_reg');
            $table->index(['is_present_job'], 'idx_je_present');
            $table->index(['status'], 'idx_je_status');

            $table->foreign('Company_id', 'fk_je_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('registration_id', 'fk_je_registration')
                  ->references('id')->on('registration_master')
                  ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('Job_Experiences');
    }
};
