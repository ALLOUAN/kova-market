<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Moderation of a product review: waiting for the back-office, published, or refused.
 */
enum ReviewStatus: string implements HasColor, HasLabel
{
    case Pending = 'en_attente';
    case Approved = 'publie';
    case Rejected = 'refuse';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'À valider',
            self::Approved => 'Publié',
            self::Rejected => 'Refusé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'gray',
        };
    }
}
