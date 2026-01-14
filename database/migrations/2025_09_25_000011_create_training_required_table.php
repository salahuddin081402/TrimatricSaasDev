<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Training_Required', function (Blueprint $table) {
            $table->engine  = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedBigInteger('Company_id');
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('Training_Category_Id');
            $table->unsignedBigInteger('Training_ID');

            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->primary(['Company_id', 'registration_id', 'Training_Category_Id', 'Training_ID']);
            $table->index(['status'], 'idx_tr_status');

            $table->foreign('Company_id', 'fk_tr_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('registration_id', 'fk_tr_registration')
                  ->references('id')->on('registration_master')
                  ->onDelete('cascade');

            $table->foreign(['Company_id', 'Training_Category_Id', 'Training_ID'], 'fk_tr_training')
                  ->references(['Company_id', 'Training_Category_Id', 'Training_ID'])
                  ->on('Training_list')
                  ->onDelete('restrict');
        });
    }

    public function down(): void {
        Schema::dropIfExists('Training_Required');
    }
};
