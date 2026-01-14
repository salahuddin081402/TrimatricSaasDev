<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ID_Card_Types', function (Blueprint $table) {
            $table->unsignedInteger('ID_Card_Type_id', true);
            $table->string('ID_Card_Type_name', 100);
            $table->tinyInteger('status')->default(1);
            $table->unique('ID_Card_Type_name', 'uq_id_card_type_name');
        });

        // CHECK (status IN (0,1))
        DB::statement("
            ALTER TABLE `ID_Card_Types`
            ADD CONSTRAINT `chk_id_card_types_status`
            CHECK (`status` IN (0,1))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('ID_Card_Types');
    }
};
