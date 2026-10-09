<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Life of a newsletter campaign: written, waiting for its date, going out, gone, or called off before leaving.
 */
enum CampaignStatus: string implements HasColor, HasLabel
{
    case Draft = 'brouillon';
    case Scheduled = 'programmee';
    case Sending = 'en_cours';
    case Sent = 'envoyee';
    case Cancelled = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Scheduled => 'Programmée',
            self::Sending => 'Envoi en cours',
            self::Sent => 'Envoyée',
            self::Cancelled => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'info',
            self::Sending => 'warning',
            self::Sent => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Its content can still be changed: nothing has left yet.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Scheduled;
    }
}
