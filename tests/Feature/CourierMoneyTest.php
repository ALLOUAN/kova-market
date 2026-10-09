<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\RemittanceMethod;
use App\Enums\Role;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\PlaceOrder;
use App\Services\Delivery\CashSettlement;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Mes encaissements" in the courier app: their own deliveries, cash collected and handed over, what they owe,
 * their payments and receipts; never another courier's.
 */
class CourierMoneyTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Commune $cocody;

    private Courier $courier;

    private User $courierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create();
        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
        [$this->courierUser, $this->courier] = $this->makeCourier('Moussa Traoré', '+2250506070809', $zone);
    }

    public function test_the_courier_sees_what_they_collected_handed_over_and_still_owe(): void
    {
        $order = $this->delivered(cash: 25500);
        $remittance = app(CashSettlement::class)->record($this->courier, 20000, RemittanceMethod::MobileMoney, $this->manager);
        $this->actingAs($this->courierUser);

        $this->get('/livreur')->assertOk()->assertSee('href="'.route('courier.money').'"', false);
        $this->get('/livreur/encaissements')
            ->assertOk()
            ->assertSeeText('À reverser à la boutique')
            ->assertSeeText("5\u{00A0}500\u{00A0}FCFA")
            ->assertSeeText("25\u{00A0}500\u{00A0}FCFA")
            ->assertSeeText("20\u{00A0}000\u{00A0}FCFA")
            ->assertSeeText($remittance->number())
            ->assertSeeText("Encaissé à la livraison de {$order->number}")
            ->assertSee('href="'.route('courier.remittances.receipt', $remittance).'"', false)
            ->assertDontSeeText('commission');

        $this->get('/livreur/encaissements?periode=today')->assertOk()->assertSee('periode=today" class="ch-seg__item is-active"', false);

        $receipt = $this->get(route('courier.remittances.receipt', $remittance))->assertOk();
        $this->assertSame('application/pdf', $receipt->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $receipt->getContent());
    }

    public function test_once_everything_is_handed_over_the_page_says_so(): void
    {
        $this->delivered(cash: 25500);
        app(CashSettlement::class)->settle($this->courier, $this->manager);
        $this->actingAs($this->courierUser);

        $this->get('/livreur/encaissements')->assertSeeText('Vous avez tout reversé. Merci !');
    }

    public function test_a_courier_cannot_open_another_couriers_receipt(): void
    {
        $this->delivered(cash: 25500);
        $remittance = app(CashSettlement::class)->record($this->courier, 25500, RemittanceMethod::Cash, $this->manager);
        [$otherUser] = $this->makeCourier('Awa Koné', '+2250102030405', DeliveryZone::first());

        $this->actingAs($otherUser);
        $this->get(route('courier.remittances.receipt', $remittance))->assertNotFound();
        $this->get('/livreur/encaissements')->assertOk()->assertDontSeeText($remittance->number())->assertSeeText('Aucun versement pour le moment.');
    }

    public function test_the_account_page_shows_the_profile_and_the_tab_bar_once_the_password_is_chosen(): void
    {
        $this->actingAs($this->courierUser);

        $this->get('/livreur/mot-de-passe')
            ->assertOk()
            ->assertSeeText('Mon compte')
            ->assertSeeText('Moussa Traoré')
            ->assertSeeText('Zone 1')
            ->assertSeeText('Changer mon mot de passe')
            ->assertSee('class="tabbar"', false)
            ->assertSee('name="current_password"', false);

        // First sign-in: only the password, no tab bar leading to pages that send back here.
        $this->courierUser->forceFill(['must_change_password' => true])->save();
        $this->get('/livreur/mot-de-passe')
            ->assertOk()
            ->assertSeeText('Choisir mon mot de passe')
            ->assertDontSee('class="tabbar"', false)
            ->assertDontSee('name="current_password"', false)
            ->assertDontSeeText('Mes zones');
    }

    public function test_customers_and_staff_without_courier_account_cannot_open_the_page(): void
    {
        $this->actingAs(User::factory()->customer()->create());
        $this->get('/livreur/encaissements')->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Courier}
     */
    private function makeCourier(string $name, string $phone, DeliveryZone $zone): array
    {
        $user = User::factory()->create(['name' => $name, 'phone' => $phone]);
        $user->assignRole(Role::Courier->value);
        $courier = $user->courier()->create(['transport' => 'moto']);
        $courier->zones()->sync([$zone->id]);

        return [$user, $courier];
    }

    private function delivered(int $cash): Order
    {
        $product = Product::factory()->create(['price' => 24000, 'stock' => 5]);
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => $product->defaultVariant->id, 'quantity' => 1]);
        $order = app(PlaceOrder::class)->handle($cart, [
            'customer_name' => 'Koffi Yao', 'phone' => '+2250701020304', 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'landmark' => null, 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
        ]);

        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::Confirmed, $this->manager);
        app(DeliveryDispatcher::class)->assign($order, $this->courier);
        $statuses->move($order, OrderStatus::Preparing, $this->manager);
        $statuses->move($order, OrderStatus::OutForDelivery, $this->courierUser);
        $order->forceFill(['cash_collected' => $cash])->save();
        $statuses->move($order, OrderStatus::Delivered, $this->courierUser);

        return $order->fresh();
    }
}
