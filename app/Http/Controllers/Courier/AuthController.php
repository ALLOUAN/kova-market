<?php

namespace App\Http\Controllers\Courier;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Courier sign-in (F-122): phone number and password, 5 attempts per minute, courier accounts only.
 */
class AuthController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        return $request->user()?->hasRole(Role::Courier->value) ? redirect()->route('courier.home') : view('courier.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], ['phone' => 'téléphone', 'password' => 'mot de passe']);

        $key = 'courier-login:'.(PhoneNumber::normalize($data['phone']) ?? $data['phone']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['phone' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($key).' secondes.']);
        }

        $user = User::findByLogin($data['phone']);

        if (! User::passwordMatches($user, $data['password']) || ! $user->hasRole(Role::Courier->value)) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['phone' => 'Numéro ou mot de passe incorrect.']);
        }

        if ($user->isSuspended()) {
            throw ValidationException::withMessages(['phone' => 'Votre compte livreur est suspendu. Contactez la boutique.']);
        }

        RateLimiter::clear($key);
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('courier.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('courier.login');
    }
}
