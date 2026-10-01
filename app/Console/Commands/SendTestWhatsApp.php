<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppException;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Services\WhatsApp\WhatsAppMessage;
use App\Support\PhoneNumber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Checks the WhatsApp connection (F-134) without placing an order: sends the "order placed" template with its
 * example values, straight through the gateway (no queue). With the Twilio sandbox, the number must have sent
 * "join <code>" first.
 */
#[Signature('whatsapp:test {phone : Numéro du destinataire, ex. 0701020304 ou +33612345678}')]
#[Description('Send a test WhatsApp message through the configured driver')]
class SendTestWhatsApp extends Command
{
    public function handle(WhatsAppGateway $gateway): int
    {
        $input = (string) $this->argument('phone');
        $phone = str_starts_with($input, '+') ? '+'.preg_replace('/\D/', '', $input) : PhoneNumber::normalize($input);

        if (blank($phone)) {
            $this->error('Numéro invalide.');

            return self::FAILURE;
        }

        if (config('services.whatsapp.driver') === 'twilio' && (blank(config('services.whatsapp.twilio.sid')) || blank(config('services.whatsapp.twilio.token')))) {
            $this->error('Renseignez TWILIO_ACCOUNT_SID et TWILIO_AUTH_TOKEN dans le fichier .env.');

            return self::FAILURE;
        }

        try {
            $gateway->send($phone, WhatsAppMessage::template('order_placed', config('whatsapp.templates.order_placed.example')));
        } catch (WhatsAppException $exception) {
            $this->error('Refusé : '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Message envoyé à '.$phone.' (pilote : '.config('services.whatsapp.driver').').');

        return self::SUCCESS;
    }
}
