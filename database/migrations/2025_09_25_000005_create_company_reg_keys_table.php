<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Company_Reg_Keys', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedBigInteger('Company_id');
            $table->string('reg_key', 255);
            $table->tinyInteger('status')->default(1);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->primary(['Company_id', 'reg_key']);
            $table->index(['status'], 'idx_crk_status');

            $table->foreign('Company_id', 'fk_crk_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('Company_Reg_Keys');
    }
};
