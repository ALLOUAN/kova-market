<?php

namespace App\Services\Security;

use App\Notifications\Channels\Messaging;
use App\Services\Sms\SmsGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Services\WhatsApp\WhatsAppMessage;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * One-time codes sent to the phone to prove a number belongs to the person (F-076 forgotten password, F-070 guest
 * orders): on WhatsApp (Meta's authentication template), or by SMS while SMS is switched on. 6 digits, valid 15
 * minutes, 5 tries, used once, one message a minute at most per number and purpose. Only a hash of the code is kept.
 */
class SmsCode
{
    public const MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_SECONDS = 60;

    public function __construct(private SmsGateway $sms, private WhatsAppGateway $whatsapp) {}

    /** Whether a code can reach a phone at all (WhatsApp or SMS switched on). */
    public static function available(): bool
    {
        return Messaging::phoneEnabled();
    }

    /** "WhatsApp", "SMS" or "WhatsApp ou SMS": how the code arrives, for the pages that ask for it. */
    public static function channelLabel(): string
    {
        return match (true) {
            Messaging::whatsappEnabled() && Messaging::smsEnabled() => 'WhatsApp ou SMS',
            Messaging::smsEnabled() => 'SMS',
            default => 'WhatsApp',
        };
    }

    /**
     * Sends a new code; false when one was sent to this number less than a minute ago, or no channel is on.
     *
     * @param  Closure(string): string  $message  the SMS text for a given code
     */
    public function send(string $purpose, string $phone, Closure $message): bool
    {
        if (! self::available() || ! Cache::add($this->key($purpose, $phone, 'sent'), true, self::RESEND_SECONDS)) {
            return false;
        }

        $code = (string) random_int(100000, 999999);
        Cache::put($this->key($purpose, $phone), ['hash' => Hash::make($code), 'attempts' => 0], now()->addMinutes(self::MINUTES));

        if (Messaging::whatsappEnabled()) {
            $this->whatsapp->send($phone, WhatsAppMessage::template('verification_code', [$code]));
        }

        if (Messaging::smsEnabled()) {
            $this->sms->send($phone, $message($code));
        }

        return true;
    }

    /**
     * Whether the code matches; a matching code is used up, a wrong one counts as a try.
     */
    public function verify(string $purpose, string $phone, string $code): bool
    {
        $key = $this->key($purpose, $phone);
        $entry = Cache::get($key);

        if (! is_array($entry)) {
            return false;
        }

        if (! Hash::check(trim($code), $entry['hash'])) {
            $entry['attempts']++;
            $entry['attempts'] >= self::MAX_ATTEMPTS ? Cache::forget($key) : Cache::put($key, $entry, now()->addMinutes(self::MINUTES));

            return false;
        }

        Cache::forget($key);

        return true;
    }

    private function key(string $purpose, string $phone, string $kind = 'code'): string
    {
        return "sms-code:{$purpose}:{$kind}:{$phone}";
    }
}
