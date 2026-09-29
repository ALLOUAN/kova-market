<?php

namespace App\Notifications;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms a newsletter sign-up, with the one-click unsubscribe link every mailing carries.
 */
class NewsletterWelcome extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly NewsletterSubscriber $subscriber) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $store = config('storefront.name');

        return (new MailMessage)
            ->subject("Bienvenue dans la newsletter {$store}")
            ->greeting('Merci pour votre inscription !')
            ->line('Vous recevrez nos offres, nos nouveautés et nos codes promo en avant-première, sans excès : quelques e-mails par mois au plus.')
            ->action('Découvrir la boutique', route('shop.index'))
            ->line('Vous ne souhaitez plus recevoir nos e-mails ? [Se désinscrire en un clic]('.route('newsletter.unsubscribe', $this->subscriber).').');
    }
}
