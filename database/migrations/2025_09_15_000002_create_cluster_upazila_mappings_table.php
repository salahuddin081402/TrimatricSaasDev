<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cluster_upazila_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cluster_id')->constrained('cluster_masters')->cascadeOnDelete();
            $table->foreignId('upazila_id')->constrained('upazilas')->cascadeOnDelete();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // prevent double-assign of same upazila within a company
            $table->unique(['company_id','upazila_id'], 'uq_cum_upazila_per_co');

            // defensive: block duplicate pair rows
            $table->unique(['cluster_id','upazila_id'], 'uq_cum_pair');

            // helpful indexes
            $table->index('status', 'idx_cum_status');
            $table->index('created_by', 'idx_cum_created_by');
            $table->index('updated_by', 'idx_cum_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_upazila_mappings');
    }
};
