<?php

namespace App\Notifications;

use App\Notifications\Channels\Messaging;
use App\Services\WhatsApp\WhatsAppMessage;
use App\Support\PhoneNumber;
use Illuminate\Notifications\Notification;

/**
 * Sign-in details of a courier account, sent on WhatsApp (or SMS) at creation and at each reset (F-122). The password is
 * temporary: it must be changed at the first sign-in. Sent at once, not queued, so the password is never
 * stored in the jobs table.
 */
class CourierCredentials extends Notification
{
    public function __construct(
        public readonly string $phone,
        #[\SensitiveParameter] public readonly string $temporaryPassword,
        public readonly bool $reset = false,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return Messaging::via($notifiable, $this, ['phone']);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::template('courier_credentials', [PhoneNumber::format($this->phone), $this->temporaryPassword, route('courier.login')]);
    }

    public function toSms(object $notifiable): string
    {
        return 'KOVA MARKET : '.($this->reset ? 'nouveau mot de passe livreur.' : 'votre compte livreur est prêt.')
            .' Identifiant : '.PhoneNumber::format($this->phone)
            .". Mot de passe provisoire : {$this->temporaryPassword}. "
            .route('courier.login');
    }
}
