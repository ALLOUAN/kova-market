<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Services\Delivery\DeliveryDispatcher;

/**
 * A confirmed order enters the queue of its zone (F-123). Run at once, not queued: the back-office sees the
 * courier as soon as it confirms.
 */
class DispatchConfirmedOrder
{
    public function __construct(private DeliveryDispatcher $dispatcher) {}

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->to === OrderStatus::Confirmed) {
            $this->dispatcher->enqueue($event->order->loadMissing('commune'));
        }
    }
}
