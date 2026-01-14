<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cr_project_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            // Image used for project-type cards (Step-1)
            $table->string('card_image_path', 255)->nullable();

            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
/*
        DB::table('cr_project_types')->insert([
            [
                'code'            => 'RES',
                'name'            => 'Residential',
                'description'     => 'Apartments, duplexes, villas and other residential interiors.',
                'card_image_path' => 'assets/images/cr/project_types/residential.jpg',
                'sort_order'      => 1,
            ],
            [
                'code'            => 'COM',
                'name'            => 'Commercial',
                'description'     => 'Retail, showrooms, offices and general commercial spaces.',
                'card_image_path' => 'assets/images/cr/project_types/commercial.jpg',
                'sort_order'      => 2,
            ],
            [
                'code'            => 'HOS',
                'name'            => 'Hospitality',
                'description'     => 'Hotels, resorts, serviced apartments, guest rooms.',
                'card_image_path' => 'assets/images/cr/project_types/hospitality.jpg',
                'sort_order'      => 3,
            ],
            [
                'code'            => 'INS',
                'name'            => 'Institutional',
                'description'     => 'Banks, schools, hospitals and institutional interiors.',
                'card_image_path' => 'assets/images/cr/project_types/institutional.jpg',
                'sort_order'      => 4,
            ],
        ]);  */
    }

    public function down(): void
    {
        Schema::dropIfExists('cr_project_types');
    }
};
