<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Packs (F-093): a pack is a product ("is_bundle") sold at one price, made of variants of other products with
     * their quantities. Its stock is derived from its components; selling it takes the components' stock. Order
     * lines keep the pack contents as they were when ordered.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_bundle')->default(false)->after('is_active');
        });

        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('products')->cascadeOnDelete();
            // A variant sold in a pack cannot be deleted while the pack uses it.
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['bundle_id', 'product_variant_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('bundle_contents')->nullable()->after('variant_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('bundle_contents');
        });

        Schema::dropIfExists('bundle_items');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_bundle');
        });
    }
};
