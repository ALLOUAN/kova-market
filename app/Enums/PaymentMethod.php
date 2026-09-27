<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How an order is paid (F-060 to F-063). Mobile Money through CinetPay joins with the payment module.
 */
enum PaymentMethod: string implements HasLabel
{
    case CashOnDelivery = 'paiement_livraison';

    public function getLabel(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Paiement à la livraison',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Vous payez en espèces ou par Mobile Money au livreur, à la réception.',
        };
    }
}
