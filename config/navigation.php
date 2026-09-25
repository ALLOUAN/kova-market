<?php

/*
|--------------------------------------------------------------------------
| Storefront navigation
|--------------------------------------------------------------------------
|
| Every link accepts either a "route" (named route) or a "url". Links whose
| page does not exist yet have neither and render as "#"; give them a route
| once the page is built. ":store" is replaced by the store name.
|
| The "Shop" mega menu, the mobile "Categories" tab and the category side
| panel are generated from the category tree stored in the database.
|
*/

return [

    'main' => [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Shop', 'type' => 'categories'],
        [
            'label' => 'Pages',
            'type' => 'mega',
            'columns' => [
                [
                    'title' => 'Shop',
                    'links' => [
                        ['label' => 'All Products', 'badge' => ['label' => 'SHOP', 'variant' => 'green']],
                        ['label' => 'Categories List'],
                        ['label' => 'Shop by Brands'],
                        ['label' => 'Special Offers', 'badge' => ['label' => 'HOT', 'variant' => 'danger']],
                        ['label' => 'Compare Products'],
                        ['label' => 'Find A Store'],
                    ],
                ],
                [
                    'title' => 'My Account',
                    'links' => [
                        ['label' => 'Sign In'],
                        ['label' => 'Sign Up'],
                        ['label' => 'Personal info'],
                        ['label' => 'Order History'],
                        ['label' => 'Wishlist'],
                        ['label' => 'Payment Methods'],
                        ['label' => 'Notifications'],
                    ],
                ],
                [
                    'title' => 'Orders & Checkout',
                    'links' => [
                        ['label' => 'Cart'],
                        ['label' => 'Checkout'],
                        ['label' => 'Track Your Order'],
                        ['label' => 'Return Policy', 'badge' => ['label' => 'New', 'variant' => 'yellow']],
                    ],
                ],
                [
                    'title' => 'Help',
                    'links' => [
                        ['label' => 'Help Center'],
                        ['label' => 'FAQs'],
                        ['label' => 'Contact Us'],
                        ['label' => 'Privacy Policy'],
                        ['label' => 'Terms and conditions'],
                    ],
                ],
                [
                    'title' => 'Company',
                    'links' => [
                        ['label' => 'About Us'],
                        ['label' => 'Our Team'],
                        ['label' => 'Blog'],
                        ['label' => 'Careers'],
                    ],
                ],
            ],
        ],
        [
            'label' => 'Help',
            'type' => 'dropdown',
            'links' => [
                ['label' => 'Help Center'],
                ['label' => 'FAQs'],
                ['label' => 'Shipping & Delivery'],
                ['label' => 'Returns & Refunds'],
                ['label' => 'Contact Us', 'badge' => ['label' => '24/7', 'variant' => 'green']],
            ],
        ],
    ],

    'sidebar' => [
        'Quick Links' => [
            ['label' => 'About us'],
            ['label' => 'Reviews'],
            ['label' => 'Delivery & payment'],
            ['label' => 'Blog Articles'],
        ],
        'More Links' => [
            ['label' => 'Contacts'],
            ['label' => 'Information'],
            ['label' => 'Terms & Conditions'],
        ],
    ],

    'footer' => [
        'Let Us Help You' => [
            ['label' => 'Account Info'],
            ['label' => 'Your Orders'],
            ['label' => 'Returns & Replacements'],
            ['label' => 'Shipping Rates & Policies'],
            ['label' => 'Refund and Returns Policy'],
            ['label' => 'Privacy Policy'],
            ['label' => 'Terms and Conditions'],
            ['label' => 'Cookie Settings'],
            ['label' => 'Help Center'],
        ],
        'Make Money with Us' => [
            ['label' => 'Sell on :store'],
            ['label' => 'Sell Your Services on :store'],
            ['label' => 'Sell on :store Business'],
            ['label' => 'Sell Your Apps on :store'],
            ['label' => 'Become an Affiliate'],
            ['label' => 'Advertise Your Products'],
            ['label' => 'Sell-Publish with Us'],
            ['label' => 'Become a :store Vendor'],
            ['label' => ':store Affiliation Program'],
        ],
        'Get to Know Us' => [
            ['label' => 'Careers at :store'],
            ['label' => 'About :store'],
            ['label' => 'Investor Relations'],
            ['label' => ':store Devices'],
            ['label' => 'Customer reviews'],
            ['label' => 'Social Responsibility'],
            ['label' => 'Store Locations'],
            ['label' => ':store Near Me'],
            ['label' => ':store Dealership'],
        ],
    ],

    'legal' => [
        ['label' => 'Refund policy'],
        ['label' => 'Privacy policy'],
        ['label' => 'Term & conditions'],
    ],

];
