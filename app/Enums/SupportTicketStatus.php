<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where an assistance request stands: waiting for the team, answered (the customer's turn), or closed.
 */
enum SupportTicketStatus: string implements HasColor, HasLabel
{
    case Open = 'ouvert';
    case Answered = 'repondu';
    case Closed = 'ferme';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'À traiter',
            self::Answered => 'Répondu',
            self::Closed => 'Fermé',
        };
    }

    /**
     * The same state seen from the customer area.
     */
    public function customerLabel(): string
    {
        return match ($this) {
            self::Open => 'En attente de réponse',
            self::Answered => 'Réponse reçue',
            self::Closed => 'Fermée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Answered => 'success',
            self::Closed => 'gray',
        };
    }
}
