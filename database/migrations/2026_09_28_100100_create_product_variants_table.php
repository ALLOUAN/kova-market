<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sellable variants (F-035): each has its SKU, prices in whole FCFA and its own stock.
     * Every product has at least one variant, the default one.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            $table->unsignedInteger('price');
            $table->unsignedInteger('compare_at_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedSmallInteger('low_stock_threshold')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'position']);
        });

        Schema::create('attribute_value_product_variant', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->restrictOnDelete();

            $table->primary(['product_variant_id', 'attribute_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product_variant');
        Schema::dropIfExists('product_variants');
    }
};
