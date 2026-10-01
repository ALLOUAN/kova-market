<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

/**
 * Development gateway: writes each WhatsApp message to storage/logs/whatsapp.log instead of sending it.
 */
class LogWhatsAppGateway implements WhatsAppGateway
{
    public function send(string $phone, WhatsAppMessage $message): void
    {
        Log::channel('whatsapp')->info("[{$message->name()} → {$phone}] {$message->text()}");
    }
}
