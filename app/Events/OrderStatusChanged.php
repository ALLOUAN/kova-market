<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An order changed status. Customer and courier notifications (F-130) listen to it.
 */
class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $from,
        public readonly OrderStatus $to,
    ) {}
}
