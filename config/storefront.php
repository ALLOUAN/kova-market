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
*/

return [

    'name' => env('STORE_NAME', 'KOVA MARKET'),

    'description' => 'KOVA MARKET is a modern marketplace for electronics, gadgets and home devices.',

    'about' => 'Worldwide electronics store. We sell over 1000+ branded products on our website.',

    'logo' => 'assets/images/logo/logo.webp',

    'favicon' => 'assets/images/favicon.png',

    'currency' => env('STORE_CURRENCY', 'USD'),

    'contact' => [
        'phone' => env('STORE_PHONE', '+800 300-353-569'),
        'toll_free' => env('STORE_TOLL_FREE', '0 800 300-353'),
        'email' => env('STORE_EMAIL', 'hello@kovamarket.com'),
        'address' => env('STORE_ADDRESS', 'Boston, 44 Main street'),
        'opening_hours' => 'Mon-Sun 09:00 - 19:00',
    ],

    'currencies' => [
        ['code' => 'USD', 'label' => '$ USD'],
        ['code' => 'GBP', 'label' => '£ GBP'],
        ['code' => 'EUR', 'label' => '€ EUR'],
    ],

    'languages' => [
        ['code' => 'en', 'label' => 'English', 'flag' => 'assets/images/icons/eng.webp'],
        ['code' => 'da', 'label' => 'Danish', 'flag' => 'assets/images/icons/den.webp'],
        ['code' => 'it', 'label' => 'Italian', 'flag' => 'assets/images/icons/italic.webp'],
    ],

    'social' => [
        ['icon' => 'fa-x-twitter', 'url' => '#'],
        ['icon' => 'fa-youtube', 'url' => '#'],
        ['icon' => 'fa-facebook-f', 'url' => '#'],
        ['icon' => 'fa-whatsapp', 'url' => '#'],
        ['icon' => 'fa-instagram', 'url' => '#'],
        ['icon' => 'fa-telegram', 'url' => '#'],
    ],

    'app_stores' => [
        ['image' => 'assets/images/footer/apple-store-logo.webp', 'label' => 'App Store', 'url' => '#'],
        ['image' => 'assets/images/footer/play-store-logo.webp', 'label' => 'Google Play', 'url' => '#'],
    ],

    'payment_methods_image' => 'assets/images/payment-brand/image-01.webp',

    'footer_banner' => 'assets/images/footer/banner-image1.png',

    'newsletter' => [
        'title' => 'Subscribe our',
        'highlight' => 'newsletter',
        'subtitle' => 'Subscribe and get discount 20% Off',
    ],

    /*
    | Messages rotating in the header top bar and in the sticky header campaign strip.
    */
    'announcements' => [
        'trending' => [
            'christmas, thanksgiving, trees, decor, ornaments',
            'Looking for something Explore thanksgiving?',
            'Explore what hanksgiving, trees you need decor',
        ],
        'campaign' => [
            'Top products. Better prices -under $100.',
            'Top products. Better prices -under $100.',
            'Top products. Better prices -under $100.',
        ],
    ],

    'search' => [
        'placeholders' => ['Search for something...', 'Looking for something specific?', 'Explore what you need...'],
        'popular' => ['Fashion', 'Interior', 'Nature', 'Jewellery', 'Art', 'Technology', 'Texture', 'Architecture', 'Business'],
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
        'delay' => '2–3 weeks Free Shipping',
    ],

];
