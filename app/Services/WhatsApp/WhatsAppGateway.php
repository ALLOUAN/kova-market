<?php

namespace App\Services\WhatsApp;

/**
 * WhatsApp sender (F-134), chosen in config/services.php ("whatsapp.driver"): Meta's Cloud API in production, a log
 * file locally, nothing in tests.
 */
interface WhatsAppGateway
{
    /**
     * @param  string  $phone  international form, e.g. "+2250701020304"
     *
     * @throws WhatsAppException when Meta refuses the message (the queued notification is tried again)
     */
    public function send(string $phone, WhatsAppMessage $message): void;
}
