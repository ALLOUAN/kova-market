<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Order life cycle (F-121): Reçue → Confirmée → En préparation → Expédiée → En livraison → Livrée,
 * cancellation possible before shipping and when a delivery fails.
 */
enum OrderStatus: string implements HasColor, HasLabel
{
    case Received = 'recue';
    case Confirmed = 'confirmee';
    case Preparing = 'en_preparation';
    case Shipped = 'expediee';
    case OutForDelivery = 'en_livraison';
    case Delivered = 'livree';
    case Cancelled = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Received => 'Reçue',
            self::Confirmed => 'Confirmée',
            self::Preparing => 'En préparation',
            self::Shipped => 'Expédiée',
            self::OutForDelivery => 'En livraison',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Received => 'warning',
            self::Confirmed, self::Preparing => 'info',
            self::Shipped, self::OutForDelivery => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Statuses reachable from this one in the normal flow.
     *
     * @return list<self>
     */
    public function next(): array
    {
        return match ($this) {
            self::Received => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::OutForDelivery],
            self::OutForDelivery => [self::Delivered, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canBecome(self $status): bool
    {
        return in_array($status, $this->next(), true);
    }

    public function isFinal(): bool
    {
        return $this->next() === [];
    }
}
