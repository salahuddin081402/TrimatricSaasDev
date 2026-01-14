<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Table: communication_batches
 *
 * Purpose:
 * - One row per batch/campaign
 * - Drives progress tracking, cancel control, and history UI
 * - Linked to communication_message_logs via batch_no
 *
 * This table is FINALIZED as per discussion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_batches', function (Blueprint $table) {

            $table->bigIncrements('id');

            // Tenant
            $table->unsignedBigInteger('company_id')
                ->comment('FK -> companies.id');

            // Batch identity (UUID format you approved)
            $table->char('batch_no', 36)
                ->unique()
                ->comment('Batch identifier (UUID / BATCH-YYYYMMDD-####)');

            // Scope / source
            $table->string('target_group', 50)
                ->comment('client_entrepreneur | cluster | division_admin | district_admin | all_users');

            // Channel selection
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_email')->default(false);

            // Message snapshot (campaign-level)
            $table->string('subject', 255)->nullable();
            $table->text('message')->comment('Main message body');
            $table->text('extra_message')->nullable();

            // Recipient stats
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            // Batch lifecycle
            $table->enum('status', [
                'PENDING',     // created, not dispatched
                'RUNNING',     // queue batch executing
                'COMPLETED',   // finished successfully
                'FAILED',      // batch-level failure
                'CANCELLED'    // manually cancelled
            ])->default('PENDING');

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Control flags
            $table->boolean('is_cancel_requested')
                ->default(false)
                ->comment('Used by jobs to stop gracefully');

            // Audit
            $table->unsignedBigInteger('created_by')
                ->comment('Admin user id who created the batch');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Indexes
            $table->index(['company_id', 'status'], 'idx_cb_company_status');
            $table->index(['company_id', 'created_at'], 'idx_cb_company_created');

            // FK
            $table->foreign('company_id', 'fk_cb_company')
                ->references('id')
                ->on('companies')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_batches');
    }
};
