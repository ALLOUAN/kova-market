<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Commune;
use Illuminate\Support\Collection;

/**
 * What the customer owes for a cart right now (F-044): current variant prices, unavailable lines left out,
 * delivery fee of the chosen commune (free above the threshold). Amounts in whole FCFA.
 */
class CartSummary
{
    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public function __construct(
        public readonly ?Cart $cart,
        public readonly Collection $lines,
        public readonly int $subtotal,
        public readonly ?Commune $commune,
        public readonly ?int $shippingFee,
        public readonly bool $freeShipping,
        public readonly ?int $freeShippingThreshold,
    ) {}

    /**
     * Units that will actually be ordered.
     */
    public function count(): int
    {
        return $this->lines->filter->available->sum->quantity;
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    /**
     * Total to pay; the delivery fee is only known once a commune is chosen.
     */
    public function total(): int
    {
        return $this->subtotal + ($this->shippingFee ?? 0);
    }

    /**
     * Amount still to add to get free delivery, when a threshold is set and not reached.
     */
    public function missingForFreeShipping(): ?int
    {
        if ($this->freeShippingThreshold === null || $this->subtotal >= $this->freeShippingThreshold) {
            return null;
        }

        return $this->freeShippingThreshold - $this->subtotal;
    }
}
