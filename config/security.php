<?php

/*
|--------------------------------------------------------------------------
| HTTP security headers (F-140, F-143)
|--------------------------------------------------------------------------
|
| Sent on every response by App\Http\Middleware\SecurityHeaders. HSTS is only sent over HTTPS. The content
| security policy lists every outside origin the pages use: add the analytics origins here when F-155 is
| wired. SECURITY_CSP_REPORT_ONLY=true sends it as "Report-Only" to check a change without breaking pages.
|
*/

return [

    /*
    | Preproduction (APP_ENV=staging) holds a copy of the production data after each monthly restore test (F-173):
    | with PREPROD_USER and PREPROD_PASSWORD set, every page asks for them (except /up for the health checks).
    */
    'preproduction' => [
        'user' => env('PREPROD_USER'),
        'password' => env('PREPROD_PASSWORD'),
    ],

    'hsts' => [
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => (bool) env('SECURITY_HSTS_SUBDOMAINS', true),
    ],

    'csp' => [
        'enabled' => (bool) env('SECURITY_CSP_ENABLED', true),
        'report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', false),

        // The storefront theme and Filament (Alpine.js, Livewire) run inline scripts and styles, hence
        // 'unsafe-inline' and 'unsafe-eval'; the policy still limits where scripts, frames and forms can go.
        // Google Analytics, Meta and TikTok (F-155) are allowed here but only loaded after consent (analytics.js).
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => [
                "'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://challenges.cloudflare.com',
                'https://www.googletagmanager.com', 'https://connect.facebook.net', 'https://analytics.tiktok.com',
            ],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://fonts.bunny.net'],
            'font-src' => ["'self'", 'data:', 'https://fonts.gstatic.com', 'https://fonts.bunny.net'],
            'img-src' => [
                "'self'", 'data:', 'blob:', 'https://ui-avatars.com',
                'https://www.google-analytics.com', 'https://www.googletagmanager.com', 'https://www.facebook.com', 'https://analytics.tiktok.com',
            ],
            'media-src' => ["'self'"],
            'connect-src' => [
                "'self'", 'https://challenges.cloudflare.com',
                'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://www.googletagmanager.com',
                'https://www.facebook.com', 'https://connect.facebook.net', 'https://analytics.tiktok.com',
            ],
            'frame-src' => ["'self'", 'https://challenges.cloudflare.com', 'https://www.google.com'],
            'frame-ancestors' => ["'self'"],
            'form-action' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
        ],
    ],

];
