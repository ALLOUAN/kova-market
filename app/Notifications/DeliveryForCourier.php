<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * SMS to a courier (F-123): an order given to them, or a new order waiting in their zone.
 */
class DeliveryForCourier extends Notification implements ShouldQueue
{
    use Queueable;

    public const ASSIGNED = 'assigned';

    public const WAITING = 'waiting';

    public int $tries = 3;

    public function __construct(public readonly Order $order, public readonly string $event)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        $place = "{$this->order->commune_name}, {$this->order->district}";

        return $this->event === self::ASSIGNED
            ? "KOVA MARKET : livraison {$this->order->number} pour vous ({$place}). ".route('courier.orders.show', $this->order)
            : "KOVA MARKET : nouvelle commande à livrer ({$place}). Prenez-la : ".route('courier.home');
    }
}
