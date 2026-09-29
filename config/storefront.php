<?php

/*
|--------------------------------------------------------------------------
| Storefront identity & behaviour
|--------------------------------------------------------------------------
|
| Store-wide values used by the layout (header, footer, menus, modals).
| Catalog content lives in the database; home page marketing content lives
| in config/homepage.php and navigation in config/navigation.php.
|
| Contact values are placeholders until the client provides the real ones
| (set them in .env; they will move to the back-office settings).
|
*/

return [

    'name' => env('STORE_NAME', 'KOVA MARKET'),

    'description' => 'KOVA MARKET, votre boutique en ligne à Abidjan : produits de qualité, paiement Mobile Money et livraison dans votre commune.',

    'about' => 'Boutique en ligne basée à Abidjan. Commandez en quelques clics, payez par Mobile Money ou à la livraison.',

    'logo' => 'assets/images/logo/logo.webp',
    // Same logo at 400 px wide, enough for the header and modals (sharp on retina screens), 4 KB instead of 19.
    'logo_small' => 'assets/images/logo/logo-400.webp',

    'favicon' => 'assets/images/favicon.png',

    // ISO code of the store currency and the symbol displayed after amounts (whole FCFA, no decimals).
    'currency' => env('STORE_CURRENCY', 'XOF'),

    'currency_symbol' => 'FCFA',

    'contact' => [
        'phone' => env('STORE_PHONE', '+225 07 00 00 00 00'),
        'toll_free' => env('STORE_TOLL_FREE', '+225 07 00 00 00 00'),
        // WhatsApp number used for the floating button and pre-filled messages (F-081).
        'whatsapp' => env('STORE_WHATSAPP', ''),
        'email' => env('STORE_EMAIL', 'contact@kovamarket.ci'),
        'address' => env('STORE_ADDRESS', 'Abidjan, Côte d’Ivoire'),
        'opening_hours' => 'Lun - Sam : 08h00 - 19h00',
    ],

    // The switchers are hidden while a single option is configured.
    'currencies' => [
        ['code' => 'XOF', 'label' => 'FCFA'],
    ],

    'languages' => [
        ['code' => 'fr', 'label' => 'Français', 'flag' => 'assets/images/icons/eng.webp'],
    ],

    // Icons whose url is "#" are hidden until the real profile is set.
    'social' => [
        ['key' => 'facebook', 'icon' => 'fa-facebook-f', 'url' => env('STORE_FACEBOOK_URL', '#')],
        ['key' => 'instagram', 'icon' => 'fa-instagram', 'url' => env('STORE_INSTAGRAM_URL', '#')],
        ['key' => 'tiktok', 'icon' => 'fa-tiktok', 'url' => env('STORE_TIKTOK_URL', '#')],
        ['key' => 'whatsapp', 'icon' => 'fa-whatsapp', 'url' => env('STORE_WHATSAPP_URL', '#')],
        ['key' => 'youtube', 'icon' => 'fa-youtube', 'url' => env('STORE_YOUTUBE_URL', '#')],
    ],

    'app_stores' => [
        ['image' => 'assets/images/footer/apple-store-logo.webp', 'label' => 'App Store', 'url' => '#'],
        ['image' => 'assets/images/footer/play-store-logo.webp', 'label' => 'Google Play', 'url' => '#'],
    ],

    // Template features without a back end yet (EX-14 wishlist, EX-15 comparison): hidden in V1, wired in V1.1.
    'features' => [
        'wishlist' => (bool) env('STORE_WISHLIST', false),
        'compare' => (bool) env('STORE_COMPARE', false),
    ],

    // Payment methods shown in the footer (F-016). Drop each operator's official logo at the given path
    // (height about 28 px); until then its name is shown.
    'payment_methods' => [
        ['label' => 'Orange Money', 'logo' => 'assets/images/payment/orange-money.webp'],
        ['label' => 'MTN MoMo', 'logo' => 'assets/images/payment/mtn-momo.webp'],
        ['label' => 'Moov Money', 'logo' => 'assets/images/payment/moov-money.webp'],
        ['label' => 'Wave', 'logo' => 'assets/images/payment/wave.webp'],
        ['label' => 'Paiement à la livraison', 'logo' => null],
    ],

    'footer_banner' => 'assets/images/footer/banner-image1.png',

    'newsletter' => [
        'title' => 'Abonnez-vous à notre',
        'highlight' => 'newsletter',
        'subtitle' => 'Recevez nos offres et nouveautés en avant-première',
    ],

    /*
    | Messages rotating in the header top bar and in the sticky header campaign strip.
    */
    'announcements' => [
        'trending' => [
            'Paiement par Orange Money, MTN MoMo, Moov Money et Wave',
            'Livraison rapide dans toutes les communes d’Abidjan',
            'Paiement à la livraison disponible',
        ],
        'campaign' => [
            'Les meilleurs produits, aux meilleurs prix.',
            'Commandez aujourd’hui, faites-vous livrer demain.',
            'Payez en toute sécurité par Mobile Money.',
        ],
    ],

    'search' => [
        'placeholders' => ['Rechercher un produit...', 'Que cherchez-vous ?', 'Trouvez ce qu’il vous faut...'],
        'popular' => ['Smartphones', 'Ordinateurs', 'Télévisions', 'Audio', 'Électroménager', 'Accessoires'],
    ],

    /*
    | Product card behaviour, shared by every product listing.
    |
    | quick_view:  "modal"   opens the quick view modal,
    |              "sidenav" opens the quick view side panel.
    | cart_action: "sidenav" opens the mini-cart side panel,
    |              "popup"   opens the "added to cart" popup.
    */
    'product_card' => [
        'quick_view' => 'modal',
        'cart_action' => 'sidenav',
        'limited_stock_threshold' => 3,
    ],

    /*
    | Delivery information shown in the expandable details of product cards.
    */
    'shipping' => [
        'delay' => 'Livraison à Abidjan en 24 à 48 h',
    ],

];
