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
| The "Boutique" mega menu, the mobile "Catégories" tab and the category side
| panel are generated from the category tree stored in the database.
|
*/

return [

    'main' => [
        ['label' => 'Accueil', 'route' => 'home'],
        ['label' => 'Boutique', 'type' => 'categories'],
        [
            'label' => 'Pages',
            'type' => 'mega',
            'columns' => [
                [
                    'title' => 'Boutique',
                    'links' => [
                        ['label' => 'Tous les produits', 'badge' => ['label' => 'SHOP', 'variant' => 'green']],
                        ['label' => 'Toutes les catégories'],
                        ['label' => 'Par marque'],
                        ['label' => 'Offres spéciales', 'badge' => ['label' => 'PROMO', 'variant' => 'danger']],
                        ['label' => 'Comparer des produits'],
                        ['label' => 'Nous trouver'],
                    ],
                ],
                [
                    'title' => 'Mon compte',
                    'links' => [
                        ['label' => 'Se connecter'],
                        ['label' => 'Créer un compte'],
                        ['label' => 'Mes informations'],
                        ['label' => 'Mes commandes'],
                        ['label' => 'Ma liste de souhaits'],
                        ['label' => 'Moyens de paiement'],
                        ['label' => 'Notifications'],
                    ],
                ],
                [
                    'title' => 'Commandes',
                    'links' => [
                        ['label' => 'Panier'],
                        ['label' => 'Commander'],
                        ['label' => 'Suivre ma commande'],
                        ['label' => 'Retours et remboursements', 'badge' => ['label' => 'Nouveau', 'variant' => 'yellow']],
                    ],
                ],
                [
                    'title' => 'Aide',
                    'links' => [
                        ['label' => 'Centre d’aide'],
                        ['label' => 'Questions fréquentes'],
                        ['label' => 'Nous contacter'],
                        ['label' => 'Politique de confidentialité'],
                        ['label' => 'Conditions générales de vente'],
                    ],
                ],
                [
                    'title' => 'À propos',
                    'links' => [
                        ['label' => 'Qui sommes-nous ?'],
                        ['label' => 'Livraison'],
                        ['label' => 'Paiement'],
                    ],
                ],
            ],
        ],
        [
            'label' => 'Aide',
            'type' => 'dropdown',
            'links' => [
                ['label' => 'Centre d’aide'],
                ['label' => 'Questions fréquentes'],
                ['label' => 'Livraison'],
                ['label' => 'Retours et remboursements'],
                ['label' => 'Nous contacter', 'badge' => ['label' => 'WhatsApp', 'variant' => 'green']],
            ],
        ],
    ],

    'sidebar' => [
        'Liens utiles' => [
            ['label' => 'Qui sommes-nous ?'],
            ['label' => 'Avis clients'],
            ['label' => 'Livraison et paiement'],
        ],
        'Plus d’infos' => [
            ['label' => 'Contact'],
            ['label' => 'Questions fréquentes'],
            ['label' => 'Conditions générales de vente'],
        ],
    ],

    'footer' => [
        'Besoin d’aide ?' => [
            ['label' => 'Mon compte'],
            ['label' => 'Mes commandes'],
            ['label' => 'Suivre ma commande'],
            ['label' => 'Livraison et tarifs'],
            ['label' => 'Retours et remboursements'],
            ['label' => 'Questions fréquentes'],
            ['label' => 'Nous contacter'],
        ],
        'Paiement et livraison' => [
            ['label' => 'Orange Money'],
            ['label' => 'MTN MoMo'],
            ['label' => 'Moov Money'],
            ['label' => 'Wave'],
            ['label' => 'Paiement à la livraison'],
            ['label' => 'Zones de livraison'],
        ],
        'À propos' => [
            ['label' => 'Qui sommes-nous ?'],
            ['label' => 'Avis clients'],
            ['label' => 'Professionnels et revendeurs'],
            ['label' => 'Gestion des cookies'],
        ],
    ],

    'legal' => [
        ['label' => 'Retours et remboursements'],
        ['label' => 'Politique de confidentialité'],
        ['label' => 'Conditions générales de vente'],
    ],

];
