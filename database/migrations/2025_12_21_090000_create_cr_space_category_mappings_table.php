<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cr_space_category_mappings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('space_id');
            $table->unsignedBigInteger('category_id');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Uniqueness: prevent duplicate mapping per tenant
            $table->unique(['company_id', 'space_id', 'category_id'], 'uq_space_category');

            // Indexes for filtering performance
            $table->index(['company_id', 'space_id'], 'idx_company_space');
            $table->index(['company_id', 'category_id'], 'idx_company_cat');
            $table->index(['space_id', 'category_id'], 'idx_space_cat');

            // FKs (tight)
            $table->foreign('company_id', 'fk_scmap_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('space_id', 'fk_scmap_space')
                ->references('id')->on('cr_spaces')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('category_id', 'fk_scmap_category')
                ->references('id')->on('cr_item_categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('cr_space_category_mappings', function (Blueprint $table) {
            $table->dropForeign('fk_scmap_category');
            $table->dropForeign('fk_scmap_space');
            $table->dropForeign('fk_scmap_company');

            $table->dropUnique('uq_space_category');
            $table->dropIndex('idx_space_cat');
            $table->dropIndex('idx_company_cat');
            $table->dropIndex('idx_company_space');
        });

        Schema::dropIfExists('cr_space_category_mappings');
    }
};
