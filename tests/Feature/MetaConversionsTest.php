<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Meta pixel events sent at once from background answers, and their copies sent by the server (Conversions API)
 * with the same id, for visitors who accepted the cookies only.
 */
class MetaConversionsTest extends TestCase
{
    use RefreshDatabase;

    private const PIXEL = '123456789012345';

    private Product $product;

    private Commune $commune;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::store(['analytics.meta_pixel_id' => self::PIXEL]);
        config(['services.meta.conversions_token' => 'capi-token', 'services.meta.graph_version' => 'v23.0']);
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);

        $this->commune = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true])->communes()->create(['name' => 'Cocody']);
        $this->product = Product::factory()->create(['name' => 'Enceinte', 'slug' => 'enceinte', 'price' => 45000, 'stock' => 5]);
    }

    public function test_add_to_cart_without_leaving_the_page_reports_at_once_with_the_id_of_its_server_copy(): void
    {
        $this->withCredentials()->withUnencryptedCookie('kova_consent', 'granted')->withUnencryptedCookie('_fbp', 'fb.1.1700000000000.42');

        $response = $this->postJson('/panier/articles', ['product_id' => $this->product->id, 'quantity' => 2])->assertOk();

        $event = $response->json('analytics.0');
        $this->assertSame('add_to_cart', $event['name']);
        $this->assertSame(90000, $event['params']['value']);

        // Not reported a second time on the next page.
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
        $this->assertNotContains('add_to_cart', array_column($this->events($this->get('/panier')), 'name'));

        $sent = $this->metaEvents('AddToCart');
        $this->assertCount(1, $sent);
        $this->assertSame($event['id'], $sent[0]['event_id']);
        $this->assertSame([[$this->product->defaultVariant->sku], 2, 90000, 'XOF'], [
            $sent[0]['custom_data']['content_ids'], $sent[0]['custom_data']['num_items'], $sent[0]['custom_data']['value'], $sent[0]['custom_data']['currency'],
        ]);
        $this->assertSame('fb.1.1700000000000.42', $sent[0]['user_data']['fbp']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v23.0/'.self::PIXEL.'/events' && $request['access_token'] === 'capi-token');
    }

    public function test_without_consent_or_token_the_server_sends_nothing(): void
    {
        $this->withCredentials()->postJson('/panier/articles', ['product_id' => $this->product->id, 'quantity' => 1])->assertOk();
        $this->withUnencryptedCookie('kova_consent', 'denied')->postJson('/panier/articles', ['product_id' => $this->product->id, 'quantity' => 1]);

        config(['services.meta.conversions_token' => null]);
        $this->withUnencryptedCookie('kova_consent', 'granted')->postJson('/panier/articles', ['product_id' => $this->product->id, 'quantity' => 1]);

        Http::assertNothingSent();
    }

    public function test_a_cash_on_delivery_order_is_one_purchase_for_meta_whichever_side_reports_it(): void
    {
        $this->withUnencryptedCookie('kova_consent', 'granted')->withUnencryptedCookie('_fbc', 'fb.1.1700000000000.click');
        $this->post('/panier/articles', ['product_id' => $this->product->id, 'quantity' => 2]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);

        $this->post('/commande', [
            'customer_name' => 'Koffi Yao', 'phone' => '07 01 02 03 04', 'email' => 'koffi@example.ci', 'commune_id' => $this->commune->id,
            'district' => 'Riviera 2', 'payment_method' => 'paiement_livraison', 'terms' => '1',
        ]);
        $order = Order::sole();
        $this->assertSame('fb.1.1700000000000.click', $order->tracking['fbc']);

        $browser = collect($this->events($this->get("/commande/{$order->number}/merci")))->firstWhere('name', 'purchase');
        $server = $this->metaEvents('Purchase');

        $this->assertCount(1, $server);
        $this->assertSame("purchase-{$order->number}", $browser['id']);
        $this->assertSame($browser['id'], $server[0]['event_id']);
        $this->assertSame([91500, $order->number], [$server[0]['custom_data']['value'], $server[0]['custom_data']['order_id']]);
        // Personal data leaves hashed only, the phone with its country code.
        $this->assertSame([hash('sha256', '2250701020304')], $server[0]['user_data']['ph']);
        $this->assertSame([hash('sha256', 'koffi@example.ci')], $server[0]['user_data']['em']);
        $this->assertSame([hash('sha256', 'koffi')], $server[0]['user_data']['fn']);
        $this->assertStringNotContainsString('0701020304', json_encode($server[0]));
    }

    public function test_an_order_placed_without_consent_keeps_nothing_for_meta(): void
    {
        $this->post('/panier/articles', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
        $this->post('/commande', [
            'customer_name' => 'Koffi Yao', 'phone' => '0701020304', 'commune_id' => $this->commune->id,
            'district' => 'Riviera 2', 'payment_method' => 'paiement_livraison', 'terms' => '1',
        ]);

        $this->assertNull(Order::sole()->tracking);
        Http::assertNothingSent();
    }

    public function test_search_wishlist_and_newsletter_are_reported(): void
    {
        $this->withCredentials()->withUnencryptedCookie('kova_consent', 'granted');

        $search = collect($this->events($this->get('/boutique?q=enceinte')))->firstWhere('name', 'search');
        $this->assertSame(['enceinte', 1], [$search['params']['search_term'], $search['params']['results']]);
        $this->assertNotContains('search', array_column($this->events($this->get('/boutique?q=enceinte&page=2')), 'name'));

        $wishlist = $this->postJson("/favoris/{$this->product->id}")->assertOk()->json('analytics.0');
        $this->assertSame(['add_to_wishlist', 45000], [$wishlist['name'], $wishlist['params']['value']]);
        // Removing it from the favourites reports nothing.
        $this->assertSame([], $this->postJson("/favoris/{$this->product->id}")->json('analytics'));

        $lead = $this->postJson('/newsletter', ['email' => 'awa@example.ci'])->assertOk()->json('analytics.0');
        $this->assertSame('generate_lead', $lead['name']);
        // Already subscribed: not a new lead.
        $this->assertSame([], $this->postJson('/newsletter', ['email' => 'awa@example.ci'])->json('analytics'));

        $this->assertSame(['Search', 'AddToWishlist', 'Lead'], array_column($this->metaEvents(), 'event_name'));
        $this->assertSame('enceinte', $this->metaEvents('Search')[0]['custom_data']['search_string']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function metaEvents(?string $name = null): array
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://graph.facebook.com/'))
            ->map(fn (array $pair) => $pair[0]['data'][0])
            ->filter(fn (array $event) => $name === null || $event['event_name'] === $name)
            ->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(TestResponse $response): array
    {
        preg_match('/window\.kovaAnalytics = (\{.*?\});<\/script>/s', $response->assertOk()->getContent(), $match);

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR)['events'];
    }
}
