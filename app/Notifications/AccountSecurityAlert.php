<?php

namespace App\Notifications;

use App\Notifications\Channels\Messaging;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Your account was changed" alert (F-147), sent to the e-mail address and phone number the account had BEFORE the
 * change: if someone else took the account and replaced them, the owner still hears of it and can react.
 */
class AccountSecurityAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    private const LABELS = [
        'password' => 'nouveau mot de passe',
        'email' => 'nouvelle adresse e-mail',
        'phone' => 'nouveau numéro de téléphone',
    ];

    public readonly string $changedAt;

    /**
     * @param  list<'password'|'email'|'phone'>  $changes
     */
    public function __construct(public readonly string $name, public readonly array $changes)
    {
        $this->changedAt = now()->translatedFormat('j F Y à H:i');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return Messaging::via($notifiable, $this, ['phone', 'mail']);
    }

    public function summary(): string
    {
        return implode(', ', array_map(fn (string $change) => self::LABELS[$change], $this->changes));
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::template('security_alert', [$this->name, $this->changedAt, $this->summary(), route('contact.show')]);
    }

    public function toSms(object $notifiable): string
    {
        return "KOVA MARKET : votre compte a été modifié le {$this->changedAt} ({$this->summary()}). "
            .'Si ce n’est pas vous, contactez-nous : '.route('contact.show');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre compte KOVA MARKET a été modifié')
            ->greeting("Bonjour {$this->name},")
            ->line("Le {$this->changedAt}, votre compte a été modifié : {$this->summary()}.")
            ->line('Si c’est vous, il n’y a rien à faire.')
            ->line('Si vous n’êtes pas à l’origine de ce changement, contactez-nous tout de suite : nous bloquerons le compte le temps de vous le rendre.')
            ->action('Nous contacter', route('contact.show'))
            ->line('Pour votre sécurité, ce message est envoyé à vos anciennes coordonnées.');
    }
}
