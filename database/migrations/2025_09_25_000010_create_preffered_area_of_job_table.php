<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Preffered_Area_of_Job', function (Blueprint $table) {
            $table->engine  = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedBigInteger('Company_id');
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('Task_Param_ID');

            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->primary(['Company_id', 'registration_id', 'Task_Param_ID']);
            $table->index(['status'], 'idx_paj_status');

            $table->foreign('Company_id', 'fk_paj_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('registration_id', 'fk_paj_registration')
                  ->references('id')->on('registration_master')
                  ->onDelete('cascade');

            $table->foreign('Task_Param_ID', 'fk_paj_taskparam')
                  ->references('Task_Param_ID')->on('Tasks_Param')
                  ->onDelete('restrict');
        });
    }

    public function down(): void {
        Schema::dropIfExists('Preffered_Area_of_Job');
    }
};
