<?php

namespace App\Notifications;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * New order e-mail for the staff who handle orders (F-130, "Nouvelle commande" → admin).
 */
class NewOrderForStaff extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly Order $order)
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
        $order = $this->order;

        return (new MailMessage)
            ->subject("Nouvelle commande {$order->number} — ".Money::format($order->total))
            ->line("{$order->customer_name} ({$order->formattedPhone()}) a commandé pour ".Money::format($order->total).'.')
            ->line("Livraison : {$order->district}, {$order->commune_name} ({$order->zone_name}).")
            ->line('Paiement : '.$order->payment_method->getLabel().'.')
            ->action('Ouvrir la commande', OrderResource::getUrl('view', ['record' => $order]));
    }
}
