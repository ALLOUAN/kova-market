<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Services\Storefront\ProductViewers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "N personnes ont vu ce produit": real visits of the product page, one per visitor, over the last 15 minutes, shown
 * from the threshold set in the back-office.
 */
class ProductViewersTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_product_page_counts_a_visit_but_not_for_search_engines(): void
    {
        $product = Product::factory()->create();

        // (The test client keeps no session cookie between requests: the same visitor is covered below.)
        $this->get($product->url())->assertOk();
        $this->assertSame(1, DB::table('product_viewers')->count());

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get($product->url())->assertOk();
        $this->assertSame(1, DB::table('product_viewers')->count());
    }

    public function test_the_badge_counts_distinct_recent_visitors_from_the_threshold(): void
    {
        $product = Product::factory()->create();

        // Two visitors, one of them twice: 2 people, below the default threshold of 3.
        $this->visit($product, 'a');
        $this->visit($product, 'a');
        $this->visit($product, 'b');
        $this->artisan('catalog:refresh-viewers')->assertSuccessful();
        $this->assertNull($product->fresh()->watchers_count);

        $this->visit($product, 'c');
        $this->artisan('catalog:refresh-viewers');
        $this->assertSame(3, $product->fresh()->watchers_count);
        $this->get(route('shop.index'))->assertSee('3 personnes ont vu ce produit ces 15 dernières minutes', false);

        // A quarter of an hour later the visits are forgotten, and the badge goes.
        $this->travel(16)->minutes();
        $this->artisan('catalog:refresh-viewers');
        $this->assertNull($product->fresh()->watchers_count);
        $this->assertSame(0, DB::table('product_viewers')->count());
    }

    public function test_the_back_office_sets_the_threshold_or_switches_the_badge_off(): void
    {
        $product = Product::factory()->create();
        $this->visit($product, 'a');
        $this->visit($product, 'b');

        Setting::store(['product_card.viewers_minimum' => 2]);
        $this->artisan('catalog:refresh-viewers');
        $this->assertSame(2, $product->fresh()->watchers_count);

        Setting::store(['product_card.viewers_enabled' => '0']);
        $this->artisan('catalog:refresh-viewers');
        $this->assertNull($product->fresh()->watchers_count);
    }

    /**
     * A visit of the product page by a visitor with their own session.
     */
    private function visit(Product $product, string $visitor): void
    {
        $request = Request::create($product->url());
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(10), str_repeat($visitor, 40)));

        app(ProductViewers::class)->record($product, $request);
    }
}
