<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('Education_Background', function (Blueprint $table) {
            // Engine / charset
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            // PK
            $table->bigIncrements('id');

            // Parents
            $table->unsignedBigInteger('Company_id')->comment('FK companies.id');
            $table->unsignedBigInteger('registration_id')->comment('FK registration_master.id');

            // Core fields
            $table->enum('Education_Level', [
                'SSC','HSC','BSc','Masters','MPhil','Phd','Dakhil','Alim','Fazil','Kamil'
            ]);
            $table->string('Institution', 180);
            // Create as SMALLINT then convert to YEAR (schema builder lacks YEAR)
            $table->unsignedSmallInteger('Passing_Year');

            $table->enum('Result_Type', ['Grade', 'Score']);
            $table->string('obtained_grade_or_score', 20);
            $table->unsignedInteger('Out_of');

            // Audit / status
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Uniqueness: one row per level per registration within a company
            $table->unique(['Company_id', 'registration_id', 'Education_Level'], 'uq_eb_company_reg_level');

            // Helpful lookups
            $table->index(['Company_id', 'registration_id'], 'idx_eb_company_reg');
            $table->index(['status'], 'idx_eb_status');
            $table->index(['Passing_Year'], 'idx_eb_year');
            $table->index(['Education_Level'], 'idx_eb_level');
            $table->index(['Result_Type'], 'idx_eb_result_type');

            // FKs
            $table->foreign('Company_id', 'fk_eb_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('registration_id', 'fk_eb_registration')
                  ->references('id')->on('registration_master')
                  ->onDelete('cascade');
        });

        // Convert Passing_Year to YEAR type
        DB::statement("ALTER TABLE `Education_Background` MODIFY `Passing_Year` YEAR NOT NULL");

        // CHECK for grade/score format and Out_of rules (OK: no non-deterministic functions)
        DB::statement("
            ALTER TABLE `Education_Background`
            ADD CONSTRAINT `chk_eb_grade_or_score`
            CHECK (
                (
                    `Result_Type` = 'Grade'
                    AND `obtained_grade_or_score` REGEXP '^[A-Za-z]{1,2}[+-]?$'
                    AND `Out_of` IN (4,5)
                )
                OR
                (
                    `Result_Type` = 'Score'
                    AND `obtained_grade_or_score` REGEXP '^[0-9]+(\\.[0-9]{1,2})?$'
                    AND `Out_of` > 0
                    AND CAST(`obtained_grade_or_score` AS DECIMAL(10,2)) <= CAST(`Out_of` AS DECIMAL(10,2))
                )
            )
        ");

        // Enforce "Passing_Year not in the future" via triggers (allowed to use CURDATE())
        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_eb_year_not_future_bi;
            CREATE TRIGGER trg_eb_year_not_future_bi
            BEFORE INSERT ON Education_Background
            FOR EACH ROW
            BEGIN
                IF NEW.Passing_Year > YEAR(CURDATE()) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Passing_Year cannot be in the future';
                END IF;
            END
        ");

        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_eb_year_not_future_bu;
            CREATE TRIGGER trg_eb_year_not_future_bu
            BEFORE UPDATE ON Education_Background
            FOR EACH ROW
            BEGIN
                IF NEW.Passing_Year > YEAR(CURDATE()) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Passing_Year cannot be in the future';
                END IF;
            END
        ");
    }

    public function down(): void
    {
        // Drop triggers if present
        try { DB::unprepared('DROP TRIGGER IF EXISTS trg_eb_year_not_future_bu'); } catch (\Throwable $e) {}
        try { DB::unprepared('DROP TRIGGER IF EXISTS trg_eb_year_not_future_bi'); } catch (\Throwable $e) {}

        // Drop CHECK (older MySQL may not name it; ignore if missing)
        try { DB::statement("ALTER TABLE `Education_Background` DROP CHECK `chk_eb_grade_or_score`"); } catch (\Throwable $e) {}

        Schema::dropIfExists('Education_Background');
    }
};
