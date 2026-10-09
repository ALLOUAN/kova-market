<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Services\Storefront\MetaConversions;

/**
 * A delivered order is reported to Meta as "CommandeLivree" (Conversions API, consent only), whether the back-office
 * or the courier app marks it delivered. The event itself is queued.
 */
class ReportDeliveryToMeta
{
    public function __construct(private MetaConversions $conversions) {}

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->to === OrderStatus::Delivered) {
            $this->conversions->delivered($event->order);
        }
    }
}
