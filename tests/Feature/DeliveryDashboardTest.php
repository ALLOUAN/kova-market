<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Filament\Delivery\Widgets\DispatchQueue;
use App\Filament\Pages\DeliveryDashboard;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\PlaceOrder;
use App\Services\Delivery\DeliveryBoard;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Delivery dashboard (Livraison › Tableau de bord): the day's figures, the queue with "Attribuer", the alerts,
 * each courier's day and each zone's coverage.
 */
class DeliveryDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private DeliveryZone $zone;

    private Commune $cocody;

    private Courier $courier;

    private User $courierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create();
        $this->zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $this->cocody = $this->zone->communes()->create(['name' => 'Cocody']);

        $this->courierUser = User::factory()->create(['name' => 'Moussa Traoré', 'phone' => '+2250506070809']);
        $this->courierUser->assignRole(Role::Courier->value);
        $this->courier = $this->courierUser->courier()->create(['transport' => 'moto']);
        $this->courier->zones()->sync([$this->zone->id]);
    }

    public function test_the_figures_of_the_day(): void
    {
        $this->confirmed();                                   // waiting for a courier
        $this->onTheWay();
        $this->delivered();
        $failed = $this->onTheWay();
        app(OrderStatusManager::class)->move($failed, OrderStatus::Cancelled, $this->courierUser, 'Échec de livraison : Client absent');

        $stats = app(DeliveryBoard::class)->stats();

        $this->assertSame(1, $stats['to_assign']);
        $this->assertSame(1, $stats['to_prepare']);
        $this->assertSame(1, $stats['on_the_way']);
        $this->assertSame(1, $stats['couriers_on_the_way']);
        $this->assertSame(1, $stats['delivered_today']);
        $this->assertSame(1, $stats['failed_today']);
        $this->assertSame(50, $stats['success_rate_7d']);
        $this->assertSame(1, $stats['couriers_available']);
        $this->assertSame(25500, $stats['cash_with_couriers']);

        $row = app(DeliveryBoard::class)->couriers()->sole();
        $this->assertSame([1, 1, 1, 1, 25500], [$row['open'], $row['on_the_way'], $row['delivered_today'], $row['failed_today'], $row['due']]);

        $zone = app(DeliveryBoard::class)->zones()->sole();
        $this->assertSame([1, 1, 1, 1], [$zone['waiting'], $zone['in_progress'], $zone['delivered_today'], $zone['couriers']]);
    }

    public function test_the_alerts_point_at_what_is_stuck(): void
    {
        $waiting = $this->confirmed();
        $onTheWay = $this->onTheWay();
        $alerts = app(DeliveryBoard::class)->alerts();
        $this->assertSame([0, 0, 0], [$alerts['waiting']->count(), $alerts['on_the_way']->count(), $alerts['uncovered_zones']->count()]);

        $this->travel(DeliveryBoard::ON_THE_WAY_ALERT_HOURS + 1)->hours();
        $empty = DeliveryZone::create(['name' => 'Zone 4', 'fee' => 2500, 'is_active' => true]);
        DeliveryZone::create(['name' => 'Zone fermée', 'fee' => 2500, 'is_active' => false]);

        $alerts = app(DeliveryBoard::class)->alerts();
        $this->assertTrue($alerts['waiting']->sole()->is($waiting));
        $this->assertTrue($alerts['on_the_way']->sole()->is($onTheWay));
        $this->assertTrue($alerts['uncovered_zones']->sole()->is($empty));

        // A suspended courier no longer covers their zone.
        $this->courierUser->forceFill(['suspended_at' => now()])->save();
        $this->assertCount(2, app(DeliveryBoard::class)->alerts()['uncovered_zones']);
    }

    public function test_the_manager_opens_the_dashboard_and_assigns_from_the_queue(): void
    {
        $order = $this->confirmed();
        $this->actingAs($this->manager);

        $this->get(DeliveryDashboard::getUrl())->assertOk()->assertSeeText('Tableau de bord des livraisons');
        $this->assertSame('1', DeliveryDashboard::getNavigationBadge());

        Livewire::test(DeliveryDashboard::class)
            ->assertSeeText('À surveiller')
            ->assertSeeText('À attribuer')
            ->assertSeeText('Argent chez les livreurs')
            ->assertSeeText('1 commande attend un livreur')
            ->assertSeeText('Moussa Traoré')
            ->assertSeeText('Zone 1');

        Livewire::test(DispatchQueue::class)
            ->assertCanSeeTableRecords([$order])
            ->callAction(TestAction::make('assign')->table($order), ['courier_id' => $this->courier->id])
            ->assertHasNoActionErrors();

        $this->assertTrue($order->fresh()->courier->is($this->courier));
        Livewire::test(DispatchQueue::class)->assertCanNotSeeTableRecords([$order]);
        $this->assertNull(DeliveryDashboard::getNavigationBadge());
    }

    public function test_orders_shipped_to_the_interior_are_not_in_the_queue(): void
    {
        $interior = DeliveryZone::create(['name' => 'Intérieur du pays', 'fee' => 3500, 'is_active' => true, 'delivery_mode' => 'interieur']);
        $commune = $interior->communes()->create(['name' => 'Intérieur']);
        $this->confirmed($commune, ['destination_city' => 'Bouaké']);

        $this->assertSame(0, app(DeliveryBoard::class)->stats()['to_assign']);
        $this->assertSame(0, app(DeliveryBoard::class)->alerts()['uncovered_zones']->count());
    }

    public function test_a_picker_cannot_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        $this->get(DeliveryDashboard::getUrl())->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function confirmed(?Commune $commune = null, array $details = []): Order
    {
        $product = Product::factory()->create(['price' => 24000, 'stock' => 5]);
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => $product->defaultVariant->id, 'quantity' => 1]);
        $order = app(PlaceOrder::class)->handle($cart, [
            'customer_name' => 'Koffi Yao', 'phone' => '+2250701020304', 'email' => null, 'commune_id' => ($commune ?? $this->cocody)->id,
            'district' => 'Riviera 2', 'landmark' => null, 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
            ...$details,
        ]);

        return app(OrderStatusManager::class)->move($order, OrderStatus::Confirmed, $this->manager);
    }

    private function onTheWay(): Order
    {
        $order = $this->confirmed();
        app(DeliveryDispatcher::class)->assign($order, $this->courier);
        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::Preparing, $this->manager);

        return $statuses->move($order, OrderStatus::OutForDelivery, $this->courierUser);
    }

    private function delivered(): Order
    {
        $order = $this->onTheWay();
        $order->forceFill(['cash_collected' => $order->total])->save();

        return app(OrderStatusManager::class)->move($order, OrderStatus::Delivered, $this->courierUser);
    }
}
