<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Subjects offered on the contact page (F-080).
 */
enum ContactSubject: string implements HasLabel
{
    case Order = 'commande';
    case Product = 'produit';
    case Delivery = 'livraison';
    case ReturnRequest = 'retour';
    case Partnership = 'partenariat';
    case Other = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::Order => 'Une commande',
            self::Product => 'Un produit',
            self::Delivery => 'La livraison',
            self::ReturnRequest => 'Un retour ou un remboursement',
            self::Partnership => 'Un partenariat',
            self::Other => 'Autre chose',
        };
    }
}
