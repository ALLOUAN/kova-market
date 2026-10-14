<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

/**
 * The 6-digit back-office sign-in code, for accounts using two-factor by e-mail. Sent at once, not queued: on shared
 * hosting the queue only runs every minute and the code would arrive late.
 */
class AdminLoginCode extends Notification
{
    public function __construct(
        #[SensitiveParameter]
        public string $code,
        public int $codeExpiryMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de connexion : '.$this->code)
            ->greeting('Bonjour,')
            ->line('Voici votre code pour vous connecter à l’administration de '.config('storefront.name').' :')
            ->line('**'.implode(' ', str_split($this->code, 3)).'**')
            ->line("Il est valable {$this->codeExpiryMinutes} minutes.")
            ->line('Si vous n’êtes pas à l’origine de cette connexion, changez votre mot de passe sans attendre : quelqu’un le connaît.')
            ->salutation('L’équipe '.config('storefront.name'));
    }
}
