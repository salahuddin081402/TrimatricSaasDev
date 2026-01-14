<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->comment('Tenant/Company');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 20)->comment('add/edit/delete/login/logout/other');
            $table->string('table_name', 50)->nullable()->comment('Affected table/entity');
            $table->unsignedBigInteger('row_id')->nullable()->comment('Affected row id');
            $table->text('details')->nullable()->comment('Optional details or JSON snapshot');
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('time_local')->nullable()->comment('User’s local time (from browser/device)');
            $table->timestamp('time_dhaka')->nullable()->comment('Asia/Dhaka time (set by server)');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for reporting & lookups
            $table->index('company_id', 'idx_log_company_id');
            $table->index('user_id', 'idx_log_user_id');
            $table->index('action', 'idx_log_action');
            $table->index('time_dhaka', 'idx_log_time_dhaka');
            $table->index(['table_name', 'row_id'], 'idx_log_table_row');
            $table->index('deleted_at');

            // FKs
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
