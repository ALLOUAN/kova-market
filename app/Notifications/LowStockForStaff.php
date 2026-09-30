<?php

namespace App\Notifications;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A variant reached its stock alert threshold (F-135, "Stock sous le seuil" → admin e-mail).
 */
class LowStockForStaff extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly ProductVariant $variant)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $variant = $this->variant->loadMissing('product', 'attributeValues.attribute');
        $state = $variant->stock === 0 ? 'est épuisé' : 'n’a plus que '.$variant->product->saleQuantity()->format($variant->stock).' en stock';

        return (new MailMessage)
            ->subject("Stock bas : {$variant->product->name}")
            ->line("« {$variant->product->name} » ({$variant->label()}, réf. {$variant->sku}) {$state}.")
            ->line("Seuil d’alerte : {$variant->lowStockThresholdLabel()}.")
            ->action('Voir le produit', ProductResource::getUrl('edit', ['record' => $variant->product]));
    }
}
