<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The team answered an assistance request: an e-mail to the customer (when the account has one) with the answer and a
 * link to the conversation, where they can reply. The answer also waits in the customer area, marked unread.
 */
class SupportReplyForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly SupportTicket $ticket, public readonly SupportTicketMessage $reply)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return filled($notifiable->routeNotificationFor('mail', $this)) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Réponse à votre demande {$this->ticket->reference()} — {$this->ticket->title}")
            ->greeting('Bonjour '.(str($notifiable->name)->before(' ')->toString() ?: $notifiable->name).',')
            ->line('Notre équipe a répondu à votre demande « '.$this->ticket->title.' » :')
            ->line($this->reply->body)
            ->action('Voir la conversation et répondre', route('account.support.show', $this->ticket))
            ->line('Pour nous répondre, utilisez le bouton ci-dessus plutôt que ce mail.');
    }
}
