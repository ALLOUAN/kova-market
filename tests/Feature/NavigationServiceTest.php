<?php

namespace Tests\Feature;

use App\Services\Storefront\NavigationService;
use Tests\TestCase;

class NavigationServiceTest extends TestCase
{
    public function test_links_resolve_named_routes_urls_and_placeholders(): void
    {
        config(['storefront.name' => 'KOVA MARKET']);

        $links = app(NavigationService::class)->links([
            ['label' => 'Accueil', 'route' => 'home'],
            ['label' => 'Blog', 'url' => 'https://blog.example.com'],
            ['label' => 'Not built yet', 'route' => 'pages.missing'],
            ['label' => 'Vendre sur :store'],
        ]);

        $this->assertSame(route('home'), $links[0]['href']);
        $this->assertSame('https://blog.example.com', $links[1]['href']);
        $this->assertSame('#', $links[2]['href']);
        $this->assertSame('#', $links[3]['href']);
        $this->assertSame('Vendre sur KOVA MARKET', $links[3]['label']);
    }

    public function test_every_menu_link_of_the_storefront_leads_to_an_existing_page(): void
    {
        $leaves = [];
        $walk = function (array $items) use (&$walk, &$leaves): void {
            foreach ($items as $key => $item) {
                // A badge ("SHOP", "PROMO") decorates a link; it is not one.
                if (! is_array($item) || $key === 'badge') {
                    continue;
                }

                if (isset($item['label']) && ! isset($item['type']) && ! isset($item['links']) && ! isset($item['columns'])) {
                    $leaves[] = $item;
                }

                $walk($item);
            }
        };
        $walk(config('navigation'));

        $links = app(NavigationService::class)->links($leaves);

        $this->assertNotEmpty($links);
        foreach ($links as $link) {
            $this->assertNotSame('#', $link['href'], "« {$link['label']} » ne mène nulle part.");
        }
    }
}
