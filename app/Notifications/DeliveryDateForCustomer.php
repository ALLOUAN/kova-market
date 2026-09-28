<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The planned delivery date was set or changed by the back-office (F-127).
 */
class DeliveryDateForCustomer extends Notification implements ShouldQueue
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
        return collect(['sms', 'mail'])
            ->filter(fn (string $channel) => filled($notifiable->routeNotificationFor($channel, $this)))
            ->map(fn (string $channel) => $channel === 'sms' ? SmsChannel::class : $channel)
            ->values()
            ->all();
    }

    public function toSms(object $notifiable): string
    {
        return "KOVA MARKET : votre commande {$this->order->number} sera livrée le {$this->date()}. Suivi : ".route('tracking.show');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commande {$this->order->number} : livraison prévue le {$this->date()}")
            ->greeting('Bonjour '.$this->order->customer_name.',')
            ->line("Votre commande {$this->order->number} sera livrée le {$this->date()} à {$this->order->commune_name}.")
            ->line('Gardez votre téléphone à portée de main : le livreur vous appellera en arrivant.')
            ->action('Suivre ma commande', route('tracking.show'));
    }

    private function date(): string
    {
        return $this->order->delivery_date->translatedFormat('l j F');
    }
}
