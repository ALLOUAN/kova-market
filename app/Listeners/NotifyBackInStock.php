<?php

namespace App\Listeners;

use App\Events\BackInStock;
use App\Models\StockAlert;
use App\Notifications\BackInStockForCustomer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Tells the visitors waiting for a product that it is back (EX-17), once per alert. An inactive product
 * keeps its alerts until it is on sale again and restocked.
 */
class NotifyBackInStock implements ShouldQueue
{
    public function handle(BackInStock $event): void
    {
        $variant = $event->variant->loadMissing('product', 'attributeValues.attribute');

        if (! $variant->product?->is_active || $variant->stock === 0) {
            return;
        }

        $label = $variant->attributeValues->isEmpty() ? null : $variant->label();

        StockAlert::waitingFor($variant)->each(function (StockAlert $alert) use ($variant, $label): void {
            Notification::send($alert->recipient(), new BackInStockForCustomer($variant->product, $alert->product_variant_id ? $label : null));
            $alert->forceFill(['notified_at' => now()])->save();
        });
    }
}
