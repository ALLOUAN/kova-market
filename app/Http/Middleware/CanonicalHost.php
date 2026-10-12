<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One address for the whole site (search engines): in production, a page asked on another host (kova-market.com
 * without "www") or over plain http is sent, permanently, to the same path on APP_URL (https://www.kova-market.com).
 * Only GET and HEAD: payment returns and webhooks posted to another host still reach the application.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction() || ! $request->isMethodSafe() || $request->is('up')) {
            return $next($request);
        }

        $canonical = parse_url((string) config('app.url'));
        $host = $canonical['host'] ?? null;
        $https = ($canonical['scheme'] ?? 'https') === 'https';

        if ($host === null || ($request->getHost() === $host && (! $https || $request->isSecure()))) {
            return $next($request);
        }

        return redirect()->to(($https ? 'https' : 'http').'://'.$host.$request->getRequestUri(), 301);
    }
}
