<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response (F-140, F-143): HSTS over HTTPS, content security policy from
 * config/security.php, no MIME sniffing, no framing by other sites, a strict referrer and no browser features
 * the store does not use.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        if ($request->isSecure()) {
            $hsts = 'max-age='.config('security.hsts.max_age').(config('security.hsts.include_subdomains') ? '; includeSubDomains' : '');
            $headers->set('Strict-Transport-Security', $hsts);
        }

        if (config('security.csp.enabled')) {
            $name = config('security.csp.report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $headers->set($name, $this->policy($request));
        }

        return $response;
    }

    private function policy(Request $request): string
    {
        $directives = collect(config('security.csp.directives'))
            ->map(fn (array $sources, string $directive) => $directive.' '.implode(' ', $sources));

        if ($request->isSecure()) {
            $directives->push('upgrade-insecure-requests');
        }

        return $directives->implode('; ');
    }
}
