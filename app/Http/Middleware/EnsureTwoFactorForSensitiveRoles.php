<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * Filament's "two-factor required" gate, applied only to the roles that must use it (F-100):
 * super-admins and managers are sent to the set-up page until an authenticator app is configured.
 */
class EnsureTwoFactorForSensitiveRoles extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->requiresTwoFactor()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
