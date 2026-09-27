<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * What a promo code gives (F-091).
 */
enum CouponType: string implements HasLabel
{
    case Fixed = 'montant';
    case Percentage = 'pourcentage';
    case FreeShipping = 'livraison_offerte';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'Montant fixe',
            self::Percentage => 'Pourcentage',
            self::FreeShipping => 'Livraison offerte',
        };
    }
}
