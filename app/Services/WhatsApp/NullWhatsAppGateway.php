<?php

namespace App\Services\WhatsApp;

/**
 * Sends nothing (tests, or WhatsApp switched off by configuration).
 */
class NullWhatsAppGateway implements WhatsAppGateway
{
    public function send(string $phone, WhatsAppMessage $message): void {}
}
