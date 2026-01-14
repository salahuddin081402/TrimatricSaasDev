<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cr_project_subtypes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('project_type_id');
            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();

            // Image for sub-type cards (Step-1)
            $table->string('card_image_path', 255)->nullable();

            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code', 'uq_project_subtype_code');
            $table->unique(['project_type_id', 'name'], 'uq_project_subtype_type_name');

            $table->foreign('project_type_id', 'fk_cr_project_subtypes_project_type')
                ->references('id')
                ->on('cr_project_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // Seed Residential sub-types
        /*
        $resId = DB::table('cr_project_types')->where('code', 'RES')->value('id');
        if ($resId) {
            DB::table('cr_project_subtypes')->insert([
                [
                    'project_type_id'  => $resId,
                    'code'             => 'RES_APT',
                    'name'             => 'Apartment',
                    'description'      => 'Standard residential apartment unit.',
                    'card_image_path'  => 'assets/images/cr/project_subtypes/res-apartment.jpg',
                    'sort_order'       => 1,
                ],
                [
                    'project_type_id'  => $resId,
                    'code'             => 'RES_DPX',
                    'name'             => 'Duplex',
                    'description'      => 'Duplex / triplex type residential unit.',
                    'card_image_path'  => 'assets/images/cr/project_subtypes/res-duplex.jpg',
                    'sort_order'       => 2,
                ],
                [
                    'project_type_id'  => $resId,
                    'code'             => 'RES_VIL',
                    'name'             => 'Villa / Bungalow',
                    'description'      => 'Independent villa or bungalow.',
                    'card_image_path'  => 'assets/images/cr/project_subtypes/res-villa.jpg',
                    'sort_order'       => 3,
                ],
            ]);
        }  

        // Sample Commercial sub-types
        $comId = DB::table('cr_project_types')->where('code', 'COM')->value('id');
        if ($comId) {
            DB::table('cr_project_subtypes')->insert([
                [
                    'project_type_id'  => $comId,
                    'code'             => 'COM_OFF',
                    'name'             => 'Office / Corporate',
                    'description'      => 'Corporate office interior.',
                    'card_image_path'  => 'assets/images/cr/project_subtypes/com-office.jpg',
                    'sort_order'       => 1,
                ],
                [
                    'project_type_id'  => $comId,
                    'code'             => 'COM_RET',
                    'name'             => 'Retail Showroom',
                    'description'      => 'Retail / showroom interior.',
                    'card_image_path'  => 'assets/images/cr/project_subtypes/com-retail.jpg',
                    'sort_order'       => 2,
                ],
            ]);
        }

        // Sample Hospitality sub-type
        $hosId = DB::table('cr_project_types')->where('code', 'HOS')->value('id');
        if ($hosId) {
            DB::table('cr_project_subtypes')->insert([
                [
                    'project_type_id'  => $hosId,
                    'code'             => 'HOS_GUEST',
                    'name'             => 'Hotel Guest Room',
                    'description'      => 'Standard hotel guest room.',
                    'card_image_path'  => 'assets/images/cr/project_subtypes/hos-guest-room.jpg',
                    'sort_order'       => 1,
                ],
            ]);
        }   */
    }

    public function down(): void
    {
        Schema::dropIfExists('cr_project_subtypes');
    }
};
