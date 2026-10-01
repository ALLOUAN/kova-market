<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // "login" holds either an e-mail address or a phone number, typed in any usual Ivorian format.
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::findByLogin($request->input(Fortify::username()));

            // Customers only, refused like a wrong password otherwise: a team member signing in here would reach the
            // back-office without its two-factor code (same "web" session). Suspended accounts are refused too.
            return User::passwordMatches($user, (string) $request->input('password')) && $user->isCustomer() && ! $user->isSuspended() ? $user : null;
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            // Same key for "07 01 02 03 04" and "+2250701020304": retyping a number differently does not reset the count.
            $login = (string) $request->input(Fortify::username());
            $throttleKey = Str::transliterate((PhoneNumber::normalize($login) ?? Str::lower($login)).'|'.$request->ip());

            return [
                Limit::perMinute(5)->by($throttleKey),
                // Whatever the address: tries spread over many IP addresses stop at 20 an hour for one account (F-147).
                Limit::perHour(20)->by('account|'.Str::before($throttleKey, '|')),
                // Whatever the account: one address trying passwords on many accounts. Mobile operators put many
                // customers behind one address, hence the margin.
                Limit::perMinute(30)->by('ip|'.$request->ip()),
            ];
        });

        // Sign-up says when a phone number already has an account: limited so it cannot list the customers (F-147).
        RateLimiter::for('fortify-forms', fn (Request $request) => $request->routeIs('register.store')
            ? Limit::perMinute(10)->by('register|'.$request->ip())
            : Limit::none());

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
