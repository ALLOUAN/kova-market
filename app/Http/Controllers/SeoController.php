<?php

namespace App\Http\Controllers;

use App\Services\Storefront\SitemapGenerator;
use Illuminate\Http\Response;

/**
 * robots.txt and sitemap.xml (F-154). Only production opens the store to search engines: a preproduction copy asks
 * them to stay away. The back-office path is never listed (it is kept out of sight on purpose).
 */
class SeoController extends Controller
{
    /** Private or useless-to-index sections of the storefront. */
    private const DISALLOWED = [
        '/panier', '/commande', '/compte', '/suivi', '/mot-de-passe-oublie', '/reinitialiser-mot-de-passe',
        '/livreur', '/api/', '/*?*tri=', '/*?*prix_min=', '/*?*prix_max=',
    ];

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', ...array_map(fn (string $path) => "Disallow: {$path}", self::DISALLOWED), '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(SitemapGenerator $sitemap): Response
    {
        return response($sitemap->current(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
