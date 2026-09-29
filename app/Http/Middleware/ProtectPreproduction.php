<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Preproduction behind a shared login (F-171, F-173): it holds real customer data after the restore tests, so it
 * must not be browsable by the public. Only for APP_ENV=staging with PREPROD_USER / PREPROD_PASSWORD set; the
 * /up health check stays open for the deploy and restore workflows.
 */
class ProtectPreproduction
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = config('security.preproduction.user');
        $password = config('security.preproduction.password');

        if (! app()->environment('staging') || blank($user) || blank($password) || $request->is('up')) {
            return $next($request);
        }

        if (hash_equals((string) $user, (string) $request->getUser()) && hash_equals((string) $password, (string) $request->getPassword())) {
            return $next($request);
        }

        return response('Préproduction : accès réservé.', 401, ['WWW-Authenticate' => 'Basic realm="Preproduction", charset="UTF-8"']);
    }
}
