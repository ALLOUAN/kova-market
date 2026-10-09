<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a courier hands the cash collected over to the store (F-126).
 */
enum RemittanceMethod: string implements HasLabel
{
    case Cash = 'especes';
    case MobileMoney = 'mobile_money';
    case Transfer = 'virement';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::MobileMoney => 'Mobile Money',
            self::Transfer => 'Virement',
        };
    }
}
