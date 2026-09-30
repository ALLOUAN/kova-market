<?php

namespace App\Services\Cart;

use App\Enums\CouponType;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\Coupon;
use Illuminate\Support\Collection;

/**
 * What the customer owes for a cart right now (F-044): current variant prices, unavailable lines left out,
 * promo code discount (F-042), delivery fee of the chosen commune (free above the threshold or with a
 * free-delivery code). Amounts in whole FCFA.
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
        public readonly ?Coupon $coupon = null,
        public readonly int $discount = 0,
        public readonly ?string $couponIssue = null,
    ) {}

    /**
     * Units that will actually be ordered.
     */
    public function count(): int
    {
        // Articles, not grams: a line sold by weight or volume counts as one.
        return $this->lines->filter->available->sum->countedItems();
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    /**
     * Whether the cart's promo code currently gives its advantage.
     */
    public function couponApplies(): bool
    {
        return $this->coupon !== null && $this->couponIssue === null;
    }

    public function hasFreeShippingCoupon(): bool
    {
        return $this->couponApplies() && $this->coupon->type === CouponType::FreeShipping;
    }

    /**
     * Total to pay: subtotal − discount + delivery; the delivery fee is only known once a commune is chosen.
     */
    public function total(): int
    {
        return $this->subtotal - $this->discount + ($this->shippingFee ?? 0);
    }

    /**
     * Amount still to add to get free delivery, when a threshold is set and not reached.
     */
    public function missingForFreeShipping(): ?int
    {
        if ($this->hasFreeShippingCoupon() || $this->freeShippingThreshold === null || $this->subtotal >= $this->freeShippingThreshold) {
            return null;
        }

        return $this->freeShippingThreshold - $this->subtotal;
    }
}
