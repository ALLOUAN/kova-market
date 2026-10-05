<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Order life cycle (F-121), set by the delivery mode (DeliveryMode):
 *  - Abidjan:  Reçue → Confirmée → En préparation → En livraison → Livrée;
 *  - Interior: Reçue → Confirmée → En préparation → Expédiée → Livrée.
 * Cancellation possible before shipping and when a delivery fails.
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
     * Statuses reachable from this one in the normal flow, which depends on how the order is delivered:
     * in Abidjan the courier leaves straight from preparation ("En livraison"), towards the interior the parcel
     * is shipped ("Expédiée") then delivered. An Abidjan order left "Expédiée" by the former flow can still leave.
     *
     * @return list<self>
     */
    public function next(DeliveryMode $mode = DeliveryMode::Abidjan): array
    {
        $interior = $mode === DeliveryMode::Interior;

        return match ($this) {
            self::Received => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [$interior ? self::Shipped : self::OutForDelivery, self::Cancelled],
            self::Shipped => [$interior ? self::Delivered : self::OutForDelivery],
            self::OutForDelivery => [self::Delivered, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    /**
     * Step before this one in the normal flow of this delivery mode (target of a super-admin correction).
     */
    public function previous(DeliveryMode $mode = DeliveryMode::Abidjan): ?self
    {
        if ($this === self::Cancelled) {
            return null;
        }

        $flow = $mode->flow();
        $index = array_search($this, $flow, true);

        // A status outside this flow (Abidjan order "Expédiée" by the former flow) goes back to preparation.
        return $index === false ? self::Preparing : ($flow[$index - 1] ?? null);
    }

    public function canBecome(self $status, DeliveryMode $mode = DeliveryMode::Abidjan): bool
    {
        return in_array($status, $this->next($mode), true);
    }

    public function isFinal(): bool
    {
        return $this->next() === [];
    }
}
