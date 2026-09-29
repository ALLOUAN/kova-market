<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * State of one online payment attempt (F-060 to F-067). Initiated: the customer was sent to CinetPay; pending:
 * CinetPay reports the payment under way; then succeeded, failed or cancelled; refunded is recorded afterwards.
 */
enum TransactionStatus: string implements HasColor, HasLabel
{
    case Initiated = 'initie';
    case Pending = 'en_attente';
    case Succeeded = 'reussi';
    case Failed = 'echoue';
    case Cancelled = 'annule';
    case Refunded = 'rembourse';

    public function getLabel(): string
    {
        return match ($this) {
            self::Initiated => 'Initié',
            self::Pending => 'En attente',
            self::Succeeded => 'Réussi',
            self::Failed => 'Échoué',
            self::Cancelled => 'Annulé',
            self::Refunded => 'Remboursé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Initiated, self::Pending => 'warning',
            self::Succeeded => 'success',
            self::Failed => 'danger',
            self::Cancelled, self::Refunded => 'gray',
        };
    }

    /**
     * Still waiting for CinetPay's final answer.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Initiated, self::Pending], true);
    }
}
