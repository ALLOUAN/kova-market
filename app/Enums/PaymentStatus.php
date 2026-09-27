<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'en_attente';
    case Paid = 'paye';
    case Cancelled = 'annule';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Paid => 'Payé',
            self::Cancelled => 'Annulé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Cancelled => 'gray',
        };
    }
}
