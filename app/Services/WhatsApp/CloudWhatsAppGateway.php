<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta's WhatsApp Cloud API: POST /{phone-number-id}/messages with a template, its language and its parameters.
 * The token is the system user's permanent token of KOVA MARKET's Meta business account.
 */
class CloudWhatsAppGateway implements WhatsAppGateway
{
    public function __construct(
        private string $token,
        private string $phoneNumberId,
        private string $version,
        private string $language,
    ) {}

    public function send(string $phone, WhatsAppMessage $message): void
    {
        $parameters = array_map(fn (string $value) => ['type' => 'text', 'text' => $value], $message->parameters);
        $components = [['type' => 'body', 'parameters' => $parameters]];

        // Authentication templates repeat the code in their "Copier le code" button.
        if ($message->hasCopyCodeButton()) {
            $components[] = ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $message->parameters[0]]]];
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->post("https://graph.facebook.com/{$this->version}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => ltrim($phone, '+'),
                    'type' => 'template',
                    'template' => ['name' => $message->name(), 'language' => ['code' => $this->language], 'components' => $components],
                ]);
        } catch (ConnectionException $exception) {
            throw new WhatsAppException('Meta injoignable : '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            // Meta's error code and message, never the token nor the whole text sent.
            $error = $response->json('error.message', 'HTTP '.$response->status()).' (code '.$response->json('error.code', '?').')';
            Log::channel('whatsapp')->warning("Refusé par Meta [{$message->name()} → {$phone}] {$error}");

            throw new WhatsAppException("WhatsApp : {$error}");
        }

        Log::channel('whatsapp')->info("Envoyé [{$message->name()} → {$phone}] ".$response->json('messages.0.id', ''));
    }
}
