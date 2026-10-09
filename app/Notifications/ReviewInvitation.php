<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A few days after the delivery, the customer is asked what they think of the products (reviews:invite): reviews
 * build the trust of the next customers. E-mail only, once per order, to customers with an account (reviews are
 * given from the account's order page).
 */
class ReviewInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly Order $order) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');
        $firstName = str($order->customer_name)->before(' ')->toString() ?: $order->customer_name;

        $mail = (new MailMessage)
            ->subject('Votre avis sur votre commande '.$order->number)
            ->greeting("Bonjour {$firstName},")
            ->line('Vous avez reçu votre commande il y a quelques jours. Êtes-vous satisfait de vos articles ?')
            ->line('Votre avis aide les autres clients à bien choisir. Il ne prend qu’une minute :');

        foreach ($order->items->whereNotNull('product_id')->take(5) as $item) {
            $mail->line("• {$item->product_name}");
        }

        return $mail
            ->action('Donner mon avis', route('account.orders.show', $order))
            ->line('Une note de 1 à 5 étoiles suffit ; un commentaire est le bienvenu.')
            ->salutation('Merci, l’équipe '.config('storefront.name'));
    }
}
