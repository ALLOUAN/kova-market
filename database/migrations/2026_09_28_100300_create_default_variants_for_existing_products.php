<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data migration (decision C-07): every existing product gets its default variant, carrying the product's
 * current prices and stock, plus an "initial" stock movement. Product rows are not modified, so the
 * storefront displays exactly what it displayed before. Re-running skips products that already have variants.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->whereNotExists(fn ($query) => $query->from('product_variants')->whereColumn('product_variants.product_id', 'products.id'))
            ->orderBy('id')
            ->each(function (object $product): void {
                $now = now();

                $variantId = DB::table('product_variants')->insertGetId([
                    'product_id' => $product->id,
                    'sku' => 'KM-'.str_pad((string) $product->id, 6, '0', STR_PAD_LEFT),
                    'price' => $product->price,
                    'compare_at_price' => $product->compare_at_price,
                    'stock' => $product->stock,
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('stock_movements')->insert([
                    'product_variant_id' => $variantId,
                    'quantity' => $product->stock,
                    'stock_after' => $product->stock,
                    'reason' => 'initial',
                    'note' => 'Reprise du stock existant',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    /**
     * Removes the variants created here (their movements go with them); product rows were never changed.
     */
    public function down(): void
    {
        DB::table('product_variants')->where('is_default', true)->delete();
    }
};
