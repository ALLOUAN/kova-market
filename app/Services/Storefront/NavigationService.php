<?php

namespace App\Services\Storefront;

use Illuminate\Support\Facades\Route;

/**
 * Turns the menus declared in config/navigation.php into render-ready links.
 */
class NavigationService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function mainMenu(): array
    {
        return once(fn () => array_map(function (array $item) {
            $item['href'] = $this->href($item);
            $item['columns'] = array_map(fn (array $column) => [
                'title' => $column['title'],
                'links' => $this->links($column['links']),
            ], $item['columns'] ?? []);
            $item['links'] = $this->links($item['links'] ?? []);

            return $item;
        }, config('navigation.main')));
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
        return array_map(fn (array $link) => [
            ...$link,
            'label' => str_replace(':store', config('storefront.name'), $link['label']),
            'href' => $this->href($link),
        ], $links);
    }

    /**
     * @param  array<string, mixed>  $link
     */
    private function href(array $link): string
    {
        if (isset($link['route']) && Route::has($link['route'])) {
            return route($link['route']);
        }

        return $link['url'] ?? '#';
    }
}
