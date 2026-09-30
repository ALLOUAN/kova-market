<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\Channels\SmsChannel;
use App\Services\Orders\OrderReceipt;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The products sold by weight were weighed at preparation and the total changed (App\Services\Orders\WeighIn):
 * the customer knows the amount to pay on delivery before the courier arrives.
 */
class WeighInForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly Order $order, public readonly int $previousTotal)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return collect(['sms', 'mail'])
            ->filter(fn (string $channel) => filled($notifiable->routeNotificationFor($channel, $this)))
            ->map(fn (string $channel) => $channel === 'sms' ? SmsChannel::class : $channel)
            ->values()
            ->all();
    }

    public function toSms(object $notifiable): string
    {
        return "KOVA MARKET : vos produits au poids de la commande {$this->order->number} ont été pesés. "
            .'Nouveau total : '.Money::format($this->order->total).' (au lieu de '.Money::format($this->previousTotal).'), à régler à la livraison. '
            .'Reçu : '.app(OrderReceipt::class)->url($this->order);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Commande {$this->order->number} : vos produits ont été pesés")
            ->greeting('Bonjour '.$this->order->customer_name.',')
            ->line('Nous avons pesé vos produits vendus au poids. Vous payez le poids réel :');

        foreach ($this->order->items->filter(fn (OrderItem $item) => $item->weighNote() !== null) as $item) {
            $mail->line("{$item->product_name} — {$item->weighNote()} : ".Money::format($item->line_total));
        }

        return $mail
            ->line('Nouveau total : **'.Money::format($this->order->total).'** (au lieu de '.Money::format($this->previousTotal).'), à régler à la livraison.')
            ->action('Voir mon reçu', app(OrderReceipt::class)->url($this->order));
    }
}
