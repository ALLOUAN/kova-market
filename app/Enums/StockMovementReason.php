<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Why a variant's stock changed (F-103). Sales and releases are recorded by the order module.
 */
enum StockMovementReason: string implements HasLabel
{
    case Initial = 'initial';
    case Adjustment = 'ajustement';
    case Return = 'retour';
    case Sale = 'vente';
    case Release = 'liberation';

    public function getLabel(): string
    {
        return match ($this) {
            self::Initial => 'Stock initial',
            self::Adjustment => 'Ajustement',
            self::Return => 'Retour client',
            self::Sale => 'Vente',
            self::Release => 'Libération (commande non payée)',
        };
    }

    /**
     * Reasons a back-office user may pick when correcting a stock by hand.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return [
            self::Adjustment->value => self::Adjustment->getLabel(),
            self::Return->value => self::Return->getLabel(),
        ];
    }
}
