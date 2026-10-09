<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Storefront\HomePageService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_the_seeded_demo_catalog(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSeeText('Offres du jour')
            ->assertSeeText('Les meilleures offres du jour')
            ->assertSeeText('Les incontournables de la semaine')
            ->assertSeeText('Produits vedettes')
            ->assertSeeText('Ultra-Thin Modern Tech Quiet Noise Cancelling Laptop')
            ->assertSeeText('Méga fête du smartphone')
            ->assertSee('assets/images/brands/brand-a-01.webp', false);
    }

    public function test_collection_products_are_listed_in_curated_order(): void
    {
        $collection = Collection::create(['name' => 'Offres du jour', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $second = Product::factory()->create(['name' => 'Second Deal']);
        $first = Product::factory()->create(['name' => 'First Deal']);
        $collection->products()->attach([$second->id => ['position' => 2], $first->id => ['position' => 1]]);

        $this->get('/')->assertOk()->assertSeeTextInOrder(['First Deal', 'Second Deal']);
    }

    public function test_new_arrivals_list_the_latest_products_and_popular_the_best_sellers(): void
    {
        $this->travelTo(now()->subDays(3));
        $bestSeller = Product::factory()->create(['name' => 'Best Seller', 'sold_count' => 50]);
        $this->travelBack();
        Product::factory()->create(['name' => 'Steady Seller', 'sold_count' => 10]);
        $retired = Product::factory()->create(['name' => 'Retired Gadget', 'sold_count' => 99]);
        $retired->update(['is_active' => false]);
        Product::factory()->create(['name' => 'Brand New Gadget']);

        $data = app(HomePageService::class)->data();

        $this->assertSame('Brand New Gadget', $data['newArrivals']['products']->first()->name);
        $this->assertSame(['Best Seller', 'Steady Seller'], $data['popular']['products']->take(2)->pluck('name')->all());
        $this->assertNotContains('Retired Gadget', $data['newArrivals']['products']->pluck('name'));
        $this->assertNotContains('Retired Gadget', $data['popular']['products']->pluck('name'));

        $this->get('/')->assertOk()->assertSeeText('Nouveautés')->assertSeeText('Populaires')
            ->assertSee(route('shop.index', ['tri' => 'nouveautes']), false)
            ->assertSeeText($bestSeller->name);
    }

    public function test_a_curated_collection_takes_precedence_over_the_computed_row(): void
    {
        $picked = Product::factory()->create(['name' => 'Hand Picked']);
        Product::factory()->create(['name' => 'Newest Of All']);
        Collection::where('slug', HomePageService::NEW_ARRIVALS)->sole()->products()->attach($picked);

        $this->assertSame(['Hand Picked'], app(HomePageService::class)->data()['newArrivals']['products']->pluck('name')->all());
    }

    public function test_the_footer_has_no_empty_link_and_lists_the_payment_methods(): void
    {
        $this->seed(ContentSeeder::class);

        $content = $this->get('/')->assertOk()->getContent();
        $footer = Str::between($content, '<footer', '<div class="rbt-toolbar');

        $this->assertStringNotContainsString('href="#"', $footer);
        foreach (['Orange Money', 'MTN MoMo', 'Moov Money', 'Wave', 'Paiement à la livraison'] as $method) {
            $this->assertStringContainsString($method, $footer);
        }
        // App store buttons wait for the mobile app; wishlist and comparison wait for V1.1.
        $this->assertStringNotContainsString('Téléchargez l’app', $content);
        $this->assertStringNotContainsString('wishlistModal', $content);
        $this->assertStringNotContainsString('compareviewModal', $content);

        $this->get(route('pages.show', 'moyens-de-paiement'))->assertOk();
        $this->get(route('pages.show', 'qui-sommes-nous'))->assertOk();
        $this->get(route('contact.show', ['sujet' => 'partenariat']))->assertOk()->assertSee('value="partenariat" selected', false);
    }

    public function test_inactive_products_are_hidden(): void
    {
        $collection = Collection::create(['name' => 'Offres du jour', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->create(['name' => 'Retired Gadget', 'is_active' => false]));

        $this->get('/')->assertOk()->assertDontSeeText('Retired Gadget');
    }

    public function test_sold_out_product_offers_a_restock_notification_instead_of_add_to_cart(): void
    {
        $collection = Collection::create(['name' => 'Offres du jour', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->soldOut()->create());

        $this->get('/')
            ->assertOk()
            ->assertSee('rbt-stock-out-product-card', false)
            ->assertSeeText('Épuisé')
            ->assertSeeText('Me prévenir');
    }

    public function test_discounted_product_shows_compare_price_and_rounded_discount(): void
    {
        $collection = Collection::create(['name' => 'Offres du jour', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->onSale(price: 108000, compareAt: 177000)->create(['stock' => 12]));

        $this->get('/')
            ->assertOk()
            ->assertSee("<del class=\"price-text\">177\u{00A0}000\u{00A0}FCFA</del>", false)
            ->assertSeeText('-39%')
            ->assertSeeText('12 en stock');
    }

    public function test_low_stock_is_flagged_as_limited(): void
    {
        config(['storefront.product_card.limited_stock_threshold' => 3]);
        $collection = Collection::create(['name' => 'Offres du jour', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $collection->products()->attach(Product::factory()->create(['stock' => 2]));

        $this->get('/')->assertOk()->assertSeeText('Stock limité')->assertDontSeeText('2 en stock');
    }

    public function test_best_deals_countdown_runs_while_the_collection_has_not_ended(): void
    {
        $collection = Collection::create(['name' => 'Les meilleures offres du jour', 'slug' => HomePageService::BEST_DEALS, 'ends_at' => now()->addDays(2)]);
        $collection->products()->attach(Product::factory()->create());

        $this->get('/')->assertOk()->assertSeeText('Vite ! L’offre se termine dans');
    }

    public function test_best_deals_countdown_is_hidden_once_the_collection_has_ended(): void
    {
        $collection = Collection::create(['name' => 'Les meilleures offres du jour', 'slug' => HomePageService::BEST_DEALS, 'ends_at' => now()->subMinute()]);
        $collection->products()->attach(Product::factory()->create());

        $this->get('/')->assertOk()->assertDontSeeText('Vite ! L’offre se termine dans');
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
        Product::factory()->for(Category::factory()->create(['name' => 'Action Cams', 'parent_id' => $featured->id]))->create();
        Product::factory()->for(Category::factory()->create(['name' => 'Hidden Department']))->create();

        $response = $this->get('/')->assertOk()->assertSeeText('Action Cams');

        $this->assertSame(1, substr_count($response->getContent(), 'rbt-cat-box-7'));
        $response->assertSeeText('Hidden Department');
    }

    public function test_menus_leave_out_the_categories_without_online_products(): void
    {
        $department = Category::factory()->featured()->create(['name' => 'Rayon Audio']);
        Product::factory()->for(Category::factory()->create(['name' => 'Casques', 'parent_id' => $department->id]))->create();
        Category::factory()->create(['name' => 'Enceintes vides', 'parent_id' => $department->id]);
        Category::factory()->featured()->create(['name' => 'Rayon vide']);
        Product::factory()->for(Category::factory()->create(['name' => 'Rayon hors ligne']))->create(['is_active' => false]);

        $this->get('/')->assertOk()
            ->assertSeeText('Rayon Audio')
            ->assertSeeText('Casques')
            ->assertDontSeeText('Enceintes vides')
            ->assertDontSeeText('Rayon vide')
            ->assertDontSeeText('Rayon hors ligne');

        // An empty category still opens by its address.
        $this->get(route('categories.show', Category::where('name', 'Rayon vide')->first()))->assertOk();
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
        config(['storefront.product_card.quick_view' => 'sidenav', 'storefront.product_card.cart_action' => 'sidenav']);
        $collection = Collection::create(['name' => 'Offres du jour', 'slug' => HomePageService::DEALS_OF_THE_DAY]);
        $product = Product::factory()->create();
        $collection->products()->attach($product);

        $this->get('/')
            ->assertOk()
            ->assertSee('rbt-quickview-sidenav-activation', false)
            ->assertSee('rbt-quickview-sidenav-area', false)
            ->assertSee('<input type="hidden" name="product_id" value="'.$product->id.'">', false)
            ->assertSee('<input type="hidden" name="open" value="sidenav">', false);

        // After adding from a card, the next page slides the mini-cart open.
        $this->post('/panier/articles', ['product_id' => $product->id, 'open' => 'sidenav']);
        $this->get('/')->assertSee("classList.add('side-menu-active')", false);
    }

    public function test_currency_and_language_switchers_are_hidden_with_a_single_option(): void
    {
        $this->get('/')->assertOk()->assertDontSee('currency-menu', false)->assertDontSee('switcher-language', false);

        config(['storefront.currencies' => [['code' => 'XOF', 'label' => 'FCFA'], ['code' => 'EUR', 'label' => 'EUR']]]);

        $this->get('/')->assertOk()->assertSee('currency-menu', false);
    }

    public function test_social_networks_without_a_profile_url_are_hidden(): void
    {
        config(['storefront.social' => [
            ['key' => 'facebook', 'icon' => 'fa-facebook-f', 'url' => 'https://facebook.com/kovamarket'],
            ['key' => 'tiktok', 'icon' => 'fa-tiktok', 'url' => '#'],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://facebook.com/kovamarket', false)
            ->assertDontSee('aria-label="fa-tiktok"', false);
    }
}
