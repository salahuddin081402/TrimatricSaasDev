<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $db = DB::getDatabaseName();

        // 1) Capture ALL FKs on cluster_members so we can recreate them verbatim
        $fks = DB::select("
            SELECT
              rc.CONSTRAINT_NAME        AS name,
              kcu.TABLE_NAME            AS table_name,
              GROUP_CONCAT(kcu.COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION)              AS cols,
              kcu.REFERENCED_TABLE_NAME AS ref_table,
              GROUP_CONCAT(kcu.REFERENCED_COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION)   AS ref_cols,
              rc.UPDATE_RULE            AS on_update,
              rc.DELETE_RULE            AS on_delete
            FROM information_schema.REFERENTIAL_CONSTRAINTS rc
            JOIN information_schema.KEY_COLUMN_USAGE kcu
              ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
             AND rc.CONSTRAINT_NAME   = kcu.CONSTRAINT_NAME
            WHERE rc.CONSTRAINT_SCHEMA = ?
              AND kcu.TABLE_NAME = 'cluster_members'
            GROUP BY rc.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.REFERENCED_TABLE_NAME, rc.UPDATE_RULE, rc.DELETE_RULE
        ", [$db]);

        // 2) Drop ALL FKs (some engines bind FK to the exact index)
        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `cluster_members` DROP FOREIGN KEY `{$fk->name}`");
        }

        // 3) Drop old unique on (company_id,user_id). Try by name first, then by columns.
        try {
            Schema::table('cluster_members', function (Blueprint $table) {
                $table->dropUnique('uq_cmbr_user_per_co');
            });
        } catch (\Throwable $e) {
            // fallback if index name differs
            Schema::table('cluster_members', function (Blueprint $table) {
                $table->dropUnique(['company_id','user_id']);
            });
        }

        // 4) Create helper non-unique index for (company_id,user_id). Then create the new unique.
        Schema::table('cluster_members', function (Blueprint $table) {
            // keep lookup speed for FKs/queries
            $table->index(['company_id','user_id'], 'idx_cmbr_company_user');
            // new uniqueness: (company_id, cluster_id, user_id)
            $table->unique(['company_id','cluster_id','user_id'], 'uq_cmbr_company_cluster_user');
        });

        // 5) Recreate all previously dropped FKs exactly as before
        foreach ($fks as $fk) {
            $cols    = implode('`,`', explode(',', $fk->cols));
            $refCols = implode('`,`', explode(',', $fk->ref_cols));
            $onUpd   = strtoupper($fk->on_update ?: 'RESTRICT');
            $onDel   = strtoupper($fk->on_delete ?: 'RESTRICT');
            DB::statement("
                ALTER TABLE `cluster_members`
                ADD CONSTRAINT `{$fk->name}`
                FOREIGN KEY (`{$cols}`)
                REFERENCES `{$fk->ref_table}` (`{$refCols}`)
                ON UPDATE {$onUpd} ON DELETE {$onDel}
            ");
        }
    }

    public function down(): void
    {
        $db = DB::getDatabaseName();

        // Capture FKs again (names are the same we re-added)
        $fks = DB::select("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = ?
              AND CONSTRAINT_NAME IN (
                  SELECT CONSTRAINT_NAME
                  FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_SCHEMA = ? AND TABLE_NAME='cluster_members' AND REFERENCED_TABLE_NAME IS NOT NULL
              )
        ", [$db, $db]);

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `cluster_members` DROP FOREIGN KEY `{$fk->name}`");
        }

        Schema::table('cluster_members', function (Blueprint $table) {
            $table->dropUnique('uq_cmbr_company_cluster_user');
            $table->dropIndex('idx_cmbr_company_user');
            // restore old unique
            $table->unique(['company_id','user_id'], 'uq_cmbr_user_per_co');
        });

        // no FK re-add in down() to avoid guessing order; re-run your original schema migration if needed
    }
};
