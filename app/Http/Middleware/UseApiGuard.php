<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * API v1 (F-160): the customer is recognised by a Sanctum bearer token on every route, signed-in or not, so the
 * shared services (cart, checkout) see the same user as `auth:sanctum` routes; guests stay guests.
 */
class UseApiGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
