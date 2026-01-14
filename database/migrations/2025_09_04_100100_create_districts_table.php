<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('districts')) return;

        Schema::create('districts', function (Blueprint $table) {
            $table->id()->comment('District ID');
            $table->foreignId('division_id')
                  ->constrained('divisions')
                  ->cascadeOnDelete()
                  ->comment('FK divisions.id');
            $table->string('name', 120)->comment('District name');
            $table->unsignedSmallInteger('dist_no')->nullable()->comment('Seq per division (1..99)');
            $table->string('short_code', 5)->nullable()->comment('DIV(3)+LPAD(dist_no,2)');
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['division_id','name'], 'uq_districts_division_name');
            $table->unique(['division_id','dist_no'], 'uq_districts_division_distno');
            $table->unique('short_code', 'uq_districts_short_code');
            $table->index('division_id', 'idx_districts_division');
        });

        // Triggers: auto dist_no + short_code
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_districts_bi BEFORE INSERT ON districts
FOR EACH ROW
BEGIN
  IF NEW.dist_no IS NULL THEN
    SET NEW.dist_no = IFNULL(
      (SELECT MAX(d2.dist_no) FROM districts d2 WHERE d2.division_id = NEW.division_id), 0
    ) + 1;
  END IF;

  SET NEW.short_code = CONCAT(
    (SELECT v.short_code FROM divisions v WHERE v.id = NEW.division_id),
    LPAD(NEW.dist_no, 2, '0')
  );
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_districts_bu BEFORE UPDATE ON districts
FOR EACH ROW
BEGIN
  IF (NEW.division_id <> OLD.division_id) OR (NEW.dist_no <> OLD.dist_no) THEN
    SET NEW.short_code = CONCAT(
      (SELECT v.short_code FROM divisions v WHERE v.id = NEW.division_id),
      LPAD(NEW.dist_no, 2, '0')
    );
  END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_districts_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_districts_bi');
        Schema::dropIfExists('districts');
    }
};
