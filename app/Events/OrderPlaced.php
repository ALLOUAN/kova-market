<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A customer placed an order. Notifications (SMS, e-mail, back-office alert: F-130) listen to it.
 */
class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
