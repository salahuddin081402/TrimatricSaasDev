<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cr_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('subcategory_id');
            $table->string('sku', 80);
            $table->string('name', 255);
            $table->string('short_description', 500)->nullable();
            $table->text('specification')->nullable();
            $table->string('origin_country', 120)->nullable();
            $table->enum('default_tag', ['preferred', 'standard', 'value', 'premium'])->nullable();
            $table->unsignedInteger('default_qty')->default(1);

            // Image used for product cards (Step-2)
            $table->string('main_image_url', 500)->nullable();

            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('sku', 'uq_cr_products_sku');

            $table->foreign('subcategory_id', 'fk_cr_products_subcategory')
                ->references('id')
                ->on('cr_item_subcategories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // Find Double Person Bed subcategory under Furniture
        $doubleBedSubcatId = DB::table('cr_item_subcategories as s')
            ->join('cr_item_categories as c', 'c.id', '=', 's.category_id')
            ->where('c.code', 'FURN')
            ->where('s.code', 'FURN_DBED')
            ->value('s.id');
/*
        if ($doubleBedSubcatId) {
            DB::table('cr_products')->insert([
                [
                    'subcategory_id'    => $doubleBedSubcatId,
                    'sku'               => 'DB-AVN-180x200',
                    'name'              => 'Avenue Solid Wood Double Bed',
                    'short_description' => 'Solid wood double bed with upholstered headboard.',
                    'specification'     => 'Solid oak + veneer; upholstered headboard; 180x200 cm bed size; recommended for master bed rooms.',
                    'origin_country'    => 'Malaysia',
                    'default_tag'       => 'preferred',
                    'default_qty'       => 1,
                    'main_image_url'    => 'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80',
                    'is_active'         => 1,
                    'sort_order'        => 1,
                ],
                [
                    'subcategory_id'    => $doubleBedSubcatId,
                    'sku'               => 'DB-LIN-160x200',
                    'name'              => 'Linea Minimal Bed with Fabric Headboard',
                    'short_description' => 'Minimal bed frame with soft fabric headboard.',
                    'specification'     => 'MDF + veneer frame; fabric panel headboard; 160x200 cm; recommended for master/child bed rooms.',
                    'origin_country'    => 'Turkey',
                    'default_tag'       => 'standard',
                    'default_qty'       => 1,
                    'main_image_url'    => 'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80',
                    'is_active'         => 1,
                    'sort_order'        => 2,
                ],
                [
                    'subcategory_id'    => $doubleBedSubcatId,
                    'sku'               => 'DB-NOV-160x200',
                    'name'              => 'Nova Storage Bed with Drawers',
                    'short_description' => 'Storage bed with four under-bed drawers.',
                    'specification'     => 'Engineered wood structure; 4 pull-out storage drawers; 160x200 cm; soft-close hardware.',
                    'origin_country'    => 'Bangladesh',
                    'default_tag'       => 'value',
                    'default_qty'       => 1,
                    'main_image_url'    => 'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80',
                    'is_active'         => 1,
                    'sort_order'        => 3,
                ],
            ]);
        }  */
    }

    public function down(): void
    {
        Schema::dropIfExists('cr_products');
    }
};
