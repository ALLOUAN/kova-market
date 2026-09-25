<?php

/*
|--------------------------------------------------------------------------
| Home page marketing content
|--------------------------------------------------------------------------
|
| Editorial content of the home page (hero slider and promotional banners).
| Product listings are driven by the database collections referenced below.
|
*/

return [

    'hero' => [
        ['image' => 'assets/images/product-banner/product-banner-img-17.webp', 'subtitle' => 'Exclusive Offer Going', 'highlight' => 'GOPRO', 'title' => 'HERO 10', 'price' => 189.00, 'compare_at_price' => 295.00, 'badge' => 'Save 30%'],
        ['image' => 'assets/images/product-banner/product-banner-img-18.webp', 'subtitle' => 'Limited Weekend Deal', 'highlight' => 'OSMO MINI', 'title' => 'PRO', 'price' => 249.00, 'compare_at_price' => 295.00, 'badge' => 'Save 30%'],
        ['image' => 'assets/images/product-banner/product-banner-img-20.webp', 'subtitle' => 'Limited Weekend Deal', 'highlight' => 'AIRPODS', 'title' => 'PRO', 'price' => 179.98, 'compare_at_price' => 295.00, 'badge' => 'Save 30%'],
        ['image' => 'assets/images/product-banner/product-banner-img-19.webp', 'subtitle' => 'Exclusive Offer Going', 'highlight' => 'DSLR', 'title' => 'PERFORS', 'price' => 179.98, 'compare_at_price' => 295.00, 'badge' => 'Save 30%'],
        ['image' => 'assets/images/product-banner/product-banner-img-21.webp', 'subtitle' => 'Exclusive Offer Going', 'highlight' => 'IPAD', 'title' => 'PRO M1', 'price' => 179.98, 'compare_at_price' => 295.00, 'badge' => 'Save 30%'],
        ['image' => 'assets/images/product-banner/product-banner-img-22.webp', 'subtitle' => 'Limited Weekend Deal', 'highlight' => 'MACBOOK', 'title' => 'PRO M1', 'price' => 179.98, 'compare_at_price' => 295.00, 'badge' => 'Save 30%'],
    ],

    'banners' => [
        // Card next to the "Popular By Categories" grid.
        'categories' => [
            'image' => 'assets/images/catagory-img/banner-cat-01.webp',
            'subtitle' => 'Weekend Deal',
            'highlight' => 'DJI Ronin',
            'title' => 'Action',
            'tagline' => 'Super holiday',
        ],
        // Wide banner above "Today's best deals".
        'best_deals' => [
            'image' => 'assets/images/product-banner/product-banner-img-01.webp',
            'subtitle' => 'Power Up Deals',
            'highlight' => 'New Device',
            'title' => 'coming Soon',
            'tagline' => 'Land major deals',
        ],
        // Tall banner next to "This Week's Highlights".
        'highlights' => [
            'image' => 'assets/images/product-banner/product-banner-img-02.webp',
            'subtitle' => 'Power Up Deals',
            'highlight' => 'Red Camera',
            'title' => 'Plus',
            'tagline' => 'Holiday Cheers',
        ],
        // Full width banner closing the page.
        'closing' => [
            'image' => 'assets/images/product-banner/product-banner-img-03.webp',
            'subtitle' => 'Exclusive Weekend Discount',
            'title' => 'Feel-The good',
            'highlight' => 'shopping Up to 50% Discount',
            'tagline' => 'Incredibly slim designs.....',
            'price' => 179.98,
            'compare_at_price' => 295.00,
        ],
    ],

    'deals_filters' => ['Best Sellers', 'New Arrivals', 'On Sale'],

];
