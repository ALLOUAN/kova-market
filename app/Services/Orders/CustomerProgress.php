<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Carbon;

/**
 * The steps shown to the customer after ordering, worked out from the order's real status: each step is ticked off
 * by the person who takes it (manager confirming, picker preparing, courier or carrier delivering), with its time.
 *
 *  - Abidjan:  Commande reçue → Confirmation (or Paiement en ligne) → Préparation → Livraison;
 *  - Interior: Commande reçue → Confirmation (or Paiement en ligne) → Préparation → Expédiée → Livraison,
 *              the parcel then travels to the town the customer typed.
 */
class CustomerProgress
{
    /** Position of each status in the flows ("Expédiée" and "En livraison" never meet in one order). */
    private const RANK = [
        'recue' => 0,
        'confirmee' => 1,
        'en_preparation' => 2,
        'expediee' => 3,
        'en_livraison' => 3,
        'livree' => 5,
    ];

    /**
     * @return list<array{key: string, label: string, icon: string, detail: string, at: ?Carbon, state: string}>
     */
    public function steps(Order $order): array
    {
        $order->loadMissing(['statusHistory', 'courier.user', 'commune.zone']);
        $rank = self::RANK[$order->status->value] ?? 0;
        $online = $order->payment_method->isOnline();
        $paid = in_array($order->payment_status, [PaymentStatus::Paid, PaymentStatus::Refunded], true);
        $interior = $order->isInterior();

        $steps = [
            [
                'key' => 'received',
                'label' => 'Commande reçue',
                'icon' => 'fa-check',
                'done' => true,
                'at' => $order->created_at,
                'detail' => null,
            ],
            $online ? [
                'key' => 'payment',
                'label' => 'Paiement en ligne',
                'icon' => 'fa-credit-card',
                'done' => $paid,
                'at' => $paid ? $order->payments()->whereNotNull('paid_at')->latest('paid_at')->value('paid_at') : null,
                'detail' => $paid ? 'Reçu' : 'En attente de votre paiement',
            ] : [
                'key' => 'confirmation',
                'label' => 'Confirmation',
                'icon' => 'fa-phone',
                'done' => $rank >= 1,
                'at' => $this->reachedAt($order, OrderStatus::Confirmed),
                'detail' => $rank >= 1 ? 'Commande confirmée' : 'Nous vous appelons au '.$order->formattedPhone(),
            ],
            [
                'key' => 'preparation',
                'label' => 'Préparation',
                'icon' => 'fa-box-open',
                'done' => $rank >= 3,
                // Ready when it left: shipped (interior), handed to the courier on the way (Abidjan).
                'at' => $this->reachedAt($order, $interior ? OrderStatus::Shipped : OrderStatus::OutForDelivery)
                    ?? $this->reachedAt($order, OrderStatus::Shipped),
                'detail' => match (true) {
                    $rank >= 3 => 'Vos articles sont prêts',
                    $rank === 2 => 'Vos articles sont en cours de préparation',
                    default => 'Vos articles seront préparés',
                },
            ],
        ];

        if ($interior) {
            $steps[] = [
                'key' => 'shipped',
                'label' => 'Expédiée',
                'icon' => 'fa-truck-ramp-box',
                'done' => $rank >= 3,
                'at' => $this->reachedAt($order, OrderStatus::Shipped),
                'detail' => $rank >= 3 ? "Expédiée vers {$order->destination_city}" : "Expédition vers {$order->destination_city}",
            ];
        }

        $steps[] = [
            'key' => 'delivery',
            'label' => 'Livraison',
            'icon' => 'fa-truck-fast',
            'done' => $rank >= 5,
            'at' => $this->reachedAt($order, OrderStatus::Delivered),
            'detail' => $interior ? $this->interiorDeliveryDetail($order, $rank) : $this->abidjanDeliveryDetail($order, $rank),
        ];

        // The first step not done yet is the one under way.
        $current = collect($steps)->search(fn (array $step) => ! $step['done']);

        return array_map(fn (array $step, int $index) => [
            'key' => $step['key'],
            'label' => $step['label'],
            'icon' => $step['icon'],
            'at' => $step['at'] ? Carbon::parse($step['at']) : null,
            'detail' => $step['detail'] ?? '',
            'state' => $step['done'] ? 'done' : ($index === $current ? 'current' : 'upcoming'),
        ], $steps, array_keys($steps));
    }

    /**
     * Delivered or cancelled: the steps will not move any more (the page stops checking).
     */
    public function isSettled(Order $order): bool
    {
        return $order->status->isFinal();
    }

    private function abidjanDeliveryDetail(Order $order, int $rank): string
    {
        // Customers only see the courier's first name.
        $courier = $order->courier?->user?->name ? strtok($order->courier->user->name, ' ') : null;

        return match (true) {
            $rank >= 5 => 'Livrée à '.$order->commune_name,
            $order->status === OrderStatus::OutForDelivery => ($courier ? "{$courier} est en route" : 'Votre livreur est en route').' vers '.$order->commune_name,
            // An order left "Expédiée" by the former flow.
            $rank === 3 => $courier ? "Confiée à votre livreur {$courier}" : 'Prête à partir en livraison',
            default => $order->commune_name.$this->delay($order),
        };
    }

    private function interiorDeliveryDetail(Order $order, int $rank): string
    {
        return match (true) {
            $rank >= 5 => 'Remise à '.$order->destination_city,
            $rank === 3 => 'En cours d’acheminement vers '.$order->destination_city.$this->delay($order),
            default => $order->destination_city.$this->delay($order),
        };
    }

    private function delay(Order $order): string
    {
        $delay = $order->commune?->zone?->delay_label;

        return $delay ? ' · '.$delay : '';
    }

    /**
     * Last time the order reached this status (a correction may have gone back and forth).
     */
    private function reachedAt(Order $order, OrderStatus $status): ?Carbon
    {
        return $order->statusHistory
            ->filter(fn (OrderStatusHistory $entry) => $entry->to_status === $status)
            ->last()
            ?->created_at;
    }
}
