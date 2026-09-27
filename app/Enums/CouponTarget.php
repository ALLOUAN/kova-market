<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Which cart lines a promo code applies to (F-091).
 */
enum CouponTarget: string implements HasLabel
{
    case All = 'tout';
    case Categories = 'categories';
    case Products = 'produits';

    public function getLabel(): string
    {
        return match ($this) {
            self::All => 'Tout le catalogue',
            self::Categories => 'Certaines catégories',
            self::Products => 'Certains produits',
        };
    }
}
