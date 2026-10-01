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

        // A page or an API answer made for a signed-in person is never kept by the browser, a proxy or a CDN
        // (F-147): a cached copy could be served to the next visitor, already signed in. Files keep their own rules.
        $personal = $request->bearerToken() !== null || ($request->hasSession() && $request->user() !== null);
        if ($personal && preg_match('~^(text/html|application/json)~', (string) $headers->get('Content-Type'))) {
            $headers->set('Cache-Control', 'no-store, private');
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
