<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How an order reaches the customer, set by the zone of the chosen destination:
 *  - Abidjan:  a KOVA courier delivers it (no "Expédiée" step: prepared, then on the way, then delivered);
 *  - Interior: shipped by carrier to the town the customer typed ("Expédiée", then delivered).
 */
enum DeliveryMode: string implements HasLabel
{
    case Abidjan = 'abidjan';
    case Interior = 'interieur';

    public function getLabel(): string
    {
        return match ($this) {
            self::Abidjan => 'Livraison à Abidjan',
            self::Interior => 'Expédition vers l’intérieur',
        };
    }

    /**
     * Steps of the normal flow, in order, for this way of delivering.
     *
     * @return list<OrderStatus>
     */
    public function flow(): array
    {
        return match ($this) {
            self::Abidjan => [OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OutForDelivery, OrderStatus::Delivered],
            self::Interior => [OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Shipped, OrderStatus::Delivered],
        };
    }

    /**
     * Delivered by the store's couriers (dispatched to the couriers of the zone).
     */
    public function usesCouriers(): bool
    {
        return $this === self::Abidjan;
    }
}
