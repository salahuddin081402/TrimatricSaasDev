<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: communication_message_logs
 * Tenant-aware batch SMS/Email delivery logs.
 *
 * Notes:
 * - MySQL 8+ recommended (for UUID() default in DB).
 * - FK assumes `companies` table exists with `id` BIGINT UNSIGNED.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_message_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Tenant
            $table->unsignedBigInteger('company_id')->comment('FK -> companies.id');

            // Professional unique identifier for this log row
            // Using DB default UUID() is MySQL-specific; we set it via raw default expression.
            $table->char('message_uid', 36)
                ->default(DB::raw('(UUID())'))
                ->comment('Unique per message attempt/log row');

            // Batch Tracking
            $table->char('batch_no', 36)->comment('UUID batch number (same for one campaign/batch)');

            // Message Type
            $table->enum('message_type', ['SMS', 'EMAIL'])->comment('Delivery channel');

            // Recipient Info
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_phone', 20)->nullable()->comment('Used for SMS');
            $table->string('recipient_email', 190)->nullable()->comment('Used for Email');

            // Message Content Snapshot
            $table->string('message_subject', 255)->nullable()->comment('Email subject / SMS short title');
            $table->text('message_body')->nullable()->comment('Actual content sent');

            // Delivery Result
            $table->enum('status', ['SUCCESS', 'FAILED'])->default('SUCCESS');
            $table->text('failure_reason')->nullable()->comment('Failure reason if status = FAILED');

            // Gateway / Transport Info
            $table->string('gateway_name', 100)->nullable()->comment('e.g. MRAM, SMTP');
            $table->text('gateway_response')->nullable()->comment('Raw gateway/mailer response (if any)');

            // Audit
            $table->dateTime('sent_at')->nullable()->comment('Actual send time');
            $table->string('sender_ip', 45)->nullable()->comment('IPv4/IPv6');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User ID who triggered the send/batch');

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->unique(['company_id', 'message_uid'], 'uq_company_message_uid');

            $table->index(['company_id', 'batch_no'], 'idx_company_batch');
            $table->index(['company_id', 'message_type'], 'idx_company_type');
            $table->index(['company_id', 'status'], 'idx_company_status');
            $table->index(['company_id', 'sent_at'], 'idx_company_sent_at');
            $table->index(['company_id', 'recipient_phone'], 'idx_company_phone');
            $table->index(['company_id', 'recipient_email'], 'idx_company_email');

            // Foreign key
            $table->foreign('company_id', 'fk_cml_company')
                ->references('id')->on('companies')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_message_logs');
    }
};
