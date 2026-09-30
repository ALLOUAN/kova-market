<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sale units (App\Enums\SaleUnit): a product is sold by the piece, by weight, by volume, by pack, by lot or in a
 * local unit, with a minimum, a step and a ceiling in base units (grams, millilitres, units). Cart and order
 * quantities count base units, so they widen past 65 535 (65 kg in grams); an order line keeps its unit, so
 * "1 750" still reads "1,75 kg" after the product changes. Existing products stay "piece": nothing to convert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sale_unit', 20)->default('piece')->after('stock');
            $table->string('unit_label', 30)->nullable()->after('sale_unit');
            $table->unsignedInteger('min_quantity')->nullable()->after('unit_label');
            $table->unsignedInteger('quantity_step')->nullable()->after('min_quantity');
            $table->unsignedInteger('max_quantity')->nullable()->after('quantity_step');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
            $table->string('sale_unit', 20)->default('piece')->after('quantity');
            $table->string('unit_label', 30)->nullable()->after('sale_unit');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['sale_unit', 'unit_label']);
            $table->unsignedSmallInteger('quantity')->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sale_unit', 'unit_label', 'min_quantity', 'quantity_step', 'max_quantity']);
        });
    }
};
