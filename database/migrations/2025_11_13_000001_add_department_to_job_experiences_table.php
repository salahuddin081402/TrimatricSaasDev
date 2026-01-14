<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'job_experiences';
    private string $after = 'Job_title';   // match exact case from schema
    private string $column = 'department';

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        // Only add if missing
        if (!Schema::hasColumn($this->table, $this->column)) {
            Schema::table($this->table, function (Blueprint $table) {
                // nullable to avoid failing on existing rows
                $table->string($this->column, 100)
                      ->nullable()
                      ->after($this->after)
                      ->comment('Department name for this job experience');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        // Drop if present
        if (Schema::hasColumn($this->table, $this->column)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn($this->column);
            });
        }
    }
};
