<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Expertise_on_Softwares', function (Blueprint $table) {
            $table->engine  = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('Company_id');
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('expert_on_software'); // FK Software_List.id
            $table->decimal('experience_in_years', 4, 1)->default(0.0);

            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['registration_id', 'expert_on_software'], 'uq_reg_software');
            $table->index(['Company_id'], 'idx_eos_company');
            $table->index(['status'], 'idx_eos_status');

            $table->foreign('Company_id', 'fk_eos_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('registration_id', 'fk_eos_registration')
                  ->references('id')->on('registration_master')
                  ->onDelete('cascade');

            $table->foreign('expert_on_software', 'fk_eos_software')
                  ->references('id')->on('Software_List')
                  ->onDelete('restrict');
        });
    }

    public function down(): void {
        Schema::dropIfExists('Expertise_on_Softwares');
    }
};
