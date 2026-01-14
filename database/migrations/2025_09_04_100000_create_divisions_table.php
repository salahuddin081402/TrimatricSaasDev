<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('divisions')) return;

        Schema::create('divisions', function (Blueprint $table) {
            $table->id()->comment('Division ID');
            $table->string('name', 100)->comment('Division name');
            $table->char('short_code', 3)->comment('3-char code, e.g. DHK');
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('name', 'uq_divisions_name');
            $table->unique('short_code', 'uq_divisions_short_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
