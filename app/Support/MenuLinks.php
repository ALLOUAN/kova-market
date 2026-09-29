<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;

/**
 * Links of the menus edited in the back-office (Contenus › Menus, F-111): the destinations offered in a list
 * (pages of the site, content pages, categories, brands, or an address typed by hand), and the translation between
 * the form's item (label + destination key) and the link shape of config/navigation.php (route + parameters, or url).
 */
class MenuLinks
{
    public const CUSTOM = 'url';

    /** Pages of the site: destination key => label. A "|" carries query parameters. */
    private const ROUTES = [
        'r:home' => 'Accueil',
        'r:shop.index' => 'Boutique : tous les produits',
        'r:shop.index|tri=nouveautes' => 'Boutique : nouveautés',
        'r:shop.index|tri=popularite' => 'Boutique : les plus vendus',
        'r:cart.show' => 'Panier',
        'r:checkout.show' => 'Commander',
        'r:tracking.show' => 'Suivre ma commande',
        'r:faq' => 'Questions fréquentes',
        'r:contact.show' => 'Nous contacter',
        'r:contact.show|sujet=partenariat' => 'Nous contacter : professionnels et revendeurs',
        'r:account.show' => 'Mon compte',
        'r:account.orders' => 'Mes commandes',
        'r:account.addresses.index' => 'Mes adresses',
        'r:home|connexion=1' => 'Se connecter',
        'r:home|inscription=1' => 'Créer un compte',
        'r:password.request' => 'Mot de passe oublié',
    ];

    /**
     * Destinations grouped for a select: pages of the site, content pages, categories, brands, custom address.
     *
     * @return array<string, array<string, string>>
     */
    public static function destinations(): array
    {
        return [
            'Pages du site' => self::ROUTES,
            'Pages de contenu' => Page::query()->orderBy('title')->pluck('title', 'slug')->mapWithKeys(fn ($title, $slug) => ["p:{$slug}" => $title])->all(),
            'Catégories' => Category::query()->orderBy('name')->pluck('name', 'slug')->mapWithKeys(fn ($name, $slug) => ["c:{$slug}" => $name])->all(),
            'Marques' => Brand::query()->orderBy('name')->pluck('name', 'slug')->mapWithKeys(fn ($name, $slug) => ["b:{$slug}" => $name])->all(),
            'Autre' => [self::CUSTOM => 'Adresse personnalisée…'],
        ];
    }

    /**
     * The form item of a config link.
     *
     * @param  array<string, mixed>  $link
     * @return array{label: string, target: string, url: ?string, badge_label: ?string, badge_variant: ?string}
     */
    public static function toForm(array $link): array
    {
        $parameters = $link['parameters'] ?? [];

        $target = match (true) {
            isset($link['url']) => self::CUSTOM,
            ($link['route'] ?? null) === 'pages.show' => 'p:'.($parameters['page'] ?? ''),
            ($link['route'] ?? null) === 'categories.show' => 'c:'.($parameters['category'] ?? ''),
            ($link['route'] ?? null) === 'brands.show' => 'b:'.($parameters['brand'] ?? ''),
            isset($link['route']) => 'r:'.$link['route'].($parameters ? '|'.http_build_query($parameters) : ''),
            default => self::CUSTOM,
        };

        return [
            'label' => (string) ($link['label'] ?? ''),
            'target' => $target,
            'url' => $link['url'] ?? null,
            'badge_label' => $link['badge']['label'] ?? null,
            'badge_variant' => $link['badge']['variant'] ?? null,
        ];
    }

    /**
     * The config link of a form item.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function toConfig(array $item): array
    {
        $target = (string) ($item['target'] ?? self::CUSTOM);
        [$kind, $value] = array_pad(explode(':', $target, 2), 2, '');

        $link = match ($kind) {
            'p' => ['route' => 'pages.show', 'parameters' => ['page' => $value]],
            'c' => ['route' => 'categories.show', 'parameters' => ['category' => $value]],
            'b' => ['route' => 'brands.show', 'parameters' => ['brand' => $value]],
            'r' => self::route($value),
            default => ['url' => (string) ($item['url'] ?? '')],
        };

        $badge = filled($item['badge_label'] ?? null)
            ? ['badge' => ['label' => $item['badge_label'], 'variant' => $item['badge_variant'] ?: 'green']]
            : [];

        return ['label' => trim((string) ($item['label'] ?? '')), ...$link, ...$badge];
    }

    /**
     * @return array{route: string, parameters?: array<string, string>}
     */
    private static function route(string $value): array
    {
        [$route, $query] = array_pad(explode('|', $value, 2), 2, '');
        parse_str($query, $parameters);

        return $parameters === [] ? ['route' => $route] : ['route' => $route, 'parameters' => $parameters];
    }
}
