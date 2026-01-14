<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cluster_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cluster_id')->constrained('cluster_masters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // one cluster per user per company
            $table->unique(['company_id','user_id'], 'uq_cmbr_user_per_co');

            // defensive duplicate guard
            $table->unique(['cluster_id','user_id'], 'uq_cmbr_pair');

            // helpful indexes
            $table->index('status', 'idx_cmbr_status');
            $table->index('created_by', 'idx_cmbr_created_by');
            $table->index('updated_by', 'idx_cmbr_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_members');
    }
};
