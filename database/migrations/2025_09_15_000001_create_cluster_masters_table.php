<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cluster_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->unsignedTinyInteger('cluster_no');           // 01..99 per (company,district)
            $table->char('short_code', 7);                       // DIST(5)+cluster_no(2) — unique per company
            $table->string('cluster_name', 150);
            $table->foreignId('cluster_supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // uniques (scoped to company)
            $table->unique(['company_id','district_id','cluster_name'], 'uq_cm_name_per_dist');
            $table->unique(['company_id','district_id','cluster_no'],   'uq_cm_no_per_dist');
            $table->unique(['company_id','short_code'],                  'uq_cm_code_per_co');

            // helpful indexes
            $table->index('status', 'idx_cm_status');
            $table->index('cluster_supervisor_id', 'idx_cm_supervisor');
            $table->index('created_by', 'idx_cm_created_by');
            $table->index('updated_by', 'idx_cm_updated_by');
        });

        // Optional check (MySQL 8.0.16+). Safe to skip if your MySQL is older.
        DB::statement("ALTER TABLE cluster_masters
            ADD CONSTRAINT chk_cm_cluster_no CHECK (cluster_no BETWEEN 1 AND 99)");
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_masters');
    }
};
