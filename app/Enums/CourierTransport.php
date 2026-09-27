<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a courier travels (F-122).
 */
enum CourierTransport: string implements HasLabel
{
    case Motorbike = 'moto';
    case Car = 'voiture';
    case Bicycle = 'velo';
    case Tricycle = 'tricycle';
    case OnFoot = 'pied';

    public function getLabel(): string
    {
        return match ($this) {
            self::Motorbike => 'Moto',
            self::Car => 'Voiture',
            self::Bicycle => 'Vélo',
            self::Tricycle => 'Tricycle',
            self::OnFoot => 'À pied',
        };
    }
}
