<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `education_background` MODIFY `Passing_Year` YEAR(4) NULL");
        DB::statement("ALTER TABLE `education_background` MODIFY `Result_Type` ENUM('GPA','CGPA','Division','Class','Percentage') NULL");
        DB::statement("ALTER TABLE `education_background` MODIFY `obtained_grade_or_score` VARCHAR(20) NULL");
        DB::statement("ALTER TABLE `education_background` MODIFY `Out_of` INT(10) UNSIGNED NULL");
    }

    public function down(): void
    {
        // Normalize NULLs before reverting NOT NULL
        DB::statement("UPDATE `education_background` SET `Passing_Year` = YEAR(CURDATE()) WHERE `Passing_Year` IS NULL");
        DB::statement("UPDATE `education_background` SET `Result_Type` = 'GPA' WHERE `Result_Type` IS NULL");
        DB::statement("UPDATE `education_background` SET `obtained_grade_or_score` = '' WHERE `obtained_grade_or_score` IS NULL");
        DB::statement("UPDATE `education_background` SET `Out_of` = 0 WHERE `Out_of` IS NULL");

        DB::statement("ALTER TABLE `education_background` MODIFY `Passing_Year` YEAR(4) NOT NULL");
        DB::statement("ALTER TABLE `education_background` MODIFY `Result_Type` ENUM('GPA','CGPA','Division','Class','Percentage') NOT NULL");
        DB::statement("ALTER TABLE `education_background` MODIFY `obtained_grade_or_score` VARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE `education_background` MODIFY `Out_of` INT(10) UNSIGNED NOT NULL");
    }
};
