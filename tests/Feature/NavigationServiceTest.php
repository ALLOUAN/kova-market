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
            ['label' => 'Home', 'route' => 'home'],
            ['label' => 'Blog', 'url' => 'https://blog.example.com'],
            ['label' => 'Not built yet', 'route' => 'pages.missing'],
            ['label' => 'Sell on :store'],
        ]);

        $this->assertSame(route('home'), $links[0]['href']);
        $this->assertSame('https://blog.example.com', $links[1]['href']);
        $this->assertSame('#', $links[2]['href']);
        $this->assertSame('#', $links[3]['href']);
        $this->assertSame('Sell on KOVA MARKET', $links[3]['label']);
    }
}
