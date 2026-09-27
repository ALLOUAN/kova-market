<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Back-office login: an account is locked for 15 minutes after 5 failed attempts (F-100),
 * on top of Filament's own per-visitor throttling.
 */
class Login extends BaseLogin
{
    public const MAX_FAILURES = 5;

    public const LOCKOUT_SECONDS = 15 * 60;

    public function authenticate(): ?LoginResponse
    {
        $key = $this->lockoutKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILURES)) {
            Notification::make()
                ->title('Compte temporairement verrouillé')
                ->body('Trop de tentatives échouées. Réessayez dans '.ceil(RateLimiter::availableIn($key) / 60).' minute(s).')
                ->danger()
                ->send();

            return null;
        }

        $response = parent::authenticate();

        if ($response !== null) {
            RateLimiter::clear($key);
        }

        return $response;
    }

    protected function throwFailureValidationException(): never
    {
        RateLimiter::hit($this->lockoutKey(), self::LOCKOUT_SECONDS);

        parent::throwFailureValidationException();
    }

    private function lockoutKey(): string
    {
        return 'admin-login:'.Str::lower(trim((string) ($this->data['email'] ?? '')));
    }
}
