<?php

namespace Tests\Feature\Api;

use App\Models\Brand;
use App\Models\Product;
use App\Services\Storefront\ProductListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_are_paginated_with_their_filter_choices(): void
    {
        $brand = Brand::factory()->create(['name' => 'JBL']);
        Product::factory()->count(ProductListing::PER_PAGE + 1)->for($brand)->create();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(ProductListing::PER_PAGE, 'data')
            ->assertJsonPath('meta.total', ProductListing::PER_PAGE + 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('facets.brands.0.name', 'JBL')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'price', 'in_stock', 'image']], 'links', 'facets' => ['categories', 'brands', 'attributes', 'price']]);

        $this->getJson('/api/v1/products?page=2')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_filters_and_sorting_use_the_storefront_parameters(): void
    {
        Product::factory()->create(['name' => 'Enceinte Bluetooth', 'price' => 30000]);
        Product::factory()->create(['name' => 'Enceinte Filaire', 'price' => 10000]);
        Product::factory()->create(['name' => 'Casque', 'price' => 20000]);

        $this->getJson('/api/v1/products?q=enceinte&tri=prix-croissant')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Enceinte Filaire');

        $this->getJson('/api/v1/products?prix_min=15000&prix_max=25000')->assertOk()->assertJsonPath('data.0.name', 'Casque')->assertJsonCount(1, 'data');
    }

    public function test_a_brand_lists_its_products(): void
    {
        $brand = Brand::factory()->create(['slug' => 'jbl']);
        Product::factory()->for($brand)->create(['name' => 'Flip 6']);
        Product::factory()->create(['name' => 'Autre']);

        $this->getJson('/api/v1/brands')->assertOk()->assertJsonPath('data.0.slug', 'jbl');
        $this->getJson('/api/v1/brands/jbl/products')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Flip 6');
    }

    public function test_an_inactive_product_does_not_exist(): void
    {
        Product::factory()->create(['slug' => 'retire', 'is_active' => false]);

        $this->getJson('/api/v1/products/retire')->assertNotFound();
        $this->getJson('/api/v1/products/inconnu')->assertNotFound();
    }

    public function test_the_openapi_contract_is_served(): void
    {
        $response = $this->get('/api/v1/openapi.yaml')->assertOk()->assertHeader('Content-Type', 'application/yaml');

        $this->assertStringStartsWith('openapi: 3.1.0', $response->baseResponse->getFile()->getContent());
    }

    public function test_requests_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/orders/track', ['number' => 'KM-000000-0000', 'phone' => '0701020304'])->assertNotFound();
        }

        $this->postJson('/api/v1/orders/track', ['number' => 'KM-000000-0000', 'phone' => '0701020304'])->assertTooManyRequests();
    }
}
