<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsGateway;
use Illuminate\Notifications\Notification;

/**
 * "sms" notification channel: the notification's toSms() text goes to the notifiable's phone number
 * (routeNotificationForSms(), or the "sms" route of an on-demand notification).
 */
class SmsChannel
{
    public function __construct(private SmsGateway $gateway) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationFor('sms', $notification);

        if (blank($phone) || ! method_exists($notification, 'toSms')) {
            return;
        }

        $this->gateway->send($phone, $notification->toSms($notifiable));
    }
}
