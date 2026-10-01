<?php

namespace App\Services\Storefront;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

/**
 * Turns the menus declared in config/navigation.php into render-ready links, each flagged "active" when it leads
 * to the page being shown (the menus, mega menus and footer highlight it).
 */
class NavigationService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function mainMenu(): array
    {
        return once(fn () => $this->oneActive(array_map(function (array $item) {
            $item['href'] = $this->href($item);
            $item['columns'] = array_map(fn (array $column) => [
                'title' => $column['title'],
                'links' => $this->links($column['links']),
            ], $item['columns'] ?? []);
            $item['links'] = $this->links($item['links'] ?? []);
            $item['active'] = $this->itemIsActive($item);

            return $item;
        }, $this->withMarket(config('navigation.main')))));
    }

    /**
     * One lit item in the bar: the "Pages" mega menu repeats links found elsewhere (FAQ, contact…), so it only lights
     * up when no other item does.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function oneActive(array $items): array
    {
        $other = collect($items)->contains(fn (array $item) => $item['active'] && ($item['type'] ?? 'link') !== 'mega');

        return array_map(fn (array $item) => ($item['type'] ?? 'link') === 'mega' && $other ? [...$item, 'active' => false] : $item, $items);
    }

    /**
     * "Mon Marché" right after the "Boutique" menu, highlighted, while the showcase is on and shown in the menu.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function withMarket(array $items): array
    {
        $market = app(Market::class);

        if (! $market->showInMenu()) {
            return $items;
        }

        // Like "Boutique": a chevron opening its categories and their sub-categories.
        $link = ['label' => $market->title(), 'type' => 'market', 'route' => 'market.show', 'icon' => 'fa-regular fa-basket-shopping', 'highlight' => true];
        $after = collect($items)->search(fn (array $item) => ($item['type'] ?? null) === 'categories');
        array_splice($items, $after === false ? 1 : $after + 1, 0, [$link]);

        return $items;
    }

    /**
     * Link groups keyed by title (e.g. "footer", "sidebar").
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function groups(string $menu): array
    {
        return array_map(fn (array $links) => $this->links($links), config("navigation.$menu"));
    }

    /**
     * @param  list<array<string, mixed>>  $links
     * @return list<array<string, mixed>>
     */
    public function links(array $links): array
    {
        $links = array_map(function (array $link) {
            $href = $this->href($link);

            return [
                ...$link,
                'label' => str_replace(':store', config('storefront.name'), $link['label']),
                'href' => $href,
                'match' => $this->match($href),
            ];
        }, $links);

        // "Tous les produits" and "Nouveautés" both lead to /boutique: only the most precise link is active, and only
        // once when several lead to the same page (the footer's payment means).
        $scores = array_column($links, 'match');
        $active = max([0, ...$scores]) > 0 ? array_search(max($scores), $scores, true) : null;

        return array_map(function (array $link, int $index) use ($active) {
            unset($link['match']);

            return [...$link, 'active' => $index === $active];
        }, $links, array_keys($links));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function itemIsActive(array $item): bool
    {
        return match ($item['type'] ?? 'link') {
            'categories' => $this->inCatalog() && ! $this->inMarket(),
            'market' => request()->routeIs('market.show') || $this->inMarket(),
            'mega' => collect($item['columns'])->flatMap(fn (array $column) => $column['links'])->contains('active', true),
            'dropdown' => collect($item['links'])->contains('active', true),
            default => $this->match($item['href']) > 0,
        };
    }

    private function inCatalog(): bool
    {
        return request()->routeIs('shop.index', 'categories.show', 'brands.show', 'collections.show', 'products.show');
    }

    /**
     * A category or a product of the "Mon Marché" department.
     */
    private function inMarket(): bool
    {
        $category = request()->route('category');
        $product = request()->route('product');
        $category = $product instanceof Product ? $product->category : $category;

        return $category instanceof Category && app(Market::class)->contains($category);
    }

    /**
     * How well a link leads to the current page: 0 when it does not, otherwise 1 plus the number of its query
     * parameters the current address carries too (a link asking for a parameter the page lacks does not match).
     */
    private function match(string $href): int
    {
        if ($href === '' || $href === '#' || str_starts_with($href, '#')) {
            return 0;
        }

        $path = fn (string $url) => '/'.trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($path($href) !== $path(request()->url())) {
            return 0;
        }

        parse_str((string) parse_url($href, PHP_URL_QUERY), $wanted);

        foreach ($wanted as $key => $value) {
            if ((string) request()->query($key) !== (string) $value) {
                return 0;
            }
        }

        return 1 + count($wanted);
    }

    /**
     * @param  array<string, mixed>  $link
     */
    private function href(array $link): string
    {
        if (isset($link['route']) && Route::has($link['route'])) {
            return route($link['route'], $link['parameters'] ?? []);
        }

        return $link['url'] ?? '#';
    }
}
