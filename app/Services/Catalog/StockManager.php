<?php

namespace App\Services\Catalog;

use App\Enums\StockMovementReason;
use App\Events\BackInStock;
use App\Events\StockLow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only way a variant's stock changes (F-103): every change is recorded as a stock movement,
 * inside a transaction that locks the variant row, so concurrent orders can never oversell (F-055).
 */
class StockManager
{
    /**
     * Adds (positive) or removes (negative) units.
     *
     * @throws InsufficientStock when the stock would go below zero
     */
    public function adjust(ProductVariant $variant, int $quantity, StockMovementReason $reason, ?User $user = null, ?string $note = null): StockMovement
    {
        return DB::transaction(function () use ($variant, $quantity, $reason, $user, $note): StockMovement {
            /** @var ProductVariant $locked */
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->getKey());

            $stockAfter = $locked->stock + $quantity;

            if ($stockAfter < 0) {
                throw new InsufficientStock($locked, abs($quantity));
            }

            $stockBefore = $locked->stock;
            $locked->forceFill(['stock' => $stockAfter])->save();
            $variant->setRawAttributes($locked->getAttributes(), true);

            // Alert once, when the stock crosses the threshold going down (not on every later sale).
            if ($stockBefore > $locked->lowStockThreshold() && $stockAfter <= $locked->lowStockThreshold()) {
                StockLow::dispatch($locked);
            }

            if ($stockBefore === 0 && $stockAfter > 0) {
                BackInStock::dispatch($locked);
            }

            return $locked->stockMovements()->create([
                'quantity' => $quantity,
                'stock_after' => $stockAfter,
                'reason' => $reason,
                'note' => $note,
                'user_id' => $user?->getKey(),
            ]);
        });
    }

    /**
     * Sets the stock to a counted quantity (inventory), recording the difference.
     */
    public function setTo(ProductVariant $variant, int $counted, StockMovementReason $reason, ?User $user = null, ?string $note = null): ?StockMovement
    {
        $difference = $counted - $variant->fresh()->stock;

        return $difference === 0 ? null : $this->adjust($variant, $difference, $reason, $user, $note);
    }

    /**
     * Gives a product its default variant from the product's own prices and stock, with an "initial" movement.
     * The product row is left as it is (same rule as the data migration of existing products).
     */
    public function createDefaultVariant(Product $product, ?string $sku = null, ?User $user = null): ProductVariant
    {
        return DB::transaction(fn () => ProductVariant::withoutEvents(function () use ($product, $sku, $user): ProductVariant {
            $variant = $product->variants()->create([
                'sku' => $sku ?: self::defaultSku($product),
                'price' => $product->price,
                'compare_at_price' => $product->compare_at_price,
                'is_default' => true,
            ]);

            $variant->forceFill(['stock' => $product->stock])->save();

            $variant->stockMovements()->create([
                'quantity' => $product->stock,
                'stock_after' => $product->stock,
                'reason' => StockMovementReason::Initial,
                'user_id' => $user?->getKey(),
            ]);

            return $variant;
        }));
    }

    public static function defaultSku(Product $product): string
    {
        return 'KM-'.str_pad((string) $product->getKey(), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Creates a variant with its opening stock recorded as an "initial" movement.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createVariant(Product $product, array $attributes, int $openingStock = 0, ?User $user = null): ProductVariant
    {
        return DB::transaction(function () use ($product, $attributes, $openingStock, $user): ProductVariant {
            $variant = $product->variants()->create($attributes);

            if ($openingStock > 0) {
                $this->adjust($variant, $openingStock, StockMovementReason::Initial, $user);
            }

            return $variant;
        });
    }
}
