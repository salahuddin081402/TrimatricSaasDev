<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrepreneur_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);

            // FK types must match parents (companies.id, registration_master.id)
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('registration_id');

            $table->tinyInteger('do_you_have_business')->default(0);

            $table->unsignedInteger('business_type_id')->nullable();   // Business_Types.business_type_id
            $table->string('Company_name', 150)->nullable();
            // YEAR added via raw SQL after create
            $table->string('Company_address', 255)->nullable();
            $table->string('Company_contact_no', 32)->nullable();

            $table->enum('Turn_over', [
                '0 - 50k',
                '50k - 1 lac',
                '1 lac - 5 lac',
                '5 lac - 10 lac',
                '10 lac - 50 lac',
                '50 lac - 1 Cr',
                '1 Cr - Above',
            ])->nullable();

            $table->string('ID_Card_No', 20)->nullable();
            $table->unsignedInteger('ID_Card_Type_id')->nullable();    // ID_Card_Types.ID_Card_Type_id
            $table->date('ID_Card_delivery_date')->nullable();
            $table->tinyInteger('ID_Card_delivery_status')->default(0); // 0=Applied,1=Processing,2=Delivered

            $table->timestamp('created_at')->useCurrent()->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();

            // Uniques
            $table->unique(['company_id','registration_id'], 'uq_company_reg');
            $table->unique('ID_Card_No', 'uq_id_card_no');

            // Indexes
            $table->index('business_type_id', 'idx_ent_business_type');
            $table->index('ID_Card_Type_id', 'idx_ent_id_card_type');

            // FKs
            $table->foreign('business_type_id', 'fk_ent_business_type')
                ->references('business_type_id')->on('Business_Types')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->foreign('ID_Card_Type_id', 'fk_ent_id_card_type')
                ->references('ID_Card_Type_id')->on('ID_Card_Types')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->foreign('company_id', 'fk_ent_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->foreign('registration_id', 'fk_ent_registration_id')
                ->references('id')->on('registration_master')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        // Native YEAR column
        DB::statement("
            ALTER TABLE `entrepreneur_details`
            ADD COLUMN `Company_Establishment_Year` YEAR NULL AFTER `Company_name`
        ");

        // CHECK constraints
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_has_business`
            CHECK (`do_you_have_business` IN (0,1))
        ");
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_delivery_status`
            CHECK (`ID_Card_delivery_status` IN (0,1,2))
        ");
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_est_year`
            CHECK (`Company_Establishment_Year` IS NULL OR (`Company_Establishment_Year` BETWEEN 1950 AND 2155))
        ");
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_business_type_required`
            CHECK (`do_you_have_business` = 0 OR `business_type_id` IS NOT NULL)
        ");
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_company_name_required`
            CHECK (`do_you_have_business` = 0 OR (`Company_name` IS NOT NULL AND `Company_name` <> ''))
        ");
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_company_address_required`
            CHECK (`do_you_have_business` = 0 OR (`Company_address` IS NOT NULL AND `Company_address` <> ''))
        ");
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_company_contact_required`
            CHECK (`do_you_have_business` = 0 OR (`Company_contact_no` IS NOT NULL AND `Company_contact_no` <> ''))
        ");
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_turnover_required`
            CHECK (`do_you_have_business` = 0 OR `Turn_over` IS NOT NULL)
        ");
        DB::statement("ALTER TABLE `entrepreneur_details`
            ADD CONSTRAINT `chk_ent_est_year_required`
            CHECK (`do_you_have_business` = 0 OR `Company_Establishment_Year` IS NOT NULL)
        ");

        // Trigger: no DELIMITER in PDO
        DB::unprepared("DROP TRIGGER IF EXISTS `trg_bi_entrepreneur_details_id_card_no`");
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_bi_entrepreneur_details_id_card_no
BEFORE INSERT ON entrepreneur_details
FOR EACH ROW
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;

  SELECT rm.user_id
    INTO v_user_id
    FROM registration_master AS rm
   WHERE rm.id = NEW.registration_id
   LIMIT 1;

  IF v_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'registration_master row not found for registration_id';
  END IF;

  IF NEW.ID_Card_No IS NULL OR NEW.ID_Card_No = '' THEN
    SET NEW.ID_Card_No =
      CONCAT(
        LPAD(NEW.company_id, 4, '0'),
        '-',
        '04',
        '-',
        LPAD(v_user_id, 7, '0')
      );
  END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS `trg_bi_entrepreneur_details_id_card_no`");
        Schema::dropIfExists('entrepreneur_details');
    }
};
