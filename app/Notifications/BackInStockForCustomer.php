<?php

namespace App\Notifications;

use App\Models\Product;
use App\Notifications\Channels\SmsChannel;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Back in stock" message (EX-17), by SMS and/or e-mail depending on what the visitor left.
 */
class BackInStockForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly Product $product, public readonly ?string $variantLabel = null) {}

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
        return "KOVA MARKET : « {$this->name()} » est de nouveau disponible. {$this->product->url()}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("« {$this->name()} » est de nouveau disponible")
            ->greeting('Bonne nouvelle !')
            ->line("« {$this->name()} » est de nouveau en stock, à partir de ".Money::format($this->product->price).'.')
            ->line('Les quantités peuvent être limitées : ne tardez pas.')
            ->action('Voir le produit', $this->product->url())
            ->line('Vous recevez ce message une seule fois, parce que vous avez demandé à être prévenu.');
    }

    private function name(): string
    {
        return $this->variantLabel ? "{$this->product->name} ({$this->variantLabel})" : $this->product->name;
    }
}
