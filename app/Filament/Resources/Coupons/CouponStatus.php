<?php

namespace App\Filament\Resources\Coupons;

use Illuminate\Database\Eloquent\Builder;

/**
 * Where a promo code stands, for the list's header band and tabs: usable now, scheduled, or over (end date passed
 * or usage limit reached). A switched-off code is none of them until it is switched back on.
 */
final class CouponStatus
{
    public static function usable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn (Builder $query) => $query->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit'));
    }

    public static function scheduled(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('starts_at')->where('starts_at', '>', now());
    }

    public static function over(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where(fn (Builder $query) => $query->whereNotNull('ends_at')->where('ends_at', '<', now()))
            ->orWhere(fn (Builder $query) => $query->whereNotNull('usage_limit')->whereColumn('times_used', '>=', 'usage_limit')));
    }
}
