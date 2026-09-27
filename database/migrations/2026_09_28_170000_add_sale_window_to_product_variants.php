<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dated sale prices (F-090): a variant's reduced price ("price" below "compare_at_price") only applies between
     * sale_starts_at and sale_ends_at; outside the window the customer pays the compare price. The product keeps
     * the window of its cheapest variant as a summary (countdown). The existing end date of each product is
     * copied to its discounted variants, so the countdown and the price now end together.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dateTime('sale_starts_at')->nullable()->after('compare_at_price');
            $table->dateTime('sale_ends_at')->nullable()->after('sale_starts_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dateTime('sale_starts_at')->nullable()->after('compare_at_price');
        });

        DB::table('products')->whereNotNull('sale_ends_at')->orderBy('id')->each(function (object $product): void {
            DB::table('product_variants')
                ->where('product_id', $product->id)
                ->whereColumn('compare_at_price', '>', 'price')
                ->update(['sale_ends_at' => $product->sale_ends_at]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sale_starts_at');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['sale_starts_at', 'sale_ends_at']);
        });
    }
};
