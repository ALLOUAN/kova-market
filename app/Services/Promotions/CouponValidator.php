<?php

namespace App\Services\Promotions;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Checks a promo code against a basket and computes the discount (F-042, F-091). The cart calls it on every
 * display, so the discount follows each change; the checkout calls it again under a lock of the code.
 */
class CouponValidator
{
    /**
     * Discount on the goods, in whole FCFA: never more than the eligible lines, a percentage rounded to the
     * unit, 0 for a free-delivery code (the delivery fee is waived instead). The per-customer cap is only
     * checked when the customer is known (account or phone typed at checkout).
     *
     * @param  Collection<int, array{product: Product, amount: int}>  $lines
     *
     * @throws CouponException
     */
    public function discount(Coupon $coupon, Collection $lines, ?string $phone = null, ?User $user = null): int
    {
        $this->ensureUsable($coupon);
        $this->ensureCustomerMayUse($coupon, $phone, $user);

        $subtotal = $lines->sum('amount');

        if ($coupon->minimum_subtotal !== null && $subtotal < $coupon->minimum_subtotal) {
            throw new CouponException('Ce code promo demande un minimum d’achat de '.Money::format($coupon->minimum_subtotal).' (hors livraison).');
        }

        $coupon->loadMissing('categories', 'products');
        $eligible = $lines->filter(fn (array $line) => $coupon->covers($line['product']))->sum('amount');

        if ($eligible === 0) {
            throw new CouponException('Aucun article de votre panier n’est concerné par ce code promo.');
        }

        return match ($coupon->type) {
            CouponType::Fixed => min($coupon->value, $eligible),
            CouponType::Percentage => min((int) round($eligible * $coupon->value / 100), $eligible),
            CouponType::FreeShipping => 0,
        };
    }

    /**
     * @throws CouponException
     */
    private function ensureUsable(Coupon $coupon): void
    {
        if (! $coupon->is_active) {
            throw new CouponException('Ce code promo n’est plus valable.');
        }

        if ($coupon->starts_at?->isFuture()) {
            throw new CouponException('Ce code promo sera valable à partir du '.$coupon->starts_at->format('d/m/Y à H\hi').'.');
        }

        if ($coupon->ends_at?->isPast()) {
            throw new CouponException('Ce code promo a expiré.');
        }

        if ($coupon->usage_limit !== null && $coupon->times_used >= $coupon->usage_limit) {
            throw new CouponException('Ce code promo a atteint son nombre maximal d’utilisations.');
        }
    }

    /**
     * @throws CouponException
     */
    private function ensureCustomerMayUse(Coupon $coupon, ?string $phone, ?User $user): void
    {
        if ($coupon->usage_limit_per_customer === null || ($phone === null && $user === null)) {
            return;
        }

        $used = CouponUsage::where('coupon_id', $coupon->getKey())
            ->where(fn (Builder $query) => $query
                ->when($phone, fn (Builder $query) => $query->orWhere('phone', $phone))
                ->when($user, fn (Builder $query) => $query->orWhere('user_id', $user->getKey())))
            ->count();

        if ($used >= $coupon->usage_limit_per_customer) {
            throw new CouponException($coupon->usage_limit_per_customer === 1
                ? 'Vous avez déjà utilisé ce code promo.'
                : "Vous avez déjà utilisé ce code promo {$coupon->usage_limit_per_customer} fois, le maximum autorisé.");
        }
    }
}
