<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // SMS notifications (F-131). "log" writes them to storage/logs/sms.log, "null" discards them (tests);
    // add a provider class for real sending.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'sender' => env('SMS_SENDER', 'KOVA'),
    ],

    // Online payment (F-060 to F-067): CinetPay API v1 (OAuth). Keys of KOVA MARKET's own merchant account.
    'cinetpay' => [
        'api_key' => env('CINETPAY_API_KEY'),
        'api_password' => env('CINETPAY_API_PASSWORD'),
        'base_url' => env('CINETPAY_BASE_URL', 'https://api.cinetpay.co'),
        'currency' => env('CINETPAY_CURRENCY', 'XOF'),
        // CinetPay requires a valid e-mail; used for customers who ordered without one (else the store's contact e-mail).
        'fallback_email' => env('CINETPAY_FALLBACK_EMAIL'),
    ],

    // Cloudflare Turnstile on the public forms (F-080), active once both keys are set.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

];
