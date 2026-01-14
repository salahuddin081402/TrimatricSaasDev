<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Business_Types', function (Blueprint $table) {
            $table->unsignedInteger('business_type_id', true);
            $table->string('business_type', 100);
            $table->tinyInteger('status')->default(1);
            $table->unique('business_type', 'uq_business_type');
        });

        // CHECK (status IN (0,1))
        DB::statement("
            ALTER TABLE `Business_Types`
            ADD CONSTRAINT `chk_business_types_status`
            CHECK (`status` IN (0,1))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('Business_Types');
    }
};
