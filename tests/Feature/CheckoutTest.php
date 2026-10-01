<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Checkout\OrderNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
        $this->travelTo(now()->setDate(2026, 9, 27));
    }

    public function test_a_guest_places_a_cash_on_delivery_order(): void
    {
        Event::fake([OrderPlaced::class]);
        $product = Product::factory()->create(['name' => 'Enceinte JBL', 'price' => 45000, 'stock' => 10]);
        $this->addToCart($product, 2);

        $this->get('/commande')->assertOk()->assertSeeText('Finaliser ma commande')->assertSeeText('Enceinte JBL');

        $response = $this->placeOrder(['phone' => '07 01 02 03 04']);

        $order = Order::sole();
        $response->assertRedirect("/commande/{$order->number}/merci");
        $this->assertSame('KM-260927-0001', $order->number);
        $this->assertSame([OrderStatus::Received, PaymentStatus::Pending], [$order->status, $order->payment_status]);
        $this->assertSame(['+2250701020304', 'Cocody', 'Zone 1'], [$order->phone, $order->commune_name, $order->zone_name]);
        $this->assertSame([90000, 1500, 91500], [$order->subtotal, $order->shipping_fee, $order->total]);
        $this->assertSame(['Enceinte JBL', 45000, 2], [$order->items->sole()->product_name, $order->items->sole()->unit_price, $order->items->sole()->quantity]);
        $this->assertSame(OrderStatus::Received, $order->statusHistory->sole()->to_status);
        Event::assertDispatched(OrderPlaced::class);

        // Stock taken with a "sale" movement, cart emptied.
        $this->assertSame(8, $product->defaultVariant->fresh()->stock);
        $this->assertSame(StockMovementReason::Sale, $product->defaultVariant->stockMovements()->first()->reason);
        $this->assertSame(0, Cart::sole()->items()->count());
        // The emptied cart keeps its commune but no delivery fee: the header shows 0 FCFA.
        $this->assertSame(0, app(CartManager::class)->summary()->total());

        $this->get("/commande/{$order->number}/merci")->assertOk()->assertSeeText($order->number)->assertSeeText("91\u{00A0}500\u{00A0}FCFA");
    }

    public function test_a_visitor_is_told_no_account_is_needed_and_may_sign_in_to_come_back(): void
    {
        $product = Product::factory()->create(['price' => 45000, 'stock' => 10]);
        $this->addToCart($product);

        $this->get('/commande')->assertOk()
            ->assertSeeText('Pas besoin de compte pour commander')
            ->assertSee('data-bs-target="#signinModal"', false);

        // Signing in from the checkout brings the customer back to it, the cart kept.
        $customer = User::factory()->customer()->create(['phone' => '0701020304']);
        $this->post('/login', ['login' => '0701020304', 'password' => 'password'])->assertRedirect('/commande');

        $this->actingAs($customer)->get('/commande')->assertOk()->assertDontSeeText('Pas besoin de compte pour commander');
    }

    public function test_order_lines_keep_their_price_when_the_product_changes(): void
    {
        $product = Product::factory()->create(['name' => 'Casque', 'price' => 30000]);
        $this->addToCart($product);
        $this->placeOrder();

        $product->defaultVariant->update(['price' => 99000]);
        $product->update(['name' => 'Casque renommé']);

        $item = Order::sole()->items->sole();
        $this->assertSame(['Casque', 30000], [$item->product_name, $item->unit_price]);
    }

    public function test_consent_phone_and_commune_are_required(): void
    {
        $this->addToCart(Product::factory()->create());

        $this->placeOrder(['terms' => null, 'phone' => '0102', 'commune_id' => null, 'district' => ''])
            ->assertSessionHasErrors(['terms', 'phone', 'commune_id', 'district']);

        $this->assertSame(0, Order::count());
    }

    public function test_nothing_is_recorded_when_a_line_can_no_longer_be_served(): void
    {
        $available = Product::factory()->create(['stock' => 5]);
        $scarce = Product::factory()->create(['name' => 'Montre', 'stock' => 3]);
        $this->addToCart($available);
        $this->addToCart($scarce, 3);

        // Someone else bought two watches meanwhile.
        $scarce->defaultVariant->forceFill(['stock' => 1])->save();

        $this->placeOrder()->assertRedirect('/panier')->assertSessionHas('cart_error', 'Il ne reste que 1 « Montre » : ajustez la quantité pour commander.');

        $this->assertSame(0, Order::count());
        $this->assertSame(5, $available->defaultVariant->fresh()->stock);
        $this->assertSame(2, Cart::sole()->items()->count());
    }

    public function test_cash_on_delivery_is_refused_above_the_limit(): void
    {
        Setting::store(['payment.cash_on_delivery_limit' => 50000]);
        $this->addToCart(Product::factory()->create(['price' => 60000, 'stock' => 5]));

        $this->placeOrder()->assertRedirect('/panier')->assertSessionHas('cart_error', "Le paiement à la livraison est limité à 50\u{00A0}000\u{00A0}FCFA par commande.");
        $this->assertSame(0, Order::count());
    }

    public function test_free_delivery_applies_above_the_threshold(): void
    {
        Setting::store(['delivery.free_shipping_threshold' => 50000]);
        $this->addToCart(Product::factory()->create(['price' => 60000, 'stock' => 5]));

        $this->placeOrder();

        $this->assertSame([0, 60000], [Order::sole()->shipping_fee, Order::sole()->total]);
    }

    public function test_a_signed_in_customer_gets_the_order_on_the_account(): void
    {
        $user = User::factory()->customer()->create(['name' => 'Aya Kouassi', 'phone' => '0501020304']);
        $this->actingAs($user);
        $this->post('/panier/articles', ['product_id' => Product::factory()->create()->id]);

        $this->get('/commande')->assertSee('value="Aya Kouassi"', false)->assertSee('value="+225 05 01 02 03 04"', false);
        $this->placeOrder(['customer_name' => 'Aya Kouassi', 'phone' => '0501020304']);

        $this->assertTrue(Order::sole()->user->is($user));
    }

    public function test_a_confirmation_page_is_only_shown_to_whoever_placed_the_order(): void
    {
        $this->addToCart(Product::factory()->create());
        $this->placeOrder();
        $number = Order::sole()->number;

        $this->flushSession();

        $this->get("/commande/{$number}/merci")->assertNotFound();
    }

    public function test_an_empty_cart_cannot_reach_the_checkout(): void
    {
        $this->get('/commande')->assertRedirect('/panier');
    }

    public function test_order_numbers_follow_the_day_and_never_repeat(): void
    {
        $numbers = app(OrderNumberGenerator::class);

        $this->assertSame(['KM-260927-0001', 'KM-260927-0002'], [$numbers->next(), $numbers->next()]);
        $this->assertSame('KM-260928-0001', $numbers->next(now()->addDay()));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function placeOrder(array $overrides = []): TestResponse
    {
        return $this->post('/commande', [
            'customer_name' => 'Koffi Yao',
            'phone' => '0701020304',
            'email' => null,
            'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2',
            'landmark' => 'Près de la pharmacie',
            'payment_method' => 'paiement_livraison',
            'terms' => '1',
            ...$overrides,
        ]);
    }

    private function addToCart(Product $product, int $quantity = 1): void
    {
        $this->post('/panier/articles', ['product_id' => $product->id, 'quantity' => $quantity]);

        if ($cart = Cart::whereNull('user_id')->latest('id')->first()) {
            $this->withCookie(CartManager::COOKIE, $cart->token);
        }
    }
}
