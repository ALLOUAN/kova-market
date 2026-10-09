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

    // WhatsApp Business (F-134): customer and courier messages, verification codes. "twilio" sends through Twilio,
    // "cloud" through Meta's Cloud API directly, "log" writes them to storage/logs/whatsapp.log, "null" discards
    // them (tests). Templates: config/whatsapp.php.
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        // Webhook (delivery reports): the app secret signs Meta's calls, the verify token answers its check.
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        // "twilio": WhatsApp through Twilio. Account SID and auth token from the Twilio console; "from" is the
        // WhatsApp sender, e.g. the sandbox number shown in the Twilio console. Template Content SIDs (HX…) are set in the
        // back-office (Paramètres › Commandes › Notifications).
        'twilio' => [
            'sid' => env('TWILIO_ACCOUNT_SID'),
            'token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_WHATSAPP_FROM'),
        ],
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

    // Meta Conversions API: the pixel's events also sent by the server (App\Services\Storefront\MetaConversions),
    // once the pixel is set in the store settings and this token is given. The test code shows them in Events
    // Manager's "Test events" tab.
    'meta' => [
        'conversions_token' => env('META_CONVERSIONS_TOKEN'),
        'test_event_code' => env('META_TEST_EVENT_CODE'),
        'graph_version' => env('META_GRAPH_VERSION', 'v23.0'),
    ],

    // Cloudflare Turnstile on the public forms (F-080), active once both keys are set.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

];
