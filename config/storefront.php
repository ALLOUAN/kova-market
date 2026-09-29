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

    'logo' => 'assets/images/logo/kova-logo.webp',
    // Same logo at 400 px wide, enough for the header and modals (sharp on retina screens), 4 KB instead of 19.
    'logo_small' => 'assets/images/logo/kova-logo-400.webp',

    'favicon' => 'assets/images/logo/kova-favicon.png',

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

    // Storefront features that can be switched off (EX-14 favourites, EX-15 comparison, EX-20/22 newsletter).
    'features' => [
        // Favourites ("Mes favoris"): on.
        'wishlist' => (bool) env('STORE_WISHLIST', true),
        // Product comparison (up to 4 products): on.
        'compare' => (bool) env('STORE_COMPARE', true),
        // Newsletter invitation window (EX-20, EX-22): off by default, switched on in Paramètres › Newsletter.
        'welcome_popup' => (bool) env('STORE_WELCOME_POPUP', false),
        // The footer's newsletter sign-up (EX-22, P-04): on; can be switched off in Paramètres › Newsletter.
        'newsletter' => (bool) env('STORE_NEWSLETTER', true),
    ],

    // Payment methods shown in the footer (F-016), with the operators' logos (public/assets/images/payment,
    // 112 px high). A method without logo shows its name. The back-office can replace each logo (Paramètres).
    'payment_methods' => [
        ['label' => 'Orange Money', 'logo' => 'assets/images/payment/orange-money.webp'],
        ['label' => 'MTN MoMo', 'logo' => 'assets/images/payment/mtn-momo.webp'],
        ['label' => 'Moov Money', 'logo' => 'assets/images/payment/moov-money.webp'],
        ['label' => 'Wave', 'logo' => 'assets/images/payment/wave.webp'],
        ['label' => 'Paiement à la livraison', 'logo' => null],
    ],

    // The theme's sample banner (English text, prices in dollars) is not shown: set one from the back-office.
    'footer_banner' => null,

    'newsletter' => [
        'title' => 'Abonnez-vous à notre',
        'highlight' => 'newsletter',
        'subtitle' => 'Recevez nos offres et nouveautés en avant-première',
        // Invitation window (features.welcome_popup): texts, optional image, seconds before it opens.
        'popup_title' => 'Ne manquez pas nos offres',
        'popup_text' => 'Recevez nos nouveautés et nos codes promo en avant-première.',
        'popup_image' => 'assets/images/banner-img/welcome-banner-img-01.webp',
        'popup_delay' => 15,
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
    | cart_action: "sidenav" opens the mini-cart side panel after an add to cart,
    |              any other value stays on the page with the confirmation message.
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

    /*
    | Home page: whole browser and Google title (empty: "<store name> - Boutique en ligne à Abidjan"), and the store's
    | guarantees shown under the hero. Both editable in Paramètres de la boutique; icon: see App\Filament\Pages\Settings.
    */
    'home_title' => null,

    // Order and visibility of the home page sections, set in the back-office; empty: the default order, all shown.
    'home_sections' => null,

    'guarantees' => [
        ['icon' => 'truck-fast', 'title' => 'Livraison rapide', 'text' => 'À Abidjan en 24 à 48 h'],
        ['icon' => 'mobile-screen', 'title' => 'Mobile Money', 'text' => 'Orange, MTN, Moov, Wave'],
        ['icon' => 'hand-holding-dollar', 'title' => 'Paiement à la livraison', 'text' => 'Payez en recevant votre colis'],
        ['icon' => 'headset', 'title' => 'Service client', 'text' => 'Du lundi au samedi'],
    ],

];
