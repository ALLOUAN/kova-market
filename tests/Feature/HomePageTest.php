<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Storefront\HomePageService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_the_seeded_demo_catalog(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSeeText('Deals of The Day')
            ->assertSeeText('Today’s best deals')
            ->assertSeeText('This Week’s Highlights')
            ->assertSeeText('Featured Products')
            ->assertSeeText('Ultra-Thin Modern Tech Quiet Noise Cancelling Laptop')
            ->assertSeeText('Smartphone Mega Fest')
            ->assertSee('assets/images/brands/brand-a-01.webp', false);
    }

    public function test_collection_products_are_listed_in_curated_order(): void
    {
        $collection = Collection::create(['name' => 'Deals of The Day', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $second = Product::factory()->create(['name' => 'Second Deal']);
        $first = Product::factory()->create(['name' => 'First Deal']);
        $collection->products()->attach([$second->id => ['position' => 2], $first->id => ['position' => 1]]);

        $this->get('/')->assertOk()->assertSeeTextInOrder(['First Deal', 'Second Deal']);
    }

    public function test_inactive_products_are_hidden(): void
    {
        $collection = Collection::create(['name' => 'Deals of The Day', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->create(['name' => 'Retired Gadget', 'is_active' => false]));

        $this->get('/')->assertOk()->assertDontSeeText('Retired Gadget');
    }

    public function test_sold_out_product_offers_a_restock_notification_instead_of_add_to_cart(): void
    {
        $collection = Collection::create(['name' => 'Deals of The Day', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->soldOut()->create());

        $this->get('/')
            ->assertOk()
            ->assertSee('rbt-stock-out-product-card', false)
            ->assertSeeText('Sold Out')
            ->assertSeeText('Notify Me');
    }

    public function test_discounted_product_shows_compare_price_and_rounded_discount(): void
    {
        $collection = Collection::create(['name' => 'Deals of The Day', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->onSale(price: 179.98, compareAt: 295)->create(['stock' => 12]));

        $this->get('/')
            ->assertOk()
            ->assertSee('<del class="price-text">$295.00</del>', false)
            ->assertSeeText('-39%')
            ->assertSeeText('12 in Stock');
    }

    public function test_low_stock_is_flagged_as_limited(): void
    {
        config(['storefront.product_card.limited_stock_threshold' => 3]);
        $collection = Collection::create(['name' => 'Deals of The Day', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->create(['stock' => 2]));

        $this->get('/')->assertOk()->assertSeeText('Limited Stock')->assertDontSeeText('2 in Stock');
    }

    public function test_expired_promotions_are_not_listed_in_special_offers(): void
    {
        Promotion::factory()->create(['title' => 'Running Offer']);
        Promotion::factory()->expired()->create(['title' => 'Finished Offer']);

        $this->get('/')->assertOk()->assertSeeText('Running Offer')->assertDontSeeText('Finished Offer');
    }

    public function test_category_tree_feeds_the_menus_and_featured_departments_the_home_grid(): void
    {
        $featured = Category::factory()->featured()->create(['name' => 'Cameras Department']);
        Category::factory()->create(['name' => 'Action Cams', 'parent_id' => $featured->id]);
        Category::factory()->create(['name' => 'Hidden Department']);

        $response = $this->get('/')->assertOk()->assertSeeText('Action Cams');

        $this->assertSame(1, substr_count($response->getContent(), 'rbt-cat-box-7'));
        $response->assertSeeText('Hidden Department');
    }

    public function test_brand_cards_count_only_active_products(): void
    {
        $brand = Brand::factory()->create();
        Product::factory()->count(2)->for($brand)->create();
        Product::factory()->for($brand)->create(['is_active' => false]);

        $this->get('/')->assertOk()->assertSee('<span class="prd-number">2</span>', false);
    }

    public function test_product_card_actions_follow_the_store_configuration(): void
    {
        config(['storefront.product_card.quick_view' => 'sidenav', 'storefront.product_card.cart_action' => 'popup']);
        $collection = Collection::create(['name' => 'Deals of The Day', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->create());

        $this->get('/')
            ->assertOk()
            ->assertSee('rbt-quickview-sidenav-activation', false)
            ->assertSee('rbt-quickview-sidenav-area', false)
            ->assertSee('data-bs-target="#popup-cartModal"', false);
    }
}
