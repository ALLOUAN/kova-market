<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Courier area (F-124, F-125): couriers only. A suspended courier is signed out at the next click, and a
 * temporary password must be replaced before anything else.
 */
class EnsureCourier
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->hasRole(Role::Courier->value) || ! $user->courier) {
            abort(403);
        }

        if ($user->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('courier.login')->with('courier_error', 'Votre compte livreur est suspendu. Contactez la boutique.');
        }

        if ($user->must_change_password && ! $request->routeIs('courier.password.*', 'courier.logout')) {
            return redirect()->route('courier.password.edit');
        }

        return $next($request);
    }
}
