<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_response_carries_the_security_headers(): void
    {
        foreach (['/', '/api/v1/products', '/'.config('admin.path').'/login'] as $url) {
            $response = $this->get($url)->assertOk();

            $response->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
            $this->assertStringContainsString("object-src 'none'", $response->headers->get('Content-Security-Policy'));
        }
    }

    public function test_hsts_is_only_sent_over_https(): void
    {
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');

        $response = $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->assertStringContainsString('upgrade-insecure-requests', $response->headers->get('Content-Security-Policy'));
    }

    public function test_the_policy_can_be_tried_in_report_only_mode(): void
    {
        config(['security.csp.report_only' => true]);

        $this->get('/')->assertHeaderMissing('Content-Security-Policy')->assertHeader('Content-Security-Policy-Report-Only');
    }

    public function test_a_short_password_is_refused_at_sign_up(): void
    {
        $this->post('/register', [
            'name' => 'Awa Koné',
            'phone' => '0701020304',
            'password' => 'abc1234',
            'password_confirmation' => 'abc1234',
        ])->assertSessionHasErrors('password');

        $this->assertSame(0, User::count());
    }

    public function test_public_forms_are_refused_to_robots_once_turnstile_is_configured(): void
    {
        $this->configureTurnstile(passes: false);
        $commune = $this->commune();
        $this->addToCart(Product::factory()->create(['stock' => 5]));

        $this->get('/commande')->assertOk()->assertSee('class="cf-turnstile', false)->assertSee('challenges.cloudflare.com/turnstile', false);

        $this->post('/commande', $this->orderDetails($commune))->assertSessionHasErrors(['cf-turnstile-response' => 'Merci de confirmer que vous n’êtes pas un robot.']);
        $this->post('/register', ['name' => 'Awa', 'phone' => '0701020304', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
            ->assertSessionHasErrors('cf-turnstile-response');
        $this->post('/suivi', ['number' => 'KM-260927-0001', 'phone' => '0701020304'])->assertSessionHasErrors('cf-turnstile-response');
        $this->post('/alertes-stock', ['product_id' => Product::factory()->soldOut()->create()->id, 'contact' => '0701020304'])
            ->assertSessionHasErrors('cf-turnstile-response');

        $this->assertSame([0, 0], [Order::count(), User::count()]);
    }

    public function test_a_human_passing_turnstile_places_the_order(): void
    {
        $this->configureTurnstile(passes: true);
        $commune = $this->commune();
        $this->addToCart(Product::factory()->create(['stock' => 5]));

        $this->post('/commande', [...$this->orderDetails($commune), 'cf-turnstile-response' => 'token'])->assertSessionHasNoErrors();

        $this->assertSame(1, Order::count());
        Http::assertSent(fn ($request) => $request['response'] === 'token');
    }

    public function test_the_api_relies_on_rate_limits_rather_than_turnstile(): void
    {
        $this->configureTurnstile(passes: false);
        $commune = $this->commune();
        $token = $this->postJson('/api/v1/cart/items', ['product_id' => Product::factory()->create(['stock' => 5])->id])->json('data.token');

        $this->postJson('/api/v1/orders', [...$this->orderDetails($commune), 'terms' => true], [CartManager::HEADER => $token])->assertCreated();
        Http::assertNothingSent();
    }

    private function configureTurnstile(bool $passes): void
    {
        config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret-key']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => $passes])]);
    }

    private function commune(): Commune
    {
        return DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true])
            ->communes()->create(['name' => 'Cocody']);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderDetails(Commune $commune): array
    {
        return [
            'customer_name' => 'Koffi Yao', 'phone' => '0701020304', 'commune_id' => $commune->id,
            'district' => 'Riviera 2', 'payment_method' => 'paiement_livraison', 'terms' => '1',
        ];
    }

    private function addToCart(Product $product): void
    {
        $this->post('/panier/articles', ['product_id' => $product->id]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
    }
}
