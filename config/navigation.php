<?php

/*
|--------------------------------------------------------------------------
| Storefront navigation
|--------------------------------------------------------------------------
|
| Every link has a "route" (named route, "parameters" feeds its parameters)
| or a "url": a link to nowhere is not listed (a test checks it). Add a link
| here once its page exists. ":store" is replaced by the store name.
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
                        ['label' => 'Tous les produits', 'route' => 'shop.index', 'badge' => ['label' => 'SHOP', 'variant' => 'green']],
                        ['label' => 'Nouveautés', 'route' => 'shop.index', 'parameters' => ['tri' => 'nouveautes'], 'badge' => ['label' => 'Nouveau', 'variant' => 'yellow']],
                        ['label' => 'Les plus vendus', 'route' => 'shop.index', 'parameters' => ['tri' => 'popularite']],
                        ['label' => 'Nous trouver', 'route' => 'contact.show'],
                    ],
                ],
                [
                    'title' => 'Mon compte',
                    'links' => [
                        ['label' => 'Se connecter', 'route' => 'home', 'parameters' => ['connexion' => 1]],
                        ['label' => 'Créer un compte', 'route' => 'home', 'parameters' => ['inscription' => 1]],
                        ['label' => 'Mes informations', 'route' => 'account.show'],
                        ['label' => 'Mes commandes', 'route' => 'account.orders'],
                        ['label' => 'Mes adresses', 'route' => 'account.addresses.index'],
                        ['label' => 'Moyens de paiement', 'route' => 'pages.show', 'parameters' => ['page' => 'moyens-de-paiement']],
                    ],
                ],
                [
                    'title' => 'Commandes',
                    'links' => [
                        ['label' => 'Panier', 'route' => 'cart.show'],
                        ['label' => 'Commander', 'route' => 'checkout.show'],
                        ['label' => 'Suivre ma commande', 'route' => 'tracking.show'],
                        ['label' => 'Retours et remboursements', 'route' => 'pages.show', 'parameters' => ['page' => 'retours-et-remboursements'], 'badge' => ['label' => 'Nouveau', 'variant' => 'yellow']],
                    ],
                ],
                [
                    'title' => 'Aide',
                    'links' => [
                        ['label' => 'Questions fréquentes', 'route' => 'faq'],
                        ['label' => 'Nous contacter', 'route' => 'contact.show'],
                        ['label' => 'Politique de confidentialité', 'route' => 'pages.show', 'parameters' => ['page' => 'politique-de-confidentialite']],
                        ['label' => 'Conditions générales de vente', 'route' => 'pages.show', 'parameters' => ['page' => 'conditions-generales-de-vente']],
                    ],
                ],
                [
                    'title' => 'À propos',
                    'links' => [
                        ['label' => 'Qui sommes-nous ?', 'route' => 'pages.show', 'parameters' => ['page' => 'qui-sommes-nous']],
                        ['label' => 'Livraison', 'route' => 'pages.show', 'parameters' => ['page' => 'livraison']],
                        ['label' => 'Paiement', 'route' => 'pages.show', 'parameters' => ['page' => 'moyens-de-paiement']],
                    ],
                ],
            ],
        ],
        [
            'label' => 'Aide',
            'type' => 'dropdown',
            'links' => [
                ['label' => 'Questions fréquentes', 'route' => 'faq'],
                ['label' => 'Suivre ma commande', 'route' => 'tracking.show'],
                ['label' => 'Livraison', 'route' => 'pages.show', 'parameters' => ['page' => 'livraison']],
                ['label' => 'Retours et remboursements', 'route' => 'pages.show', 'parameters' => ['page' => 'retours-et-remboursements']],
                ['label' => 'Nous contacter', 'route' => 'contact.show', 'badge' => ['label' => 'WhatsApp', 'variant' => 'green']],
            ],
        ],
    ],

    'sidebar' => [
        'Liens utiles' => [
            ['label' => 'Qui sommes-nous ?', 'route' => 'pages.show', 'parameters' => ['page' => 'qui-sommes-nous']],
            ['label' => 'Livraison', 'route' => 'pages.show', 'parameters' => ['page' => 'livraison']],
            ['label' => 'Moyens de paiement', 'route' => 'pages.show', 'parameters' => ['page' => 'moyens-de-paiement']],
        ],
        'Plus d’infos' => [
            ['label' => 'Contact', 'route' => 'contact.show'],
            ['label' => 'Questions fréquentes', 'route' => 'faq'],
            ['label' => 'Conditions générales de vente', 'route' => 'pages.show', 'parameters' => ['page' => 'conditions-generales-de-vente']],
        ],
    ],

    'footer' => [
        'Besoin d’aide ?' => [
            ['label' => 'Mon compte', 'route' => 'account.show'],
            ['label' => 'Mes commandes', 'route' => 'account.orders'],
            ['label' => 'Suivre ma commande', 'route' => 'tracking.show'],
            ['label' => 'Livraison et tarifs', 'route' => 'pages.show', 'parameters' => ['page' => 'livraison']],
            ['label' => 'Retours et remboursements', 'route' => 'pages.show', 'parameters' => ['page' => 'retours-et-remboursements']],
            ['label' => 'Questions fréquentes', 'route' => 'faq'],
            ['label' => 'Nous contacter', 'route' => 'contact.show'],
        ],
        // Every footer link leads somewhere (F-017): a link without route or url is not shown.
        'Paiement et livraison' => [
            ['label' => 'Orange Money', 'route' => 'pages.show', 'parameters' => ['page' => 'moyens-de-paiement']],
            ['label' => 'MTN MoMo', 'route' => 'pages.show', 'parameters' => ['page' => 'moyens-de-paiement']],
            ['label' => 'Moov Money', 'route' => 'pages.show', 'parameters' => ['page' => 'moyens-de-paiement']],
            ['label' => 'Wave', 'route' => 'pages.show', 'parameters' => ['page' => 'moyens-de-paiement']],
            ['label' => 'Paiement à la livraison', 'route' => 'pages.show', 'parameters' => ['page' => 'moyens-de-paiement']],
            ['label' => 'Zones de livraison', 'route' => 'pages.show', 'parameters' => ['page' => 'livraison']],
        ],
        'À propos' => [
            ['label' => 'Qui sommes-nous ?', 'route' => 'pages.show', 'parameters' => ['page' => 'qui-sommes-nous']],
            ['label' => 'Professionnels et revendeurs', 'route' => 'contact.show', 'parameters' => ['sujet' => 'partenariat']],
            ['label' => 'Nous contacter', 'route' => 'contact.show'],
        ],
    ],

    'legal' => [
        ['label' => 'Retours et remboursements', 'route' => 'pages.show', 'parameters' => ['page' => 'retours-et-remboursements']],
        ['label' => 'Politique de confidentialité', 'route' => 'pages.show', 'parameters' => ['page' => 'politique-de-confidentialite']],
        ['label' => 'Conditions générales de vente', 'route' => 'pages.show', 'parameters' => ['page' => 'conditions-generales-de-vente']],
    ],

];
