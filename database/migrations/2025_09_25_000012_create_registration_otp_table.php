<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('Registration_OTP', function (Blueprint $table) {
            $table->engine  = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('Company_Id');
            $table->string('reg_key', 255);
            $table->string('phone', 30);
            $table->string('OTP', 10);
            $table->tinyInteger('status')->default(1)->comment('1=Active/Valid, 0=Used/Expired/Invalid');

            // timestamps & audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['Company_Id'], 'idx_rotp_company');
            $table->index(['status'], 'idx_rotp_status');
            $table->index(['Company_Id', 'reg_key', 'phone', 'created_at'], 'idx_rotp_lookup');

            $table->foreign('Company_Id', 'fk_rotp_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            // Composite FK to Company_Reg_Keys
            $table->foreign(['Company_Id', 'reg_key'], 'fk_rotp_company_reg_key')
                  ->references(['Company_id', 'reg_key'])
                  ->on('Company_Reg_Keys')
                  ->onDelete('cascade');
        });

        // Add generated column + unique constraint (status_active)
        DB::statement("
            ALTER TABLE `Registration_OTP`
            ADD COLUMN `status_active` TINYINT(1)
            GENERATED ALWAYS AS (IF(`status` = 1, 1, NULL)) STORED
            COMMENT 'Generated: 1 when active; NULL otherwise (for unique-on-active constraint)'
            AFTER `status`
        ");
        DB::statement("
            ALTER TABLE `Registration_OTP`
            ADD UNIQUE KEY `uq_rotp_one_active` (`Company_Id`, `reg_key`, `phone`, `status_active`)
        ");
    }

    public function down(): void {
        // Drop unique first, then generated column, then table
        DB::statement("ALTER TABLE `Registration_OTP` DROP INDEX `uq_rotp_one_active`");
        DB::statement("ALTER TABLE `Registration_OTP` DROP COLUMN `status_active`");
        Schema::dropIfExists('Registration_OTP');
    }
};
