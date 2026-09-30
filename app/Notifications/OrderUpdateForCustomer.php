<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Channels\SmsChannel;
use App\Services\Orders\OrderReceipt;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * What the customer receives about an order (F-130): SMS is the main channel since the e-mail is optional.
 * Sent through the queue, three attempts, never blocking the order itself.
 */
class OrderUpdateForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public const PLACED = 'placed';

    /** Events that give the customer the receipt: the order (paid online, or to pay on delivery), and the delivery (paid). */
    private const WITH_RECEIPT = [self::PLACED, 'livree'];

    /**
     * Channels per event, from the specification's notification table.
     */
    private const CHANNELS = [
        self::PLACED => ['sms', 'mail'],
        'confirmee' => ['sms', 'mail'],
        'en_preparation' => ['mail'],
        'expediee' => ['sms'],
        'en_livraison' => ['sms'],
        'livree' => ['sms', 'mail'],
        'annulee' => ['sms', 'mail'],
    ];

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly Order $order, public readonly string $event)
    {
        $this->afterCommit();
    }

    /**
     * Whether this event notifies the customer at all.
     */
    public static function concerns(string $event): bool
    {
        return array_key_exists($event, self::CHANNELS);
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return collect(self::CHANNELS[$this->event] ?? [])
            ->filter(fn (string $channel) => filled($notifiable->routeNotificationFor($channel, $this)))
            ->map(fn (string $channel) => $channel === 'sms' ? SmsChannel::class : $channel)
            ->values()
            ->all();
    }

    public static function recipientOf(Order $order): AnonymousNotifiable
    {
        $recipient = (new AnonymousNotifiable)->route('sms', $order->phone);

        return filled($order->email) ? $recipient->route('mail', $order->email) : $recipient;
    }

    /**
     * Short texts: accented SMS count 70 characters per part.
     */
    public function toSms(object $notifiable): string
    {
        $number = $this->order->number;

        return match ($this->event) {
            self::PLACED => "KOVA MARKET : commande {$number} reçue (".Money::format($this->order->total).'). Suivi : '.route('tracking.show'),
            'confirmee' => "KOVA MARKET : votre commande {$number} est confirmée.",
            'expediee', 'en_livraison' => "KOVA MARKET : votre commande {$number} est en route vers {$this->order->commune_name}.",
            'livree' => "KOVA MARKET : commande {$number} livrée. Merci ! Votre reçu : ".app(OrderReceipt::class)->url($this->order),
            'annulee' => "KOVA MARKET : votre commande {$number} a été annulée. Questions : ".config('storefront.contact.phone'),
            default => "KOVA MARKET : votre commande {$number} a été mise à jour.",
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');
        $status = OrderStatus::tryFrom($this->event);

        $mail = (new MailMessage)
            ->subject($this->event === self::PLACED ? "Commande {$order->number} reçue" : "Commande {$order->number} : {$status?->getLabel()}")
            ->greeting("Bonjour {$order->customer_name},")
            ->line(match ($this->event) {
                self::PLACED => 'Merci pour votre commande ! Voici son récapitulatif.',
                'confirmee' => 'Votre commande est confirmée. Nous la préparons.',
                'en_preparation' => 'Votre commande est en cours de préparation.',
                'livree' => 'Votre commande a été livrée. Merci pour votre confiance !',
                'annulee' => 'Votre commande a été annulée. Contactez-nous pour toute question.',
                default => 'Le statut de votre commande a changé : '.$status?->getLabel().'.',
            });

        foreach ($order->items as $item) {
            $mail->line("{$item->quantityLabel()} × {$item->product_name} — ".Money::format($item->line_total));
        }

        $mail
            ->line('Total : '.Money::format($order->total).' ('.$order->payment_method->getLabel().' — '.$order->payment_status->getLabel().')')
            ->action('Suivre ma commande', route('tracking.show'))
            ->line("Numéro de commande : {$order->number}");

        // The receipt, attached and as a link: "de commande" while payment is due, "de paiement" once paid.
        if (in_array($this->event, self::WITH_RECEIPT, true)) {
            $receipt = app(OrderReceipt::class);
            $mail->line('Votre reçu est joint à cet e-mail. Vous pouvez aussi [le télécharger ici]('.$receipt->url($order).').')
                ->attachData($receipt->pdf($order), $receipt->filename($order), ['mime' => 'application/pdf']);
        }

        return $mail->salutation('L’équipe '.config('storefront.name'));
    }
}
