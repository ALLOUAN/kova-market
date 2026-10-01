<?php

namespace App\Services\WhatsApp;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * WhatsApp through Twilio's Messages API. An approved template is sent by its Content SID (HX…, set per template in
 * Paramètres › Commandes › Notifications) with its variables; a template without a Content SID goes as plain text,
 * which WhatsApp accepts within 24 hours of the person's last message and in Twilio's sandbox. Twilio reports
 * deliveries to /webhooks/twilio/whatsapp.
 */
class TwilioWhatsAppGateway implements WhatsAppGateway
{
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private string $from,
    ) {}

    /** The Content SID of a template, set in the back-office. */
    public static function contentSid(string $key): ?string
    {
        return Setting::get("whatsapp.twilio_content.{$key}") ?: null;
    }

    public function send(string $phone, WhatsAppMessage $message): void
    {
        $contentSid = self::contentSid($message->key);

        $payload = array_filter([
            'From' => 'whatsapp:'.$this->from,
            'To' => 'whatsapp:'.$phone,
            'ContentSid' => $contentSid,
            // Twilio numbers the variables from "1", like the template's {{1}}, {{2}}…
            'ContentVariables' => $contentSid ? json_encode(array_combine(
                array_map('strval', range(1, count($message->parameters))),
                $message->parameters,
            ), JSON_UNESCAPED_UNICODE) : null,
            'Body' => $contentSid ? null : $message->text(),
            'StatusCallback' => Route::has('webhooks.twilio.whatsapp') ? route('webhooks.twilio.whatsapp') : null,
        ]);

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", $payload);
        } catch (ConnectionException $exception) {
            throw new WhatsAppException('Twilio injoignable : '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            // Twilio's error code (e.g. 63016: outside the 24-hour window, a template is needed) and message.
            $error = $response->json('message', 'HTTP '.$response->status()).' (code '.$response->json('code', '?').')';
            Log::channel('whatsapp')->warning("Refusé par Twilio [{$message->name()} → {$phone}] {$error}");

            throw new WhatsAppException("WhatsApp (Twilio) : {$error}");
        }

        Log::channel('whatsapp')->info("Envoyé par Twilio [{$message->name()} → {$phone}] ".$response->json('sid', '').($contentSid ? '' : ' (texte libre)'));
    }

    /**
     * Whether a call really comes from Twilio: X-Twilio-Signature is the base64 HMAC-SHA1, keyed with the auth
     * token, of the full URL followed by each POST field name and value in alphabetical order.
     *
     * @param  array<string, string>  $fields
     */
    public static function validSignature(string $authToken, string $url, array $fields, string $signature): bool
    {
        ksort($fields);
        $data = $url.collect($fields)->map(fn ($value, $key) => $key.$value)->join('');

        return $authToken !== '' && hash_equals(base64_encode(hash_hmac('sha1', $data, $authToken, true)), $signature);
    }
}
