<?php

// Demo catalog reproducing the content of the original "Home Electronics" template.
// Dates are expressed relative to the seeding time so countdowns and promotions stay current.

return [
    'categories' => [
        [
            'name' => 'Photo et caméras',
            'icon' => 'fa-regular fa-camera',
            'tagline' => 'Les accessoires photo les plus demandés',
            'badge_label' => null,
            'badge_variant' => null,
            'image' => 'assets/images/catagory-img/cat-transp-img-07.webp',
            'is_featured' => true,
            'promo' => [
                'image' => 'assets/images/product-img/sidebar-category/product-banner.webp',
                'label' => 'Accessoires photo',
                'highlight' => '11 décembre',
                'title' => 'Jusqu’à -40 %',
                'subtitle' => 'Sur toutes les marques',
            ],
            'children' => [
                [
                    'name' => 'Caméra d’action',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-7.webp',
                    'children' => ['Sports Cameras', 'Underwater Cameras', '360 Cameras'],
                ],
                [
                    'name' => 'Objectifs',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-8.webp',
                    'children' => ['VR Cameras', 'Panoramic Cameras', '3D Cameras'],
                ],
                [
                    'name' => 'Appareil photo numérique',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-9.webp',
                    'children' => ['Drone Cameras', 'Helmet Cameras', 'Dual-Lens Cameras'],
                ],
                [
                    'name' => 'Reflex',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-10.webp',
                    'children' => ['Compact 360 Cameras', 'DSLR Cameras', 'Mirrorless Cameras'],
                ],
                [
                    'name' => 'Caméscope compact',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-11.webp',
                    'children' => ['Point-and-Shoot Cameras', 'Bridge Cameras', 'Compact Cameras'],
                ],
                [
                    'name' => 'Hybride',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-12.webp',
                    'children' => ['Full-Frame Mirrorless', 'APS-C Mirrorless', 'Micro Four Thirds Mirrorless'],
                ],
                [
                    'name' => 'Caméra embarquée',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-13.webp',
                    'children' => ['Compact Mirrorless', 'Medium Format Mirrorless', 'Panoramic'],
                ],
                [
                    'name' => 'Caméscope',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-14.webp',
                    'children' => ['Digital Camcorders', 'Professional Camcorders', '4K Camcorders'],
                ],
                [
                    'name' => 'Appareil instantané',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-15.webp',
                    'children' => ['Compact Camcorders', 'High Definition (HD) Camcorders', 'Panoramic'],
                ],
                [
                    'name' => 'Accessoires photo',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-16.webp',
                    'children' => ['SD Cards (High-Speed)', 'MicroSD Cards', 'External Hard Drives'],
                ],
                [
                    'name' => 'Trépied',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-17.webp',
                    'children' => ['Travel Tripods', 'Tabletop Tripods', 'Monopods'],
                ],
            ],
        ],
        [
            'name' => 'Montres connectées',
            'icon' => 'fa-regular fa-watch-apple',
            'tagline' => 'Toutes nos montres connectées',
            'badge_label' => 'EXCLUSIVE',
            'badge_variant' => 'primary',
            'image' => 'assets/images/catagory-img/cat-transp-img-08.webp',
            'is_featured' => true,
            'promo' => [
                'image' => 'assets/images/product-img/sidebar-category/product-banner.webp',
                'label' => 'À partir de',
                'highlight' => '11 décembre',
                'title' => 'Jusqu’à -40 %',
                'subtitle' => 'Sur toutes les marques',
            ],
            'children' => [
                [
                    'name' => 'Bracelet d’activité',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-1.webp',
                    'children' => ['Smart Bands', 'Heart Rate Monitors', 'Sleep Trackers'],
                ],
                [
                    'name' => 'Bluetooth',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-2.webp',
                    'children' => ['Luxury Bluetooth Watches', 'Hybrid Smartwatches', 'Kids\' Smartwatches'],
                ],
                [
                    'name' => 'Hybride',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-3.webp',
                    'children' => ['Fitness Hybrid Watches', 'Smart Hybrid Watches', 'Classic Hybrid Watches'],
                ],
                [
                    'name' => 'Classique',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-4.webp',
                    'children' => ['Analog Watches', 'Digital Watches', 'Dress Watches'],
                ],
                [
                    'name' => 'Écran tactile',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-5.webp',
                    'children' => ['Smartwatches', 'Fitness Trackers', 'Hybrid Smartwatches'],
                ],
            ],
        ],
        [
            'name' => 'TV, audio et vidéo',
            'icon' => 'fa-sharp fa-regular fa-camcorder',
            'tagline' => 'TV et audio-vidéo des plus grandes marques',
            'badge_label' => null,
            'badge_variant' => null,
            'image' => 'assets/images/catagory-img/cat-transp-img-09.webp',
            'is_featured' => true,
            'promo' => [
                'image' => 'assets/images/product-img/sidebar-category/product-banner.webp',
                'label' => 'À partir de',
                'highlight' => '11 décembre',
                'title' => 'Jusqu’à -40 %',
                'subtitle' => 'Sur toutes les marques',
            ],
            'children' => [
                [
                    'name' => 'QLED TV',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-18.webp',
                    'children' => [],
                ],
                [
                    'name' => 'Smart TV',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-19.webp',
                    'children' => [],
                ],
                [
                    'name' => 'TV UHD',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-20.webp',
                    'children' => [],
                ],
                [
                    'name' => 'TV HD',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-21.webp',
                    'children' => [],
                ],
                [
                    'name' => 'TV LED',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-22.webp',
                    'children' => [],
                ],
                [
                    'name' => 'TV 4K',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-23.webp',
                    'children' => [],
                ],
            ],
        ],
        [
            'name' => 'Jeux vidéo',
            'icon' => 'fa-light fa-game-console-handheld',
            'tagline' => 'Accessoires de jeu des meilleures marques',
            'badge_label' => 'TRENDING',
            'badge_variant' => 'green',
            'image' => 'assets/images/catagory-img/cat-transp-img-12.webp',
            'is_featured' => true,
            'promo' => [
                'image' => 'assets/images/product-img/sidebar-category/product-banner.webp',
                'label' => 'À partir de',
                'highlight' => '11 décembre',
                'title' => 'Jusqu’à -40 %',
                'subtitle' => 'Sur toutes les marques',
            ],
            'children' => [
                [
                    'name' => 'Clavier gaming',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-24.webp',
                    'children' => ['Apex Gamer Pro', 'Stealth Strike Keyboard', 'Rapid Fire RGB'],
                ],
                [
                    'name' => 'Casque gaming',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-25.webp',
                    'children' => ['SoundStorm Pro', 'EchoMaster Elite', 'BattleTune 360'],
                ],
                [
                    'name' => 'Fauteuil gaming',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-26.webp',
                    'children' => ['Elite Gamer Throne', 'Turbo Comfort Seat', 'Pro Series Gaming Chair'],
                ],
                [
                    'name' => 'Tapis de souris',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-27.webp',
                    'children' => ['GlidePro Mouse Pad', 'PixelPerfect Pad', 'EagleEye Mouse Mat'],
                ],
                [
                    'name' => 'Manette',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-28.webp',
                    'children' => ['ProGamer Joystick', 'Precision Play Controller', 'TurboGrip Joystick'],
                ],
                [
                    'name' => 'Casque VR',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-29.webp',
                    'children' => ['VisionSphere VR Headset', 'ImmersiveEye VR Goggles', 'RealityFusion Headset'],
                ],
                [
                    'name' => 'Accessoires PlayStation',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-30.webp',
                    'children' => ['Crystal Clear Faceplate', 'ComfortFit Chair', 'Dynamic RGB LED'],
                ],
                [
                    'name' => 'Bureau gaming',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-31.webp',
                    'children' => ['ProGamer Desk', 'Titan Gaming Station', 'Arcade Pro Desk'],
                ],
                [
                    'name' => 'Canapé gaming',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-32.webp',
                    'children' => ['Victory Lounge', 'Pixel Perch', 'Gamer\'s Retreat'],
                ],
            ],
        ],
        [
            'name' => 'Casques et musique',
            'icon' => 'fa-sharp fa-regular fa-headphones',
            'tagline' => 'Les meilleurs casques et produits audio',
            'badge_label' => null,
            'badge_variant' => null,
            'image' => 'assets/images/catagory-img/cat-transp-img-10.webp',
            'is_featured' => true,
            'promo' => [
                'image' => 'assets/images/product-img/sidebar-category/product-banner.webp',
                'label' => 'À partir de',
                'highlight' => '11 décembre',
                'title' => 'Jusqu’à -40 %',
                'subtitle' => 'Sur toutes les marques',
            ],
            'children' => [
                [
                    'name' => 'Casque Bluetooth',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-33.webp',
                    'children' => ['SoundWave Pro', 'AeroSound Bluetooth', 'PulseBeats Wireless'],
                ],
                [
                    'name' => 'Support de casque',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-34.webp',
                    'children' => ['Audio Aegis', 'Harmonic Holder', 'Headset Haven'],
                ],
                [
                    'name' => 'Home cinéma',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-35.webp',
                    'children' => ['Cinematic Sound Bar', 'Ultra HD Projector', '4K Smart TV'],
                ],
                [
                    'name' => 'Enceinte Bluetooth',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-36.webp',
                    'children' => ['SoundWave Pro', 'BassBlaster 360', 'AeroSound Compact'],
                ],
                [
                    'name' => 'Barre de son',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-37.webp',
                    'children' => ['Versatile Soundbar', 'Signature Series Soundbar', 'ProSound Soundbar'],
                ],
                [
                    'name' => 'Microphone',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-38.webp',
                    'children' => ['SoundWave Pro', 'EchoSphere Mic', 'ClearCast 3000'],
                ],
                [
                    'name' => 'Dictaphone',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-39.webp',
                    'children' => ['EchoNote Pro', 'VoxCapture 3000', 'SoundScribe'],
                ],
                [
                    'name' => 'Carte son',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-40.webp',
                    'children' => ['AeroSound Pro', 'EchoMaster FX', 'Vortex SoundBlaster'],
                ],
            ],
        ],
        [
            'name' => 'Électroménager',
            'icon' => 'fa-sharp fa-regular fa-blender-phone',
            'tagline' => 'Tout l’électroménager de la maison',
            'badge_label' => 'HOT',
            'badge_variant' => 'danger',
            'image' => 'assets/images/catagory-img/cat-transp-img-11.webp',
            'is_featured' => true,
            'promo' => [
                'image' => 'assets/images/product-img/sidebar-category/product-banner.webp',
                'label' => 'À partir de',
                'highlight' => '11 décembre',
                'title' => 'Jusqu’à -40 %',
                'subtitle' => 'Sur toutes les marques',
            ],
            'children' => [
                [
                    'name' => 'Climatiseur',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-41.webp',
                    'children' => ['CoolBreeze Pro', 'ChillMaster Elite', 'AirFlow Genius'],
                ],
                [
                    'name' => 'Chauffe-eau',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-42.webp',
                    'children' => ['AquaFlow Geysers', 'TurboHeat Geysers', 'EcoHeat Geysers'],
                ],
                [
                    'name' => 'Four',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-43.webp',
                    'children' => ['CrispBake Oven', 'QuickHeat Convection Oven', 'PerfectBake Electric Oven'],
                ],
                [
                    'name' => 'Friteuse sans huile',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-44.webp',
                    'children' => ['CrispMaster Air Fryer', 'Healthy Fry Pro', 'QuickCrisp Air Fryer'],
                ],
                [
                    'name' => 'Lave-linge',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-45.webp',
                    'children' => ['EcoClean Pro', 'UltraWash 360', 'QuickSpin Deluxe'],
                ],
                [
                    'name' => 'Machine à coudre',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-46.webp',
                    'children' => ['StitchPro 300', 'SewMaster Deluxe', 'QuiltCraft Elite'],
                ],
                [
                    'name' => 'Purificateur d’air',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-47.webp',
                    'children' => ['PureAir Breeze', 'FreshFlow Purifier', 'BreatheEasy Pro'],
                ],
                [
                    'name' => 'Aspirateur',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-48.webp',
                    'children' => ['PowerSweep Pro', 'UltraClean Cyclone', 'DustBuster Max'],
                ],
                [
                    'name' => 'Mixeur',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-49.webp',
                    'children' => ['Smoothie Master Pro', 'NutriBlend Ultra', 'EcoBlend Portable Blender'],
                ],
                [
                    'name' => 'Cuisinière',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-50.webp',
                    'children' => ['PowerMix 3000', 'Frozen Fusion Blender', 'UltraSmooth Blender'],
                ],
                [
                    'name' => 'Fer à repasser',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-51.webp',
                    'children' => ['Blender & Chop Duo', 'TurboMix Professional', 'BlendSmart 2-in-1'],
                ],
                [
                    'name' => 'Chauffage d’appoint',
                    'image' => 'assets/images/product-img/sidebar-category/category-product-52.webp',
                    'children' => ['HeatWave Blanket', 'ThermoCushion', 'SootheHeat Massager'],
                ],
            ],
        ],
        [
            'name' => 'Informatique et mobiles',
            'icon' => 'fa-regular fa-laptop',
            'tagline' => 'Ordinateurs portables, tablettes, téléphones et accessoires',
            'badge_label' => null,
            'badge_variant' => null,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-10-a-1.webp',
            'is_featured' => false,
            'promo' => [
                'image' => 'assets/images/product-img/sidebar-category/product-banner.webp',
                'label' => 'À partir de',
                'highlight' => '11 décembre',
                'title' => 'Jusqu’à -40 %',
                'subtitle' => 'Sur toutes les marques',
            ],
            'children' => [
                [
                    'name' => 'Ordinateurs portables',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-10-a-1.webp',
                    'children' => [],
                ],
                [
                    'name' => 'Smartphones',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-11-a-1.webp',
                    'children' => [],
                ],
                [
                    'name' => 'Tablettes',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-07-a-1.webp',
                    'children' => [],
                ],
                [
                    'name' => 'Accessoires informatiques',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-05-a-1.webp',
                    'children' => [],
                ],
                [
                    'name' => 'Accessoires mobiles',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-13-a-1.webp',
                    'children' => [],
                ],
                [
                    'name' => 'Webcams et streaming',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-06-a-1.webp',
                    'children' => [],
                ],
            ],
        ],
    ],
    'brands' => [
        [
            'name' => 'AMD',
            'logo' => 'assets/images/brands/brand-a-01.webp',
            'promo_label' => 'Jusqu’à -20 %',
        ],
        [
            'name' => 'Qualcomm',
            'logo' => 'assets/images/brands/brand-a-02.webp',
            'promo_label' => 'Jusqu’à -10 %',
        ],
        [
            'name' => 'Sony',
            'logo' => 'assets/images/brands/brand-a-03.webp',
            'promo_label' => 'Jusqu’à -15 %',
        ],
        [
            'name' => 'Asus',
            'logo' => 'assets/images/brands/brand-a-04.webp',
            'promo_label' => 'Jusqu’à -25 %',
        ],
        [
            'name' => 'Huawei',
            'logo' => 'assets/images/brands/brand-a-05.webp',
            'promo_label' => 'Jusqu’à -20 %',
        ],
        [
            'name' => 'Bose',
            'logo' => 'assets/images/brands/brand-a-06.webp',
            'promo_label' => 'Jusqu’à -15 %',
        ],
        [
            'name' => 'Samsung',
            'logo' => 'assets/images/brands/brand-a-07.webp',
            'promo_label' => 'Jusqu’à -12 %',
        ],
        [
            'name' => 'Lenovo',
            'logo' => 'assets/images/brands/brand-a-08.webp',
            'promo_label' => 'Jusqu’à -16 %',
        ],
        [
            'name' => 'Intel',
            'logo' => 'assets/images/brands/brand-a-09.webp',
            'promo_label' => 'Jusqu’à -10 %',
        ],
        [
            'name' => 'NVIDIA GeForce',
            'logo' => 'assets/images/brands/brand-a-10.webp',
            'promo_label' => 'Jusqu’à -14 %',
        ],
    ],
    'products' => [
        'Ultra-Thin Modern Tech Quiet Noise Cancelling Laptop' => [
            'category' => 'Ordinateurs portables',
            'brand' => 'Lenovo',
            'price' => 30000,
            'price_max' => 108000,
            'stock' => 9,
            'sold_count' => 95,
            'free_shipping' => true,
            'return_days' => 7,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-10-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-10-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'NOUVEAU',
                    'variant' => 'green',
                ],
                [
                    'label' => 'Meilleure vente',
                    'variant' => 'secondary-gradient',
                ],
            ],
            'variants_count' => 0,
            'specifications' => [
                [
                    'label' => 'Marque',
                    'value' => 'Sony Corporation Ltd',
                ],
                [
                    'label' => 'Résolution',
                    'value' => '3840×2160',
                ],
                [
                    'label' => 'Année de sortie',
                    'value' => 'Jan 2022',
                ],
                [
                    'label' => 'Carte mère',
                    'value' => 'Samsung
ATX, ITX, microATX, Mini-ITX',
                ],
            ],
        ],
        'Keurig Polaroid 4K Waterproof Smart Action Camera' => [
            'category' => 'Caméra d’action',
            'brand' => 'Sony',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 12,
            'sold_count' => 20,
            'free_shipping' => true,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-12-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-12-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'Promo',
                    'variant' => 'secondary',
                ],
            ],
            'variants_count' => 0,
            'specifications' => [
                [
                    'label' => 'Marque',
                    'value' => 'Sony Corporation Ltd',
                ],
                [
                    'label' => 'Résolution',
                    'value' => '3840×2160',
                ],
                [
                    'label' => 'Année de sortie',
                    'value' => 'Jan 2022',
                ],
                [
                    'label' => 'Carte mère',
                    'value' => 'Samsung
ATX, ITX, microATX, Mini-ITX',
                ],
            ],
        ],
        'Cubitt Smart Watch CTS Waterproof Fitness Tracker Watch PRO' => [
            'category' => 'Bracelet d’activité',
            'brand' => 'Huawei',
            'price' => 60000,
            'stock' => 4,
            'sold_count' => 20,
            'free_shipping' => true,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-03-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-03-a-1-hover.webp',
            'variants_count' => 0,
            'specifications' => [
                [
                    'label' => 'Marque',
                    'value' => 'Sony Corporation Ltd',
                ],
                [
                    'label' => 'Résolution',
                    'value' => '3840×2160',
                ],
                [
                    'label' => 'Année de sortie',
                    'value' => 'Jan 2022',
                ],
                [
                    'label' => 'Carte mère',
                    'value' => 'Samsung
ATX, ITX, microATX, Mini-ITX',
                ],
            ],
        ],
        'Apple IPhone 16 PRO max 6200U with 12GB RAM Phone' => [
            'category' => 'Smartphones',
            'brand' => 'Qualcomm',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 2,
            'sold_count' => 95,
            'free_shipping' => true,
            'return_days' => 7,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-11-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-11-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'Promo',
                    'variant' => 'secondary',
                ],
            ],
            'variants_count' => 0,
            'specifications' => [
                [
                    'label' => 'Marque',
                    'value' => 'Sony Corporation Ltd',
                ],
                [
                    'label' => 'Résolution',
                    'value' => '3840×2160',
                ],
                [
                    'label' => 'Année de sortie',
                    'value' => 'Jan 2022',
                ],
                [
                    'label' => 'Carte mère',
                    'value' => 'Samsung
ATX, ITX, microATX, Mini-ITX',
                ],
            ],
            'sale_ends_in_days' => 87,
        ],
        'Logitech Precision 9 Button Ergonomic Diital Wireless Mouse' => [
            'category' => 'Accessoires informatiques',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 0,
            'sold_count' => 95,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-05-a-2.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-05-a-1-hover.webp',
            'variants_count' => 0,
            'specifications' => [
                [
                    'label' => 'Marque',
                    'value' => 'Sony Corporation Ltd',
                ],
                [
                    'label' => 'Résolution',
                    'value' => '3840×2160',
                ],
                [
                    'label' => 'Année de sortie',
                    'value' => 'Jan 2022',
                ],
                [
                    'label' => 'Carte mère',
                    'value' => 'Samsung
ATX, ITX, microATX, Mini-ITX',
                ],
            ],
        ],
        'Keurig K-Duo 4K Waterproof Action Video Camera' => [
            'category' => 'Caméscope',
            'brand' => 'Sony',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 5,
            'sold_count' => 95,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-08-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-08-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'Nouveau',
                    'variant' => 'green',
                ],
            ],
            'variants_count' => 0,
            'specifications' => [
                [
                    'label' => 'Marque',
                    'value' => 'Sony Corporation Ltd',
                ],
                [
                    'label' => 'Résolution',
                    'value' => '3840×2160',
                ],
                [
                    'label' => 'Année de sortie',
                    'value' => 'Jan 2022',
                ],
                [
                    'label' => 'Carte mère',
                    'value' => 'Samsung
ATX, ITX, microATX, Mini-ITX',
                ],
            ],
        ],
        'Cubitt Smart Wireless Apple 16 PRO Charging Case Set' => [
            'category' => 'Accessoires mobiles',
            'brand' => 'Huawei',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 16,
            'sold_count' => 95,
            'free_shipping' => true,
            'return_days' => 7,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-13-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-13-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'Top',
                    'variant' => 'danger',
                ],
                [
                    'label' => 'Tendance',
                    'variant' => 'yellow',
                ],
            ],
            'variants_count' => 0,
            'specifications' => [
                [
                    'label' => 'Marque',
                    'value' => 'Sony Corporation Ltd',
                ],
                [
                    'label' => 'Résolution',
                    'value' => '3840×2160',
                ],
                [
                    'label' => 'Année de sortie',
                    'value' => 'Jan 2022',
                ],
                [
                    'label' => 'Carte mère',
                    'value' => 'Samsung
ATX, ITX, microATX, Mini-ITX',
                ],
            ],
        ],
        'Full Amoled HD Streaming Webcam with Mic Pink webcam' => [
            'category' => 'Webcams et streaming',
            'brand' => 'Asus',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 9,
            'sold_count' => 95,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-06-a-3.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-06-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'Tendance',
                    'variant' => 'yellow',
                ],
            ],
            'variants_count' => 0,
            'specifications' => [
                [
                    'label' => 'Marque',
                    'value' => 'Sony Corporation Ltd',
                ],
                [
                    'label' => 'Résolution',
                    'value' => '3840×2160',
                ],
                [
                    'label' => 'Année de sortie',
                    'value' => 'Jan 2022',
                ],
                [
                    'label' => 'Carte mère',
                    'value' => 'Samsung
ATX, ITX, microATX, Mini-ITX',
                ],
            ],
        ],
        'Samsung Quiet Comfort Noise Cancelling Earbuds - Black' => [
            'category' => 'Casque Bluetooth',
            'brand' => 'Samsung',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 97,
            'sold_count' => 97,
            'free_shipping' => true,
            'return_days' => 7,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-01-a-1.webp',
            'hover_video' => 'assets/videos/vedio-review-1.mp4',
            'badges' => [
                [
                    'label' => 'Top',
                    'variant' => 'danger',
                ],
                [
                    'label' => 'Meilleure vente',
                    'variant' => 'secondary-gradient',
                ],
            ],
            'colors' => [
                [
                    'name' => 'Noir',
                    'hex' => '#2B2B2B',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-01-a-1.webp',
                ],
                [
                    'name' => 'Rouge',
                    'hex' => '#a09fa4',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-01-a-2.webp',
                ],
                [
                    'name' => 'Rose',
                    'hex' => '#cc999d',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-01-a-3.webp',
                ],
            ],
            'variants_count' => 15,
        ],
        'Keurig K-Duo Bose Noise Cancelling Headphones 700' => [
            'category' => 'Casque Bluetooth',
            'brand' => 'Bose',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 97,
            'sold_count' => 97,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-04-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-04-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'Meilleure vente',
                    'variant' => 'secondary-gradient',
                ],
            ],
            'colors' => [
                [
                    'name' => 'Violet',
                    'hex' => '#bdb6d6',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-04-a-1.webp',
                ],
                [
                    'name' => 'Bleu',
                    'hex' => '#486788',
                    'image' => null,
                ],
                [
                    'name' => 'Noir',
                    'hex' => '#1a1a1a',
                    'image' => null,
                ],
            ],
            'variants_count' => 15,
        ],
        'GoPro HERO 11 4K Action Camera with SD Card' => [
            'category' => 'Caméra d’action',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 97,
            'sold_count' => 97,
            'free_shipping' => true,
            'return_days' => 7,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-08-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-08-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'Nouveau',
                    'variant' => 'green',
                ],
            ],
            'colors' => [
                [
                    'name' => 'Noir',
                    'hex' => '#202020',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-08-a-1.webp',
                ],
                [
                    'name' => 'Gris',
                    'hex' => '#9e9e9e',
                    'image' => null,
                ],
                [
                    'name' => 'Noir clair',
                    'hex' => '#171717',
                    'image' => null,
                ],
            ],
            'variants_count' => 15,
        ],
        'Samsung Galaxy N-569 Tab S7 with Stylish – 8GB/128GB' => [
            'category' => 'Tablettes',
            'brand' => 'Samsung',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 97,
            'sold_count' => 97,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-07-a-1.webp',
            'hover_image' => 'assets/images/product-img/electronics/electronics-bg-trans-07-a-1-hover.webp',
            'badges' => [
                [
                    'label' => 'Tendance',
                    'variant' => 'yellow',
                ],
            ],
            'colors' => [
                [
                    'name' => 'Gris',
                    'hex' => '#afb1b3',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-07-a-1.webp',
                ],
                [
                    'name' => 'Bleu ciel',
                    'hex' => '#7796b9',
                    'image' => null,
                ],
                [
                    'name' => 'Rose rouge',
                    'hex' => '#b84a5f',
                    'image' => null,
                ],
            ],
            'variants_count' => 15,
        ],
        'Beats Studio Pro Wireless Earbuds – Black' => [
            'category' => 'Casque Bluetooth',
            'price' => 42000,
            'compare_at_price' => 153000,
            'stock' => 25,
            'sold_count' => 20,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-01.webp',
            'variants_count' => 0,
        ],
        'Apple 12.9-inch iPad Pro Wi-Fi 512GB Gray Space' => [
            'category' => 'Tablettes',
            'price' => 15500,
            'compare_at_price' => 33500,
            'stock' => 25,
            'sold_count' => 20,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-02.webp',
            'variants_count' => 0,
        ],
        'DJI OM 5 Handheld Smartphone Gimbal' => [
            'category' => 'Accessoires photo',
            'price' => 42000,
            'compare_at_price' => 70000,
            'stock' => 25,
            'sold_count' => 20,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-03.webp',
            'variants_count' => 0,
        ],
        'Apple Watch Ultra 2 – Titanium Case' => [
            'category' => 'Écran tactile',
            'price' => 36000,
            'compare_at_price' => 58000,
            'stock' => 25,
            'sold_count' => 20,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-04.webp',
            'variants_count' => 0,
        ],
        'Apple MacBook Pro 16-inch – M2 Chip' => [
            'category' => 'Ordinateurs portables',
            'brand' => 'Intel',
            'price' => 42000,
            'compare_at_price' => 70000,
            'stock' => 25,
            'sold_count' => 20,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-05.webp',
            'variants_count' => 0,
        ],
        'Apple iPad Air 10.9-inch – Wi-Fi 256GB' => [
            'category' => 'Tablettes',
            'price' => 60000,
            'compare_at_price' => 131500,
            'stock' => 25,
            'sold_count' => 20,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-06.webp',
            'variants_count' => 0,
        ],
        'Samsung Galaxy Watch 4 Aluminum Smartwatch 44MM Bluetooth' => [
            'category' => 'Bluetooth',
            'brand' => 'Samsung',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 97,
            'sold_count' => 97,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-lg-01.webp',
            'badges' => [
                [
                    'label' => 'Nouveau',
                    'variant' => 'green',
                ],
            ],
            'colors' => [
                [
                    'name' => 'Orange',
                    'hex' => '#ed9951',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-lg-01.webp',
                ],
                [
                    'name' => 'Blanc',
                    'hex' => '#fffdfc',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-lg-03.webp',
                ],
                [
                    'name' => 'Bleu',
                    'hex' => '#4c5f7b',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-lg-04.webp',
                ],
                [
                    'name' => 'Blanc clair',
                    'hex' => '#f0f0f0',
                    'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-lg-02.webp',
                ],
            ],
            'variants_count' => 16,
            'sale_ends_in_days' => 87,
        ],
        '2021 Apple 12.9-inch iPad 512GB Gray Space' => [
            'category' => 'Tablettes',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 25,
            'sold_count' => 20,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-list-02.webp',
            'variants_count' => 0,
        ],
        'Nespresso Vertuo Plus Coffee Maker – Black' => [
            'category' => 'Cuisinière',
            'price' => 108000,
            'compare_at_price' => 177000,
            'stock' => 25,
            'sold_count' => 20,
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-02.webp',
            'variants_count' => 0,
        ],
    ],
    'collections' => [
        'deals-of-the-day' => [
            'name' => 'Offres du jour',
            'ends_in_days' => null,
            'products' => [
                'Ultra-Thin Modern Tech Quiet Noise Cancelling Laptop',
                'Keurig Polaroid 4K Waterproof Smart Action Camera',
                'Cubitt Smart Watch CTS Waterproof Fitness Tracker Watch PRO',
                'Apple IPhone 16 PRO max 6200U with 12GB RAM Phone',
                'Logitech Precision 9 Button Ergonomic Diital Wireless Mouse',
                'Keurig K-Duo 4K Waterproof Action Video Camera',
                'Cubitt Smart Wireless Apple 16 PRO Charging Case Set',
                'Full Amoled HD Streaming Webcam with Mic Pink webcam',
            ],
        ],
        'todays-best-deals' => [
            'name' => 'Les meilleures offres du jour',
            'ends_in_days' => 87,
            'products' => [
                'Samsung Quiet Comfort Noise Cancelling Earbuds - Black',
                'Keurig K-Duo Bose Noise Cancelling Headphones 700',
                'GoPro HERO 11 4K Action Camera with SD Card',
                'Samsung Galaxy N-569 Tab S7 with Stylish – 8GB/128GB',
            ],
        ],
        'weekly-highlights' => [
            'name' => 'Les incontournables de la semaine',
            'ends_in_days' => null,
            'products' => [
                'Beats Studio Pro Wireless Earbuds – Black',
                'Apple 12.9-inch iPad Pro Wi-Fi 512GB Gray Space',
                'DJI OM 5 Handheld Smartphone Gimbal',
                'Apple Watch Ultra 2 – Titanium Case',
                'Apple MacBook Pro 16-inch – M2 Chip',
                'Apple iPad Air 10.9-inch – Wi-Fi 256GB',
            ],
        ],
        'featured-products' => [
            'name' => 'Produits vedettes',
            'ends_in_days' => null,
            'products' => [
                'Samsung Galaxy Watch 4 Aluminum Smartwatch 44MM Bluetooth',
                '2021 Apple 12.9-inch iPad 512GB Gray Space',
                'Nespresso Vertuo Plus Coffee Maker – Black',
            ],
        ],
        'trending-searches' => [
            'name' => 'Produits tendance',
            'ends_in_days' => null,
            'products' => [
                'Samsung Quiet Comfort Noise Cancelling Earbuds - Black',
                'Keurig K-Duo Bose Noise Cancelling Headphones 700',
                'GoPro HERO 11 4K Action Camera with SD Card',
                'Samsung Galaxy N-569 Tab S7 with Stylish – 8GB/128GB',
            ],
        ],
    ],
    'promotions' => [
        [
            'title' => 'Méga fête du smartphone',
            'description' => 'Les smartphones des grandes marques à prix imbattables.',
            'image' => 'assets/images/offer-list/offer-card-image-1.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 29,
            'ends_in_days' => 50,
        ],
        [
            'title' => 'Fiesta des gadgets',
            'description' => 'Les derniers gadgets à prix cassés.',
            'image' => 'assets/images/offer-list/offer-card-image-2.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 0,
            'ends_in_days' => 19,
        ],
        [
            'title' => 'Festival high-tech',
            'description' => 'Équipez-vous avec des offres imbattables !',
            'image' => 'assets/images/offer-list/offer-card-image-3.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 3,
            'ends_in_days' => 19,
        ],
        [
            'title' => 'Carnaval de l’électronique',
            'description' => 'Le meilleur de l’électronique à prix électrisants.',
            'image' => 'assets/images/offer-list/offer-card-image-4.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 58,
            'ends_in_days' => 80,
        ],
        [
            'title' => 'Galaxie des gadgets',
            'description' => 'Des gadgets à des prix hors du commun !',
            'image' => 'assets/images/offer-list/offer-card-image-5.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 90,
            'ends_in_days' => 111,
        ],
        [
            'title' => 'Univers numérique',
            'description' => 'Plongez dans nos offres sur les gadgets incontournables !',
            'image' => 'assets/images/offer-list/offer-card-image-6.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 60,
            'ends_in_days' => 80,
        ],
        [
            'title' => 'Salon des technologies',
            'description' => 'Découvrez les technologies de demain !',
            'image' => 'assets/images/offer-list/offer-card-image-7.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 90,
            'ends_in_days' => 111,
        ],
        [
            'title' => 'Festival du mobile',
            'description' => 'Offres chocs sur les derniers smartphones, pour une durée limitée !',
            'image' => 'assets/images/offer-list/offer-card-image-8.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 1,
            'ends_in_days' => 49,
        ],
        [
            'title' => 'Méga fête du smartphone',
            'description' => 'Les smartphones du moment à prix imbattables !',
            'image' => 'assets/images/offer-list/offer-card-image-9.webp',
            'location_label' => 'Sur tout le site',
            'starts_in_days' => 59,
            'ends_in_days' => 111,
        ],
    ],
];
