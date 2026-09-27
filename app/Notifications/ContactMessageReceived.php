<?php

namespace App\Notifications;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Copy of a contact message sent to the store's contact address (F-080); "Répondre" goes to the customer.
 */
class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly ContactMessage $contactMessage)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->contactMessage;

        $mail = (new MailMessage)
            ->subject("Contact : {$message->subject->getLabel()} — {$message->name}")
            ->greeting('Nouveau message du site')
            ->line("**{$message->name}**, {$message->formattedPhone()}".($message->email ? ", {$message->email}" : ''))
            ->line("Sujet : {$message->subject->getLabel()}")
            ->line($message->message)
            ->action('Voir dans le back-office', ContactMessageResource::getUrl('index'));

        return $message->email ? $mail->replyTo($message->email, $message->name) : $mail;
    }
}
