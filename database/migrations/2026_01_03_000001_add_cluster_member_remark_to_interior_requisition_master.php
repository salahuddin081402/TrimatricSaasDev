<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interior_requisition_master', function (Blueprint $table) {
            if (!Schema::hasColumn('interior_requisition_master', 'cluster_member_remark')) {
                $table->text('cluster_member_remark')
                    ->nullable()
                    ->after('project_eta');
                // placed BEFORE head_office_remark logically
            }
        });
    }

    public function down(): void
    {
        Schema::table('interior_requisition_master', function (Blueprint $table) {
            if (Schema::hasColumn('interior_requisition_master', 'cluster_member_remark')) {
                $table->dropColumn('cluster_member_remark');
            }
        });
    }
};
