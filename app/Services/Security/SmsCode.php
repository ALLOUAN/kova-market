<?php

namespace App\Services\Security;

use App\Services\Sms\SmsGateway;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * One-time codes sent by SMS to prove a phone number belongs to the person (F-076 forgotten password, F-070 guest
 * orders): 6 digits, valid 15 minutes, 5 tries, used once, one SMS a minute at most per number and purpose. Only a
 * hash of the code is kept.
 */
class SmsCode
{
    public const MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_SECONDS = 60;

    public function __construct(private SmsGateway $sms) {}

    /**
     * Sends a new code; false when one was sent to this number less than a minute ago.
     *
     * @param  Closure(string): string  $message  the SMS text for a given code
     */
    public function send(string $purpose, string $phone, Closure $message): bool
    {
        if (! Cache::add($this->key($purpose, $phone, 'sent'), true, self::RESEND_SECONDS)) {
            return false;
        }

        $code = (string) random_int(100000, 999999);
        Cache::put($this->key($purpose, $phone), ['hash' => Hash::make($code), 'attempts' => 0], now()->addMinutes(self::MINUTES));
        $this->sms->send($phone, $message($code));

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
