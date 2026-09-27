<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Development gateway: writes each SMS to storage/logs/sms.log instead of sending it.
 */
class LogSmsGateway implements SmsGateway
{
    public function __construct(private string $sender) {}

    public function send(string $phone, string $message): void
    {
        Log::channel('sms')->info("[{$this->sender} → {$phone}] {$message}");
    }
}
