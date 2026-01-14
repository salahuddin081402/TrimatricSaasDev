<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safely drop existing table & triggers if present
        Schema::disableForeignKeyConstraints();

        DB::unprepared("DROP TRIGGER IF EXISTS trg_eb_year_not_future_bi");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_eb_year_not_future_bu");

        Schema::dropIfExists('education_background');

        Schema::enableForeignKeyConstraints();

        // Re-create table with clean schema + proper FKs
        Schema::create('education_background', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('Company_id');      // companies.id
            $table->unsignedBigInteger('registration_id'); // registration_master.id
            $table->unsignedBigInteger('degree_id');       // degrees.id

            // Main fields
            $table->string('Institution', 180);
            $table->year('Passing_Year');

            // Common result types used in BD contexts
            $table->enum('Result_Type', ['GPA','CGPA','Division','Class','Percentage']);
            $table->string('obtained_grade_or_score', 20);
            $table->unsignedInteger('Out_of');

            // Meta
            $table->tinyInteger('status')->default(1); // 1=Active, 0=Inactive
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Uniqueness: one record per (Company, Registration, Degree)
            $table->unique(['Company_id','registration_id','degree_id'], 'uq_eb_company_reg_degree');

            // Helpful indexes
            $table->index(['Company_id','registration_id'], 'idx_eb_company_reg');
            $table->index('degree_id', 'idx_eb_degree');
            $table->index('Passing_Year', 'idx_eb_year');
            $table->index('Result_Type', 'idx_eb_result_type');
            $table->index('status', 'idx_eb_status');

            // FKs (adjust table names if yours differ)
            $table->foreign('Company_id', 'fk_eb_company')
                ->references('id')->on('companies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('registration_id', 'fk_eb_registration')
                ->references('id')->on('registration_master')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('degree_id', 'fk_eb_degree')
                ->references('id')->on('degrees')
                ->restrictOnDelete()   // prevent deleting a degree that’s in use
                ->cascadeOnUpdate();
        });

        // Create triggers (no DELIMITER needed via DB::unprepared)
        DB::unprepared("
            CREATE TRIGGER trg_eb_year_not_future_bi
            BEFORE INSERT ON education_background
            FOR EACH ROW
            BEGIN
                IF NEW.Passing_Year > YEAR(CURDATE()) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Passing_Year cannot be in the future';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_eb_year_not_future_bu
            BEFORE UPDATE ON education_background
            FOR EACH ROW
            BEGIN
                IF NEW.Passing_Year > YEAR(CURDATE()) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Passing_Year cannot be in the future';
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers first, then table
        DB::unprepared("DROP TRIGGER IF EXISTS trg_eb_year_not_future_bi");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_eb_year_not_future_bu");

        Schema::dropIfExists('education_background');
    }
};
