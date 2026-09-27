<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Reference content linked from the menus and footer: the legal pages (placeholder text to be replaced
 * by the client's validated texts) and a starter FAQ limited to facts of the specification.
 * Safe to run in production and to re-run: existing pages and questions are left untouched.
 */
class ContentSeeder extends Seeder
{
    private const PLACEHOLDER = '<p>Cette page est en cours de rédaction. Pour toute question, contactez notre service client.</p>';

    public function run(): void
    {
        $pages = [
            'conditions-generales-de-vente' => 'Conditions générales de vente',
            'politique-de-confidentialite' => 'Politique de confidentialité',
            'livraison' => 'Livraison',
            'retours-et-remboursements' => 'Retours et remboursements',
        ];

        foreach ($pages as $slug => $title) {
            Page::firstOrCreate(['slug' => $slug], ['title' => $title, 'content' => self::PLACEHOLDER]);
        }

        $faqs = [
            ['Commande', 'Dois-je créer un compte pour commander ?', 'Non. Vous pouvez commander en tant qu’invité avec votre nom, votre numéro de téléphone et votre adresse de livraison. Un compte vous permet en plus d’enregistrer vos adresses et de retrouver vos commandes.'],
            ['Commande', 'Comment suivre ma commande ?', 'Avec votre numéro de commande (par exemple KM-260927-0042) et votre numéro de téléphone, depuis la page « Suivre ma commande ». Vous recevez aussi un SMS à chaque étape.'],
            ['Paiement', 'Quels moyens de paiement acceptez-vous ?', 'Orange Money, MTN MoMo, Moov Money et Wave, ainsi que le paiement à la livraison.'],
            ['Livraison', 'Combien coûte la livraison ?', 'Les frais dépendent de votre commune de livraison. Ils sont calculés automatiquement dans le panier dès que vous choisissez votre commune.'],
        ];

        foreach ($faqs as $position => [$topic, $question, $answer]) {
            Faq::firstOrCreate(['question' => $question], ['topic' => $topic, 'answer' => $answer, 'position' => $position]);
        }
    }
}
