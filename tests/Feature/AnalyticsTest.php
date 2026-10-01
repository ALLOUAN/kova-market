<?php

namespace Tests\Feature;

use App\Enums\BannerPlacement;
use App\Enums\Role;
use App\Filament\Pages\Settings;
use App\Models\Banner;
use App\Models\Cart;
use App\Models\Collection;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart\CartManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const AD_DOMAINS = ['googletagmanager.com', 'google-analytics.com', 'connect.facebook.net', 'facebook.com/tr', 'analytics.tiktok.com'];

    public function test_without_any_tracker_the_banner_shows_by_default_and_nothing_is_tracked(): void
    {
        $response = $this->get('/')
            ->assertOk()
            ->assertSee('data-cookie-banner', false)
            ->assertSeeText('Nous respectons votre vie privée')
            ->assertSee('data-cookie-settings', false);

        foreach (self::AD_DOMAINS as $domain) {
            $response->assertDontSee($domain, false);
        }

        // Turned off in the back-office: no banner, no "Gérer les cookies", no script.
        Setting::store(['cookies.always' => '0']);
        $this->get('/')
            ->assertDontSee('data-cookie-banner', false)
            ->assertDontSee('assets/js/analytics.js', false)
            ->assertDontSee('data-cookie-settings', false);
    }

    public function test_the_banner_is_set_in_the_back_office_and_forced_while_a_tracker_is_set(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());

        Livewire::test(Settings::class)
            ->fillForm(['cookies.always' => false, 'cookies.title' => 'Des cookies ?', 'cookies.message' => 'Pour mesurer la fréquentation.', 'cookies.accept' => 'D’accord', 'cookies.decline' => 'Non merci'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/')->assertDontSee('data-cookie-banner', false);

        // A tracker makes consent mandatory: the banner comes back, with the texts chosen.
        $this->configureTrackers();
        $this->get('/')->assertSee('data-cookie-banner', false)
            ->assertSeeText('Des cookies ?')
            ->assertSeeText('Pour mesurer la fréquentation.')
            ->assertSeeText('D’accord')
            ->assertSeeText('Non merci');
    }

    public function test_without_consent_the_page_requests_nothing_from_advertising_domains(): void
    {
        $this->configureTrackers();

        $response = $this->get('/')->assertOk()
            ->assertSee('data-cookie-banner', false)
            ->assertSee('assets/js/analytics.js', false)
            ->assertSee('data-cookie-settings', false);

        // The identifiers are on the page; the tracker scripts are only injected by analytics.js after "Accepter".
        $this->assertSame(['G-TEST1234', '123456789012345', 'CTEST1234567890'], array_values(array_intersect_key($this->config($response), array_flip(['ga4', 'meta', 'tiktok']))));

        foreach (self::AD_DOMAINS as $domain) {
            $this->assertStringNotContainsString($domain, $response->getContent(), "{$domain} is referenced by the page before consent.");
        }

        $script = file_get_contents(public_path('assets/js/analytics.js'));
        $this->assertStringContainsString("consent === 'granted'", $script);
        $this->assertStringContainsString("hasAttribute('data-cookie-accept')", $script);
    }

    public function test_the_shopping_journey_reports_its_e_commerce_events_once(): void
    {
        $this->configureTrackers();
        $commune = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true])->communes()->create(['name' => 'Cocody']);
        $product = Product::factory()->create(['name' => 'Enceinte', 'slug' => 'enceinte', 'price' => 45000, 'stock' => 5]);
        $sku = $product->defaultVariant->sku;

        $viewItem = $this->events($this->get('/produit/enceinte'));
        $this->assertSame(['view_item'], array_column($viewItem, 'name'));
        $this->assertSame([$sku, 45000, 'XOF'], [$viewItem[0]['params']['items'][0]['item_id'], $viewItem[0]['params']['value'], $viewItem[0]['params']['currency']]);

        $this->from('/produit/enceinte')->post('/panier/articles', ['product_id' => $product->id, 'quantity' => 2]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
        $addToCart = collect($this->events($this->get('/panier')))->firstWhere('name', 'add_to_cart');
        $this->assertSame([2, 90000], [$addToCart['params']['items'][0]['quantity'], $addToCart['params']['value']]);
        $this->assertNotContains('add_to_cart', array_column($this->events($this->get('/panier')), 'name'));

        $this->assertSame(['begin_checkout'], array_column($this->events($this->get('/commande')), 'name'));

        $this->post('/commande', [
            'customer_name' => 'Koffi Yao', 'phone' => '0701020304', 'commune_id' => $commune->id,
            'district' => 'Riviera 2', 'payment_method' => 'paiement_livraison', 'terms' => '1',
        ]);
        $order = Order::sole();

        $purchase = collect($this->events($this->get("/commande/{$order->number}/merci")))->firstWhere('name', 'purchase');
        $this->assertSame([$order->number, 91500, 1500], [$purchase['params']['transaction_id'], $purchase['params']['value'], $purchase['params']['shipping']]);

        // Reloading the confirmation page does not count the sale twice.
        $this->assertNotContains('purchase', array_column($this->events($this->get("/commande/{$order->number}/merci")), 'name'));
    }

    public function test_the_search_console_token_is_published_without_consent(): void
    {
        Setting::store(['analytics.search_console_token' => 'abcDEF123_token']);

        $this->get('/')->assertSee('<meta name="google-site-verification" content="abcDEF123_token">', false);
    }

    public function test_the_identifiers_are_set_in_the_store_settings(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());

        Livewire::test(Settings::class)
            ->fillForm(['analytics.ga4_id' => 'UA-12345', 'analytics.meta_pixel_id' => 'abc'])
            ->call('save')
            ->assertHasFormErrors(['analytics.ga4_id', 'analytics.meta_pixel_id']);

        Livewire::test(Settings::class)
            ->fillForm(['analytics.ga4_id' => 'G-ABC123XYZ', 'analytics.tiktok_pixel_id' => 'CTEST1234567890'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['G-ABC123XYZ', 'CTEST1234567890'], [Setting::get('analytics.ga4_id'), Setting::get('analytics.tiktok_pixel_id')]);
    }

    public function test_the_home_page_reports_its_selections_and_banners_and_marks_them_for_clicks(): void
    {
        $this->configureTrackers();
        $selection = Collection::create(['name' => 'Offres du jour', 'slug' => 'deals-of-the-day']);
        $product = Product::factory()->create(['name' => 'Casque sans fil']);
        $selection->products()->attach($product->id, ['position' => 1]);
        $banner = Banner::create(['placement' => BannerPlacement::Hero, 'image' => 'assets/images/soldes.webp', 'highlight' => 'SOLDES', 'title' => 'D’ÉTÉ']);

        $response = $this->get('/');
        $events = collect($this->events($response));

        // One event per selection shown; the automatic rows ("Nouveautés", "Populaires") have theirs too.
        $list = $events->where('name', 'view_item_list')->firstWhere('params.item_list_id', 'deals-of-the-day')['params'];
        $this->assertEqualsCanonicalizing(['deals-of-the-day', 'new_arrivals', 'popular'], $events->where('name', 'view_item_list')->pluck('params.item_list_id')->all());
        $events = $events->keyBy('name');
        $this->assertSame('deals-of-the-day', $list['item_list_id']);
        $this->assertSame('Casque sans fil', $list['items'][0]['item_name']);
        $this->assertSame(0, $list['items'][0]['index']);
        $this->assertSame($product->defaultVariant->sku, $list['items'][0]['item_id']);

        $this->assertSame(['promotion_id' => 'banner-'.$banner->id, 'promotion_name' => 'SOLDES D’ÉTÉ', 'creative_name' => 'soldes.webp', 'creative_slot' => 'hero'], $events['view_promotion']['params']['items'][0]);

        // Marks read by analytics.js for the clicks.
        $response->assertSee('data-analytics-list=', false)->assertSee('data-analytics-item=', false)->assertSee('data-analytics-promotion=', false);
    }

    private function configureTrackers(): void
    {
        Setting::store([
            'analytics.ga4_id' => 'G-TEST1234',
            'analytics.meta_pixel_id' => '123456789012345',
            'analytics.tiktok_pixel_id' => 'CTEST1234567890',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(TestResponse $response): array
    {
        preg_match('#window\.kovaAnalytics = (.*?);</script>#s', $response->getContent(), $matches);

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<array{name: string, params: array<string, mixed>}>
     */
    private function events(TestResponse $response): array
    {
        return $this->config($response->assertOk())['events'];
    }
}
