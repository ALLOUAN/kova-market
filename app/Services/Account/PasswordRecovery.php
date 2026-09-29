<?php

namespace App\Services\Account;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use App\Services\Security\SmsCode;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * "Mot de passe oublié" (F-076) for customer accounts: an e-mail address receives a single-use link (Laravel's
 * password broker), a phone number receives a 6-digit code by SMS, valid 15 minutes, 5 tries, used once. Nothing
 * tells whether an account exists. Staff and couriers get a new password from the store instead.
 */
class PasswordRecovery
{
    public const CODE_MINUTES = SmsCode::MINUTES;

    public const MAX_ATTEMPTS = SmsCode::MAX_ATTEMPTS;

    /** Minimum delay between two codes sent to the same number. */
    public const RESEND_SECONDS = SmsCode::RESEND_SECONDS;

    private const PURPOSE = 'password';

    public function __construct(
        private SmsCode $codes,
        private ResetUserPassword $resetter,
    ) {}

    /**
     * Sends the link or the code when the identifier belongs to an active customer account; silent otherwise.
     */
    public function request(string $login): void
    {
        $user = $this->customer($login);

        if (! $user) {
            return;
        }

        if (str_contains($login, '@')) {
            Password::broker()->sendResetLink(['email' => $user->email]);

            return;
        }

        $this->codes->send(self::PURPOSE, $user->phone, fn (string $code) => config('storefront.name')
            ." : votre code pour changer de mot de passe est {$code}. Il est valable ".self::CODE_MINUTES.' minutes. Ne le communiquez à personne.');
    }

    /**
     * Sets the new password when the SMS code matches; false for a wrong, expired or exhausted code.
     *
     * @param  array{password: string, password_confirmation: string}  $passwords
     */
    public function resetWithCode(string $phone, string $code, array $passwords): bool
    {
        $phone = PhoneNumber::normalize($phone) ?? '';
        $user = $this->customer($phone);

        if (! $user || ! $this->codes->verify(self::PURPOSE, $phone, $code)) {
            return false;
        }

        $this->resetter->reset($user, $passwords);
        $this->signOutEverywhere($user);

        return true;
    }

    /**
     * Sets the new password from an e-mail link; returns the broker status (Password::PASSWORD_RESET on success).
     *
     * @param  array{token: string, email: string, password: string, password_confirmation: string}  $input
     */
    public function resetWithToken(array $input): string
    {
        if (! $this->customer($input['email'])) {
            return Password::INVALID_USER;
        }

        return Password::broker()->reset($input, function (User $user) use ($input): void {
            $this->resetter->reset($user, $input);
            $this->signOutEverywhere($user);
        });
    }

    /**
     * A customer account (no back-office or courier role) that is not suspended.
     */
    private function customer(string $login): ?User
    {
        $user = User::findByLogin($login);

        return $user && ! $user->isSuspended() && $user->roles()->doesntExist() ? $user : null;
    }

    /**
     * Whoever knew the old password loses access: remembered sessions and API tokens.
     */
    private function signOutEverywhere(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        $user->tokens()->delete();
    }
}
