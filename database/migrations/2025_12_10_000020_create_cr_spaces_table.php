<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cr_spaces', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('project_subtype_id');
            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();

            // Image used in Step-1 / Step-2 "Spaces" cards
            $table->string('card_image_path', 255)->nullable();

            $table->unsignedInteger('default_quantity')->nullable();
            $table->decimal('default_area_sqft', 10, 2)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_subtype_id', 'code'], 'uq_cr_spaces_subtype_code');
            $table->unique(['project_subtype_id', 'name'], 'uq_cr_spaces_subtype_name');

            $table->foreign('project_subtype_id', 'fk_cr_spaces_project_subtype')
                ->references('id')
                ->on('cr_project_subtypes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // Seed default spaces for Residential → Duplex (RES_DPX)
        /*
        $resDuplexSubtypeId = DB::table('cr_project_subtypes')
            ->where('code', 'RES_DPX')
            ->value('id');

        if ($resDuplexSubtypeId) {
            DB::table('cr_spaces')->insert([
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'MASTER_BED',
                    'name'               => 'Master Bed Room',
                    'description'        => 'Primary bedroom of the unit.',
                    'card_image_path'    => 'assets/images/cr/spaces/master-bed-room.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 220.00,
                    'is_active'          => 1,
                    'sort_order'         => 1,
                ],
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'CHILD_BED',
                    'name'               => 'Child Bed Room',
                    'description'        => 'Bedroom for child/teen.',
                    'card_image_path'    => 'assets/images/cr/spaces/child-bed-room.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 180.00,
                    'is_active'          => 1,
                    'sort_order'         => 2,
                ],
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'LIVING',
                    'name'               => 'Living / Lounge',
                    'description'        => 'Main living / lounge area.',
                    'card_image_path'    => 'assets/images/cr/spaces/living-lounge.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 260.00,
                    'is_active'          => 1,
                    'sort_order'         => 3,
                ],
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'DINING',
                    'name'               => 'Dining',
                    'description'        => 'Dedicated dining space.',
                    'card_image_path'    => 'assets/images/cr/spaces/dining.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 160.00,
                    'is_active'          => 1,
                    'sort_order'         => 4,
                ],
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'KITCHEN',
                    'name'               => 'Kitchen',
                    'description'        => 'Main kitchen space.',
                    'card_image_path'    => 'assets/images/cr/spaces/kitchen.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 140.00,
                    'is_active'          => 1,
                    'sort_order'         => 5,
                ],
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'FAMILY_LIV',
                    'name'               => 'Family Living',
                    'description'        => 'Informal family living / TV lounge.',
                    'card_image_path'    => 'assets/images/cr/spaces/family-living.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 200.00,
                    'is_active'          => 1,
                    'sort_order'         => 6,
                ],
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'LOBBY',
                    'name'               => 'Entrance Lobby',
                    'description'        => 'Entrance foyer / lobby area.',
                    'card_image_path'    => 'assets/images/cr/spaces/entrance-lobby.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 80.00,
                    'is_active'          => 1,
                    'sort_order'         => 7,
                ],
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'TOILET_ATT',
                    'name'               => 'Attached Toilet',
                    'description'        => 'Toilet attached to bedroom.',
                    'card_image_path'    => 'assets/images/cr/spaces/attached-toilet.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 60.00,
                    'is_active'          => 1,
                    'sort_order'         => 8,
                ],
                [
                    'project_subtype_id' => $resDuplexSubtypeId,
                    'code'               => 'BALCONY',
                    'name'               => 'Balcony',
                    'description'        => 'Balcony / terrace space.',
                    'card_image_path'    => 'assets/images/cr/spaces/balcony.jpg',
                    'default_quantity'   => 1,
                    'default_area_sqft'  => 50.00,
                    'is_active'          => 1,
                    'sort_order'         => 9,
                ],
            ]);
        } */
    }

    public function down(): void
    {
        Schema::dropIfExists('cr_spaces');
    }
};
