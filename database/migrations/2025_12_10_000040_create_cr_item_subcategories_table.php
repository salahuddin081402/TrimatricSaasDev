<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cr_item_subcategories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id');
            $table->string('code', 80);
            $table->string('name', 180);
            $table->text('description')->nullable();

            // Image used in Step-2 "Sub-Category" cards
            $table->string('card_image_path', 255)->nullable();

            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code', 'uq_item_subcat_code');
            $table->unique(['category_id', 'name'], 'uq_item_subcat_cat_name');

            $table->foreign('category_id', 'fk_cr_item_subcategories_category')
                ->references('id')
                ->on('cr_item_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        $furnId    = DB::table('cr_item_categories')->where('code', 'FURN')->value('id');
        $lightId   = DB::table('cr_item_categories')->where('code', 'LIGHT')->value('id');
        $curtainId = DB::table('cr_item_categories')->where('code', 'CURTAIN')->value('id');
        $wallId    = DB::table('cr_item_categories')->where('code', 'WALL_TREAT')->value('id');
/*
        if ($furnId) {
            DB::table('cr_item_subcategories')->insert([
                [
                    'category_id'     => $furnId,
                    'code'            => 'FURN_DBED',
                    'name'            => 'Double Person Bed',
                    'description'     => 'King/Queen size double bed options.',
                    'card_image_path' => 'assets/images/cr/subcategories/double-bed.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 1,
                ],
                [
                    'category_id'     => $furnId,
                    'code'            => 'FURN_SBEB',
                    'name'            => 'Single Bed',
                    'description'     => 'Single bed for child/guest rooms.',
                    'card_image_path' => 'assets/images/cr/subcategories/single-bed.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 2,
                ],
                [
                    'category_id'     => $furnId,
                    'code'            => 'FURN_WARD',
                    'name'            => 'Wardrobe',
                    'description'     => 'Sliding, hinged and walk-in wardrobe modules.',
                    'card_image_path' => 'assets/images/cr/subcategories/wardrobe.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 3,
                ],
                [
                    'category_id'     => $furnId,
                    'code'            => 'FURN_BSTBL',
                    'name'            => 'Bedside Table',
                    'description'     => 'Bedside tables with drawers/shelves.',
                    'card_image_path' => 'assets/images/cr/subcategories/bedside-table.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 4,
                ],
                [
                    'category_id'     => $furnId,
                    'code'            => 'FURN_STDSK',
                    'name'            => 'Study Desk',
                    'description'     => 'Compact desks for reading and work.',
                    'card_image_path' => 'assets/images/cr/subcategories/study-desk.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 5,
                ],
            ]);
        }

        if ($lightId) {
            DB::table('cr_item_subcategories')->insert([
                [
                    'category_id'     => $lightId,
                    'code'            => 'LIGHT_AMB_CEIL',
                    'name'            => 'Ambient Ceiling Light',
                    'description'     => 'Primary ceiling lights for general illumination.',
                    'card_image_path' => 'assets/images/cr/subcategories/ambient-ceiling-light.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 1,
                ],
                [
                    'category_id'     => $lightId,
                    'code'            => 'LIGHT_WALL',
                    'name'            => 'Wall Sconce',
                    'description'     => 'Decorative wall-mounted lighting.',
                    'card_image_path' => 'assets/images/cr/subcategories/wall-sconce.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 2,
                ],
                [
                    'category_id'     => $lightId,
                    'code'            => 'LIGHT_PEND',
                    'name'            => 'Pendant Light',
                    'description'     => 'Pendant fixtures over bed, dining or island.',
                    'card_image_path' => 'assets/images/cr/subcategories/pendant-light.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 3,
                ],
            ]);
        }

        if ($curtainId) {
            DB::table('cr_item_subcategories')->insert([
                [
                    'category_id'     => $curtainId,
                    'code'            => 'CURT_WIN',
                    'name'            => 'Window Curtain',
                    'description'     => 'Full height curtains for windows/doors.',
                    'card_image_path' => 'assets/images/cr/subcategories/window-curtain.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 1,
                ],
                [
                    'category_id'     => $curtainId,
                    'code'            => 'CURT_BLIND',
                    'name'            => 'Roller Blind',
                    'description'     => 'Roller / zebra blinds.',
                    'card_image_path' => 'assets/images/cr/subcategories/roller-blind.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 2,
                ],
            ]);
        }

        if ($wallId) {
            DB::table('cr_item_subcategories')->insert([
                [
                    'category_id'     => $wallId,
                    'code'            => 'WALL_PAINT',
                    'name'            => 'Paint Finish',
                    'description'     => 'Standard and special paint finishes.',
                    'card_image_path' => 'assets/images/cr/subcategories/paint-finish.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 1,
                ],
                [
                    'category_id'     => $wallId,
                    'code'            => 'WALL_WPAPER',
                    'name'            => 'Wallpaper',
                    'description'     => 'Imported/local wallpaper.',
                    'card_image_path' => 'assets/images/cr/subcategories/wallpaper.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 2,
                ],
                [
                    'category_id'     => $wallId,
                    'code'            => 'WALL_PANEL',
                    'name'            => 'Wall Paneling',
                    'description'     => 'Wood/MDF/PU panel systems.',
                    'card_image_path' => 'assets/images/cr/subcategories/wall-paneling.jpg',
                    'is_active'       => 1,
                    'sort_order'      => 3,
                ],
            ]);
        } */
    }

    public function down(): void
    {
        Schema::dropIfExists('cr_item_subcategories');
    }
};
