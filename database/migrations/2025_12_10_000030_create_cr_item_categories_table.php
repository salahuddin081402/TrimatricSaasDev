<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cr_item_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            // Image used in Step-2 "Item Category" cards
            $table->string('card_image_path', 255)->nullable();

            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
  /*
        DB::table('cr_item_categories')->insert([
            [
                'code'            => 'FURN',
                'name'            => 'Furniture',
                'description'     => 'All loose and built-in furniture items.',
                'card_image_path' => 'assets/images/cr/categories/furniture.jpg',
                'sort_order'      => 1,
            ],
            [
                'code'            => 'LIGHT',
                'name'            => 'Lighting',
                'description'     => 'Ceiling, wall, pendant and task lights.',
                'card_image_path' => 'assets/images/cr/categories/lighting.jpg',
                'sort_order'      => 2,
            ],
            [
                'code'            => 'CURTAIN',
                'name'            => 'Curtains & Fabrics',
                'description'     => 'Curtains, blinds, bed runners, cushions.',
                'card_image_path' => 'assets/images/cr/categories/curtains-fabrics.jpg',
                'sort_order'      => 3,
            ],
            [
                'code'            => 'WALL_TREAT',
                'name'            => 'Wall Treatment',
                'description'     => 'Paint, wallpaper and wall panels.',
                'card_image_path' => 'assets/images/cr/categories/wall-treatment.jpg',
                'sort_order'      => 4,
            ],
            [
                'code'            => 'FLOOR_FIN',
                'name'            => 'Floor Finish',
                'description'     => 'Tiles, laminate, wood flooring, rugs.',
                'card_image_path' => 'assets/images/cr/categories/floor-finish.jpg',
                'sort_order'      => 5,
            ],
            [
                'code'            => 'CEILING',
                'name'            => 'Ceiling Design',
                'description'     => 'False ceiling, coves, feature ceilings.',
                'card_image_path' => 'assets/images/cr/categories/ceiling-design.jpg',
                'sort_order'      => 6,
            ],
            [
                'code'            => 'DECOR',
                'name'            => 'Decor & Accessories',
                'description'     => 'Art, mirrors, decor accessories.',
                'card_image_path' => 'assets/images/cr/categories/decor-accessories.jpg',
                'sort_order'      => 7,
            ],
        ]);  */
    }

    public function down(): void
    {
        Schema::dropIfExists('cr_item_categories');
    }
};
