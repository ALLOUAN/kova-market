<?php

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\SaleQuantity;

/**
 * One cart line as displayed: current price, quantity that can really be ordered and why it may differ.
 */
class CartLine
{
    public readonly ProductVariant $variant;

    public readonly Product $product;

    /** Quantity counted in the total: never more than the stock, 0 when unavailable. */
    public readonly int $quantity;

    public readonly bool $available;

    public readonly ?string $notice;

    public function __construct(public readonly CartItem $item)
    {
        $this->variant = $item->variant;
        $this->product = $item->variant->product;
        $rules = $this->saleQuantity();
        // What the stock still allows in whole steps (0 below the minimum: 200 g left of a product sold by 250 g).
        $orderable = $rules->normalize($item->quantity, $this->variant->stock);
        $this->available = $this->product->is_active && $orderable > 0;
        $this->quantity = $this->available ? $orderable : 0;

        $this->notice = match (true) {
            ! $this->product->is_active => 'Ce produit n’est plus en vente : il ne sera pas commandé.',
            $this->variant->stock === 0 => 'Épuisé : cet article ne sera pas commandé.',
            $orderable === 0 => 'Plus que '.$rules->format($this->variant->stock).' en stock, moins que le minimum : cet article ne sera pas commandé.',
            $item->quantity > $this->variant->stock => 'Plus que '.$rules->format($this->variant->stock).' en stock : la quantité sera ramenée à '.$rules->format($orderable).'.',
            default => null,
        };
    }

    public function saleQuantity(): SaleQuantity
    {
        return $this->product->saleQuantity();
    }

    public function unitPrice(): int
    {
        return $this->variant->currentPrice();
    }

    public function total(): int
    {
        return $this->saleQuantity()->lineTotal($this->unitPrice(), $this->quantity);
    }

    /** Articles in the cart count: the pieces, or one for a line sold by weight or volume. */
    public function countedItems(): int
    {
        return $this->saleQuantity()->unit->isMeasured() ? min(1, $this->quantity) : $this->quantity;
    }

    /** "1,75 kg", "3 tas", "2": the quantity counted in the total. */
    public function quantityLabel(): string
    {
        return $this->saleQuantity()->format($this->quantity);
    }
}
