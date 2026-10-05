<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Delivery\DispatchException;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The steps of the confirmation page follow the order as the manager confirms it, the picker prepares it and the
 * courier or the carrier brings it; the page polls checkout.progress to stay up to date. The path depends on the
 * destination: Abidjan (no "Expédiée" step) or the interior of the country (shipped to the town the customer typed).
 */
class OrderProgressTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryZone $zone;

    private Commune $cocody;

    private Commune $interior;

    private OrderStatusManager $statuses;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $this->cocody = $this->zone->communes()->create(['name' => 'Cocody']);
        $this->interior = DeliveryZone::create(['name' => 'Intérieur du pays', 'fee' => 5000, 'delay_label' => 'J+2 à J+5', 'is_active' => true, 'delivery_mode' => DeliveryMode::Interior])
            ->communes()->create(['name' => 'Intérieur']);
        $this->statuses = app(OrderStatusManager::class);
    }

    public function test_a_new_order_waits_for_the_confirmation_call(): void
    {
        $order = $this->placeOrder();

        $this->get(route('checkout.confirmation', $order))
            ->assertOk()
            ->assertSee('data-url="'.route('checkout.progress', $order).'"', false);

        $this->progress($order)
            ->assertSeeInOrder(['is-done', 'Commande reçue', 'is-current', 'Confirmation', 'Nous vous appelons au'], false)
            ->assertSeeText('Vos articles seront préparés')
            ->assertHeader('X-Order-Settled', '0');
    }

    public function test_in_abidjan_each_actor_ticks_off_their_step_without_any_shipping(): void
    {
        $order = $this->placeOrder();
        $manager = User::factory()->staff(Role::Manager)->create();
        $picker = User::factory()->staff(Role::Picker)->create();
        $this->assertSame(DeliveryMode::Abidjan, $order->delivery_mode);

        // The manager confirms after the call.
        $this->statuses->move($order, OrderStatus::Confirmed, $manager);
        $this->progress($order)
            ->assertSeeText('Commande confirmée · '.now()->format('d/m'))
            ->assertSeeInOrder(['is-current', 'Préparation'], false)
            ->assertDontSeeText('Expédiée');

        // The picker prepares; the courier then leaves straight from preparation.
        $this->statuses->move($order->refresh(), OrderStatus::Preparing, $picker);
        $this->progress($order)->assertSeeText('Vos articles sont en cours de préparation');

        [$courier, $courierUser] = $this->courier('Kofi Diallo');
        app(DeliveryDispatcher::class)->assign($order->refresh(), $courier, $manager);
        $this->statuses->move($order->refresh(), OrderStatus::OutForDelivery, $courierUser);
        $this->progress($order)
            ->assertSeeText('Vos articles sont prêts')
            ->assertSeeText('Kofi est en route vers Cocody')
            ->assertDontSeeText('Diallo')
            ->assertDontSeeText('Expédiée')
            ->assertSeeInOrder(['is-current', 'Livraison'], false)
            ->assertHeader('X-Order-Settled', '0');

        $this->statuses->move($order->refresh(), OrderStatus::Delivered, $courierUser);
        $this->progress($order)
            ->assertSeeText('Livrée à Cocody')
            ->assertDontSee('is-current', false)
            ->assertHeader('X-Order-Settled', '1');

        // The tracking timeline has no "Expédiée" step either.
        $this->post(route('tracking.search'), ['number' => $order->number, 'phone' => '0701020304'])->assertDontSeeText('Expédiée');
    }

    public function test_the_town_is_required_when_shipping_to_the_interior(): void
    {
        $this->placeOrder($this->interior, city: '')->assertSessionHasErrors(['destination_city' => 'Indiquez la ville vers laquelle expédier votre commande.']);
        $this->assertSame(0, Order::count());

        // The cart is still full: the form offers "Intérieur" and its town field.
        $this->get('/commande')->assertOk()->assertSee('data-interior', false)->assertSee('name="destination_city"', false);

        // An Abidjan commune needs no town, and keeps none.
        $this->placeOrder(city: 'Bouaké')->assertSessionHasNoErrors();
        $this->assertNull(Order::sole()->destination_city);
    }

    public function test_an_order_for_the_interior_is_shipped_to_its_town_then_delivered(): void
    {
        $this->placeOrder($this->interior, city: 'Bouaké');
        $order = Order::sole();
        $manager = User::factory()->staff(Role::Manager)->create();
        $picker = User::factory()->staff(Role::Picker)->create();

        $this->assertSame([DeliveryMode::Interior, 'Bouaké', 'Bouaké', 5000], [$order->delivery_mode, $order->destination_city, $order->commune_name, $order->shipping_fee]);
        $this->progress($order)->assertSeeInOrder(['Préparation', 'Expédiée', 'Expédition vers Bouaké', 'Livraison', 'Bouaké · J+2 à J+5'], false);

        // Never offered to the Abidjan couriers.
        [$courier] = $this->courier('Kofi Diallo');
        $this->statuses->move($order, OrderStatus::Confirmed, $manager);
        $this->assertNull($order->fresh()->courier_id);
        $this->assertFalse(app(DeliveryDispatcher::class)->queueFor($courier)->exists());
        try {
            app(DeliveryDispatcher::class)->assign($order->refresh(), $courier, $manager);
            $this->fail('An order for the interior must not go to a courier.');
        } catch (DispatchException) {
        }

        // The picker prepares and hands the parcel to the carrier.
        $this->statuses->move($order->refresh(), OrderStatus::Preparing, $picker);
        $this->statuses->move($order->refresh(), OrderStatus::Shipped, $picker);
        $this->progress($order)
            ->assertSeeText('Expédiée vers Bouaké · '.now()->format('d/m'))
            ->assertSeeText('En cours d’acheminement vers Bouaké')
            ->assertSeeInOrder(['is-current', 'Livraison'], false);

        $this->statuses->move($order->refresh(), OrderStatus::Delivered, $manager);
        $this->progress($order)->assertSeeText('Remise à Bouaké')->assertHeader('X-Order-Settled', '1');

        $this->post(route('tracking.search'), ['number' => $order->number, 'phone' => '0701020304'])
            ->assertSeeText('Expédiée')
            ->assertDontSeeText('En livraison');
    }

    public function test_a_cancelled_order_tells_the_page_to_reload(): void
    {
        $order = $this->placeOrder();
        $this->statuses->move($order, OrderStatus::Cancelled, User::factory()->staff(Role::Manager)->create(), 'Client injoignable');

        $this->progress($order)->assertHeader('X-Order-Status', OrderStatus::Cancelled->value);
    }

    public function test_only_the_customer_sees_the_progress(): void
    {
        $order = $this->placeOrder();

        $this->flushSession();
        $this->get(route('checkout.progress', $order))->assertNotFound();
    }

    private function progress(Order $order): TestResponse
    {
        return $this->get(route('checkout.progress', $order))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    /**
     * @return array{0: Courier, 1: User}
     */
    private function courier(string $name): array
    {
        $user = User::factory()->create(['name' => $name, 'phone' => '0709'.random_int(100000, 999999)]);
        $user->assignRole(Role::Courier->value);
        $courier = $user->courier()->create(['transport' => 'moto']);
        $courier->zones()->sync([$this->zone->getKey(), $this->interior->delivery_zone_id]);

        return [$courier, $user];
    }

    /**
     * Places an order for Cocody (default) or another destination; returns the order, or the response when $city
     * is given (to check the validation).
     */
    private function placeOrder(?Commune $commune = null, ?string $city = null): Order|TestResponse
    {
        $product = Product::factory()->create(['name' => 'Enceinte JBL', 'price' => 45000, 'stock' => 10]);

        $this->post('/panier/articles', ['product_id' => $product->id, 'quantity' => 1]);
        if ($cart = Cart::whereNull('user_id')->latest('id')->first()) {
            $this->withCookie(CartManager::COOKIE, $cart->token);
        }

        $response = $this->post('/commande', [
            'customer_name' => 'Awa Koné', 'phone' => '0701020304', 'email' => null, 'commune_id' => ($commune ?? $this->cocody)->id,
            'destination_city' => $city, 'district' => 'Riviera 2', 'landmark' => null, 'payment_method' => 'paiement_livraison', 'terms' => '1',
        ]);

        return $city === null ? Order::latest('id')->firstOrFail() : $response;
    }
}
