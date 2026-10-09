<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\DeliveryZones\Pages\EditDeliveryZone;
use App\Models\Cart;
use App\Models\Commune;
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

/**
 * Conditions of a delivery zone: minimum order, free delivery from its own threshold (else the general one) and
 * delivery days, applied to the cart, the checkout and the order, shown to the customer and set in the back-office.
 */
class DeliveryZoneConditionsTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryZone $zone;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $this->zone = DeliveryZone::create(['name' => 'Zone 3', 'fee' => 2000, 'delay_label' => 'J+1', 'is_active' => true]);
        $this->cocody = $this->zone->communes()->create(['name' => 'Bingerville']);
    }

    public function test_the_zone_threshold_replaces_the_general_one(): void
    {
        Setting::store(['delivery.free_shipping_threshold' => 100000]);
        $this->zone->update(['free_shipping_threshold' => 30000]);
        $this->addToCart(Product::factory()->create(['price' => 40000, 'stock' => 5]));
        $this->post('/panier/commune', ['commune_id' => $this->cocody->id]);

        $this->get('/panier')->assertSeeText('Offerte');
        $this->placeOrder();

        $this->assertSame([0, 40000], [Order::sole()->shipping_fee, Order::sole()->total]);
    }

    public function test_without_its_own_threshold_the_zone_follows_the_general_one(): void
    {
        Setting::store(['delivery.free_shipping_threshold' => 100000]);
        $this->addToCart(Product::factory()->create(['price' => 40000, 'stock' => 5]));

        $this->placeOrder();

        $this->assertSame(2000, Order::sole()->shipping_fee);
        $this->assertSame(100000, $this->zone->freeShippingThreshold());
    }

    public function test_below_the_minimum_order_the_cart_says_what_is_missing_and_the_order_is_refused(): void
    {
        $this->zone->update(['min_order' => 50000]);
        $this->addToCart(Product::factory()->create(['price' => 40000, 'stock' => 5]));
        $this->post('/panier/commune', ['commune_id' => $this->cocody->id]);

        $this->get('/panier')
            ->assertSeeText('Commande minimum pour Bingerville')
            ->assertSeeText("Ajoutez encore 10\u{00A0}000\u{00A0}FCFA d’articles.")
            ->assertSee('class="rbt-btn w-100 mt--16" disabled>Commander', false);
        $this->get('/commande')->assertRedirect('/panier');

        $this->placeOrder()->assertRedirect('/panier')
            ->assertSessionHas('cart_error', "La commande minimum pour Bingerville est de 50\u{00A0}000\u{00A0}FCFA : ajoutez encore 10\u{00A0}000\u{00A0}FCFA d’articles.");
        $this->assertSame(0, Order::count());
    }

    public function test_reaching_the_minimum_lets_the_customer_order(): void
    {
        $this->zone->update(['min_order' => 50000]);
        $this->addToCart(Product::factory()->create(['price' => 50000, 'stock' => 5]));

        $this->placeOrder();

        $this->assertSame(1, Order::count());
    }

    public function test_delivery_days_and_conditions_are_shown_to_the_customer(): void
    {
        $this->zone->update(['delivery_days' => ['5', '1', '3'], 'min_order' => 10000, 'free_shipping_threshold' => 60000]);

        $this->assertSame([1, 3, 5], $this->zone->deliveryDays());
        $this->assertSame('lundi, mercredi et vendredi', $this->zone->deliveryDaysLabel());
        $this->assertSame("Livraison le lundi, mercredi et vendredi · commande minimum 10\u{00A0}000\u{00A0}FCFA · livraison offerte dès 60\u{00A0}000\u{00A0}FCFA", $this->zone->conditionsLabel());

        $this->addToCart(Product::factory()->create(['price' => 20000, 'stock' => 5]));
        $this->post('/panier/commune', ['commune_id' => $this->cocody->id]);
        $this->get('/panier')->assertSeeText('Livraison le lundi, mercredi et vendredi');
        $this->get('/commande')->assertSee('data-conditions="Livraison le lundi, mercredi et vendredi', false)->assertSee('data-minimum="10000"', false);

        $this->getJson('/api/v1/communes')->assertOk()
            ->assertJsonPath('data.0.zone.min_order', 10000)
            ->assertJsonPath('data.0.zone.delivery_days', [1, 3, 5]);

        // Every day ticked is the same as none.
        $this->zone->update(['delivery_days' => range(1, 7)]);
        $this->assertNull($this->zone->fresh()->deliveryDaysLabel());
    }

    public function test_the_conditions_are_set_in_the_back_office(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(EditDeliveryZone::class, ['record' => $this->zone->getRouteKey()])
            ->fillForm(['min_order' => 15000, 'free_shipping_threshold' => 75000, 'delivery_days' => [2, 4]])
            ->call('save')
            ->assertHasNoFormErrors();

        $zone = $this->zone->fresh();
        $this->assertSame([15000, 75000, [2, 4]], [$zone->min_order, $zone->free_shipping_threshold, $zone->deliveryDays()]);
    }

    private function placeOrder(): TestResponse
    {
        return $this->post('/commande', [
            'customer_name' => 'Koffi Yao', 'phone' => '0701020304', 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Centre', 'landmark' => null, 'payment_method' => 'paiement_livraison', 'terms' => '1',
        ]);
    }

    private function addToCart(Product $product): void
    {
        $this->post('/panier/articles', ['product_id' => $product->id]);

        if ($cart = Cart::whereNull('user_id')->latest('id')->first()) {
            $this->withCookie(CartManager::COOKIE, $cart->token);
        }
    }
}
