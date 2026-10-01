<?php

namespace App\Notifications\Channels;

use App\Models\Setting;
use Illuminate\Notifications\Notification;

/**
 * Which channels reach a person, as chosen in Paramètres › Commandes › Notifications: WhatsApp and e-mail by default,
 * SMS off until a provider is set. Notifications ask for "phone" (WhatsApp and/or SMS, to the phone number) and
 * "mail"; a channel without an address for the person is left out.
 */
final class Messaging
{
    public static function whatsappEnabled(): bool
    {
        return Setting::get('notifications.whatsapp', '1') !== '0';
    }

    public static function smsEnabled(): bool
    {
        return Setting::get('notifications.sms', '0') === '1';
    }

    /** Whether a phone message (code, alert) can reach anyone at all. */
    public static function phoneEnabled(): bool
    {
        return self::whatsappEnabled() || self::smsEnabled();
    }

    /**
     * @param  list<string>  $wanted  "phone" and/or "mail" ("sms" is read as "phone")
     * @return list<string>
     */
    public static function via(object $notifiable, Notification $notification, array $wanted): array
    {
        $hasPhone = filled($notifiable->routeNotificationFor('whatsapp', $notification)) || filled($notifiable->routeNotificationFor('sms', $notification));
        $channels = [];

        foreach ($wanted as $channel) {
            if (in_array($channel, ['phone', 'sms'], true) && $hasPhone) {
                $channels = [
                    ...$channels,
                    ...(self::whatsappEnabled() ? [WhatsAppChannel::class] : []),
                    ...(self::smsEnabled() ? [SmsChannel::class] : []),
                ];
            } elseif ($channel === 'mail' && filled($notifiable->routeNotificationFor('mail', $notification))) {
                $channels[] = 'mail';
            }
        }

        return array_values(array_unique($channels));
    }
}
