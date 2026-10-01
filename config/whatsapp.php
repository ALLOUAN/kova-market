<?php

/*
|--------------------------------------------------------------------------
| WhatsApp Business messages (Meta Cloud API)
|--------------------------------------------------------------------------
|
| Messages a business sends first must use a template approved by Meta. Each entry below is a template to create in
| WhatsApp Manager (Meta Business Suite) with exactly this name, category, language and body; {{1}}, {{2}}… are
| filled by the notifications in this order. Meta refuses a body that starts or ends with a variable, hence the
| fixed endings. Changing a text here means submitting the template again.
|
*/

return [

    'language' => env('WHATSAPP_LANGUAGE', 'fr'),

    'templates' => [

        'order_placed' => [
            'name' => 'kova_commande_recue',
            'category' => 'UTILITY',
            'body' => 'Bonjour {{1}}, nous avons bien reçu votre commande {{2}} d’un montant de {{3}}. Nous vous contactons pour organiser la livraison. Votre reçu : {{4}} Merci, l’équipe KOVA MARKET.',
            'example' => ['Awa', 'KM-261001-0001', '45 000 FCFA', 'https://kovamarket.ci/commande/KM-261001-0001/recu'],
        ],

        'order_status' => [
            'name' => 'kova_suivi_commande',
            'category' => 'UTILITY',
            'body' => 'Bonjour {{1}}, votre commande {{2}} : {{3}}. Plus de détails : {{4}} Merci, l’équipe KOVA MARKET.',
            'example' => ['Awa', 'KM-261001-0001', 'le livreur est en route, gardez votre téléphone à portée de main', 'https://kovamarket.ci/suivi'],
        ],

        'delivery_date' => [
            'name' => 'kova_date_livraison',
            'category' => 'UTILITY',
            'body' => 'Bonjour {{1}}, votre commande {{2}} sera livrée le {{3}}. Le livreur vous appellera à son arrivée. Merci, l’équipe KOVA MARKET.',
            'example' => ['Awa', 'KM-261001-0001', 'jeudi 2 octobre'],
        ],

        'weigh_in' => [
            'name' => 'kova_pesee',
            'category' => 'UTILITY',
            'body' => 'Bonjour {{1}}, vos produits vendus au poids de la commande {{2}} ont été pesés. Nouveau total : {{3}} au lieu de {{4}}, à régler à la livraison. Votre reçu : {{5}} Merci, l’équipe KOVA MARKET.',
            'example' => ['Awa', 'KM-261001-0001', '2 120 FCFA', '2 000 FCFA', 'https://kovamarket.ci/commande/KM-261001-0001/recu'],
        ],

        'back_in_stock' => [
            'name' => 'kova_retour_stock',
            'category' => 'UTILITY',
            'body' => 'Bonne nouvelle : {{1}} est de nouveau disponible. Commandez-le ici : {{2}} À bientôt sur KOVA MARKET.',
            'example' => ['Enceinte JBL Flip 6', 'https://kovamarket.ci/produit/enceinte-jbl-flip-6'],
        ],

        'courier_credentials' => [
            'name' => 'kova_livreur_acces',
            'category' => 'UTILITY',
            'body' => 'Votre accès livreur KOVA MARKET : identifiant {{1}}, mot de passe provisoire {{2}}. Connectez-vous sur {{3}} puis choisissez votre propre mot de passe.',
            'example' => ['07 01 02 03 04', 'Kx7mP2qa', 'https://kovamarket.ci/livreur/connexion'],
        ],

        'courier_delivery' => [
            'name' => 'kova_livreur_livraison',
            'category' => 'UTILITY',
            'body' => 'Livraison {{1}} à {{2}} : {{3}}. Détails dans votre application : {{4}} Merci.',
            'example' => ['KM-261001-0001', 'Cocody, Riviera 2', 'elle vous est confiée', 'https://kovamarket.ci/livreur'],
        ],

        // Sent to the former number when the password, e-mail or phone of an account changes (F-147).
        'security_alert' => [
            'name' => 'kova_securite_compte',
            'category' => 'UTILITY',
            'body' => 'Bonjour {{1}}, votre compte KOVA MARKET a été modifié le {{2}} : {{3}}. Si vous n’êtes pas à l’origine de ce changement, contactez-nous tout de suite : {{4}} Merci, l’équipe KOVA MARKET.',
            'example' => ['Awa', '1 octobre 2026 à 14:32', 'nouveau mot de passe', 'https://kovamarket.ci/contact'],
        ],

        // Authentication category: Meta fixes the text ("{{1}} est votre code de vérification.") and adds a
        // "Copier le code" button that receives the code too.
        'verification_code' => [
            'name' => 'kova_code',
            'category' => 'AUTHENTICATION',
            'body' => '{{1}} est votre code de vérification.',
            'example' => ['482913'],
            'copy_code_button' => true,
        ],
    ],
];
