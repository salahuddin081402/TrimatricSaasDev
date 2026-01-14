<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Training_list', function (Blueprint $table) {
            $table->engine  = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedBigInteger('Company_id');
            $table->unsignedBigInteger('Training_Category_Id');
            $table->unsignedBigInteger('Training_ID');
            $table->string('Training_Name', 180);
            $table->string('Description', 500)->nullable();

            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->primary(['Company_id', 'Training_Category_Id', 'Training_ID']);
            $table->unique(['Company_id', 'Training_Name'], 'uq_company_training_name');
            $table->index(['status'], 'idx_tl_status');

            $table->foreign(['Company_id', 'Training_Category_Id'], 'fk_tl_company_category')
                  ->references(['Company_id', 'Training_Category_Id'])
                  ->on('Training_Category')
                  ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('Training_list');
    }
};
