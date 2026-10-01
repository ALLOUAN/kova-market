<?php

namespace App\Notifications\Channels;

use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Notifications\Notification;

/**
 * "whatsapp" notification channel: the notification's toWhatsApp() template message goes to the notifiable's phone
 * number — its "whatsapp" route, else the phone route used by SMS.
 */
class WhatsAppChannel
{
    public function __construct(private WhatsAppGateway $gateway) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationFor('whatsapp', $notification) ?: $notifiable->routeNotificationFor('sms', $notification);

        if (blank($phone) || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $this->gateway->send($phone, $notification->toWhatsApp($notifiable));
    }
}
