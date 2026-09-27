<?php

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;

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
        $this->available = $this->product->is_active && $this->variant->stock > 0;
        $this->quantity = $this->available ? min($item->quantity, $this->variant->stock) : 0;

        $this->notice = match (true) {
            ! $this->product->is_active => 'Ce produit n’est plus en vente : il ne sera pas commandé.',
            $this->variant->stock === 0 => 'Épuisé : cet article ne sera pas commandé.',
            $item->quantity > $this->variant->stock => "Plus que {$this->variant->stock} en stock : la quantité sera ramenée à {$this->variant->stock}.",
            default => null,
        };
    }

    public function unitPrice(): int
    {
        return $this->variant->currentPrice();
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }
}
