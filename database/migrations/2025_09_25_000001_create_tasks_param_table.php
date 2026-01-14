<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Tasks_Param', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('Task_Param_ID');
            $table->string('Task_Param_Name', 150);
            $table->string('Module', 120);
            $table->enum('Type', ['Inputter', 'Approver']);
            $table->enum('Is_Client_Approval_Required', ['Y', 'N'])->default('N');

            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['Task_Param_Name', 'Module'], 'uq_task_param_name_module');
            $table->index(['Type'], 'idx_tasks_param_type');
            $table->index(['status'], 'idx_tasks_param_status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('Tasks_Param');
    }
};
