<?php

namespace App\Services\Sms;

/**
 * Discards every SMS (automated tests).
 */
class NullSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void {}
}
