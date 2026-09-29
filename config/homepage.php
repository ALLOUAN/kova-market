<?php

/*
|--------------------------------------------------------------------------
| Home page marketing content
|--------------------------------------------------------------------------
|
| Sample banners of the theme (hero slider and promotional banners), copied
| into the back-office by the demo BannerSeeder. The site never shows them by
| itself: an empty banner slot hides its banner. Amounts are whole FCFA.
|
*/

return [

    'hero' => [
        ['image' => 'assets/images/product-banner/product-banner-img-17.webp', 'subtitle' => 'Offre exclusive en cours', 'highlight' => 'GOPRO', 'title' => 'HERO 10', 'price' => 113500, 'compare_at_price' => 162000, 'badge' => '-30 %'],
        ['image' => 'assets/images/product-banner/product-banner-img-18.webp', 'subtitle' => 'Offre du week-end', 'highlight' => 'OSMO MINI', 'title' => 'PRO', 'price' => 149500, 'compare_at_price' => 213500, 'badge' => '-30 %'],
        ['image' => 'assets/images/product-banner/product-banner-img-20.webp', 'subtitle' => 'Offre du week-end', 'highlight' => 'AIRPODS', 'title' => 'PRO', 'price' => 108000, 'compare_at_price' => 154000, 'badge' => '-30 %'],
        ['image' => 'assets/images/product-banner/product-banner-img-19.webp', 'subtitle' => 'Offre exclusive en cours', 'highlight' => 'REFLEX', 'title' => 'NUMÉRIQUE', 'price' => 108000, 'compare_at_price' => 154000, 'badge' => '-30 %'],
        ['image' => 'assets/images/product-banner/product-banner-img-21.webp', 'subtitle' => 'Offre exclusive en cours', 'highlight' => 'IPAD', 'title' => 'PRO M1', 'price' => 108000, 'compare_at_price' => 154000, 'badge' => '-30 %'],
        ['image' => 'assets/images/product-banner/product-banner-img-22.webp', 'subtitle' => 'Offre du week-end', 'highlight' => 'MACBOOK', 'title' => 'PRO M1', 'price' => 108000, 'compare_at_price' => 154000, 'badge' => '-30 %'],
    ],

    'banners' => [
        // Card next to the "Catégories populaires" grid.
        'categories' => [
            'image' => 'assets/images/catagory-img/banner-cat-01.webp',
            'subtitle' => 'Offre du week-end',
            'highlight' => 'DJI Ronin',
            'title' => 'Action',
            'tagline' => 'Filmez comme un pro',
        ],
        // Wide banner above "Les meilleures offres du jour".
        'best_deals' => [
            'image' => 'assets/images/product-banner/product-banner-img-01.webp',
            'subtitle' => 'Offres chocs',
            'highlight' => 'Nouvel appareil',
            'title' => 'bientôt disponible',
            'tagline' => 'Des prix imbattables',
        ],
        // Tall banner next to "Les incontournables de la semaine".
        'highlights' => [
            'image' => 'assets/images/product-banner/product-banner-img-02.webp',
            'subtitle' => 'Offres chocs',
            'highlight' => 'Caméra rouge',
            'title' => 'Plus',
            'tagline' => 'Offre de saison',
        ],
        // Full width banner closing the page.
        'closing' => [
            'image' => 'assets/images/product-banner/product-banner-img-03.webp',
            'subtitle' => 'Remise exclusive du week-end',
            'title' => 'Faites-vous plaisir',
            'highlight' => 'jusqu’à -50 % sur une sélection',
            'tagline' => 'Des designs incroyablement fins.',
            'price' => 108000,
            'compare_at_price' => 154000,
        ],
    ],

];
