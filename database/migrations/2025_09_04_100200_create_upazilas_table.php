<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('upazilas')) return;

        Schema::create('upazilas', function (Blueprint $table) {
            $table->id()->comment('Upazila ID');
            $table->foreignId('district_id')
                  ->constrained('districts')
                  ->cascadeOnDelete()
                  ->comment('FK districts.id');
            $table->string('name', 150)->comment('Upazila name');
            $table->unsignedSmallInteger('upa_no')->nullable()->comment('Seq per district (1..99)');
            $table->string('short_code', 7)->nullable()->comment('DIST(5)+LPAD(upa_no,2)');
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['district_id','name'], 'uq_upazilas_district_name');
            $table->unique(['district_id','upa_no'], 'uq_upazilas_district_upano');
            $table->unique('short_code', 'uq_upazilas_short_code');
            $table->index('district_id', 'idx_upazilas_district');
        });

        // Triggers: auto upa_no + short_code
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_upazilas_bi BEFORE INSERT ON upazilas
FOR EACH ROW
BEGIN
  IF NEW.upa_no IS NULL THEN
    SET NEW.upa_no = IFNULL(
      (SELECT MAX(u2.upa_no) FROM upazilas u2 WHERE u2.district_id = NEW.district_id), 0
    ) + 1;
  END IF;

  SET NEW.short_code = CONCAT(
    (SELECT d.short_code FROM districts d WHERE d.id = NEW.district_id),
    LPAD(NEW.upa_no, 2, '0')
  );
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_upazilas_bu BEFORE UPDATE ON upazilas
FOR EACH ROW
BEGIN
  IF (NEW.district_id <> OLD.district_id) OR (NEW.upa_no <> OLD.upa_no) THEN
    SET NEW.short_code = CONCAT(
      (SELECT d.short_code FROM districts d WHERE d.id = NEW.district_id),
      LPAD(NEW.upa_no, 2, '0')
    );
  END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_upazilas_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_upazilas_bi');
        Schema::dropIfExists('upazilas');
    }
};
