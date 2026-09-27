<?php

namespace App\Services\Sms;

/**
 * SMS provider (F-131). The provider is chosen in config/services.php ("sms.driver"), so switching to another
 * local or international gateway never touches the notifications.
 */
interface SmsGateway
{
    /**
     * @param  string  $phone  international form, e.g. "+2250701020304"
     */
    public function send(string $phone, string $message): void;
}
