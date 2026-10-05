<?php

namespace Tests\Feature\Admin;

use App\Enums\DeliveryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\StockMovementReason;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Cart;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\PlaceOrder;
use App\Services\Orders\OrderStatusException;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->product = Product::factory()->create(['name' => 'Enceinte', 'price' => 40000, 'stock' => 10, 'sold_count' => 0]);
    }

    public function test_managers_and_pickers_see_orders_customers_and_couriers_do_not(): void
    {
        $order = $this->order();

        foreach ([Role::Manager, Role::Picker] as $role) {
            $this->actingAs(User::factory()->staff($role)->create());
            $this->get(OrderResource::getUrl('index'))->assertOk()->assertSeeText($order->number);
            $this->get(OrderResource::getUrl('view', ['record' => $order]))->assertOk()->assertSeeText('Enceinte');
        }

        $this->actingAs(User::factory()->create());
        $this->get(OrderResource::getUrl('index'))->assertForbidden();
    }

    public function test_a_manager_takes_an_order_from_received_to_delivered(): void
    {
        $order = $this->order(quantity: 2);
        $manager = User::factory()->staff(Role::Manager)->create();
        $statuses = app(OrderStatusManager::class);

        // Abidjan: no "Expédiée" step, the order leaves straight from preparation.
        $this->assertSame(DeliveryMode::Abidjan, $order->delivery_mode);
        foreach ([OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OutForDelivery, OrderStatus::Delivered] as $step) {
            $statuses->move($order, $step, $manager);
        }

        $order->refresh();
        $this->assertSame([OrderStatus::Delivered, PaymentStatus::Paid], [$order->status, $order->payment_status]);
        $this->assertSame(2, $this->product->fresh()->sold_count);
        $this->assertSame(5, $order->statusHistory()->count());
        $this->assertTrue($order->statusHistory()->reorder('id', 'desc')->first()->user->is($manager));
    }

    public function test_an_abidjan_order_is_never_shipped(): void
    {
        $order = $this->order();
        $manager = User::factory()->staff(Role::Manager)->create();
        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::Confirmed, $manager);
        $statuses->move($order, OrderStatus::Preparing, $manager);

        $this->assertSame([OrderStatus::OutForDelivery, OrderStatus::Cancelled], $statuses->availableSteps($order, $manager));

        $this->expectException(OrderStatusException::class);
        $statuses->move($order, OrderStatus::Shipped, $manager);
    }

    public function test_an_order_for_the_interior_is_shipped_then_delivered(): void
    {
        $order = $this->order(interior: true);
        $manager = User::factory()->staff(Role::Manager)->create();
        $statuses = app(OrderStatusManager::class);

        $this->assertSame([DeliveryMode::Interior, 'Bouaké', 'Bouaké'], [$order->delivery_mode, $order->destination_city, $order->commune_name]);

        foreach ([OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Shipped] as $step) {
            $statuses->move($order, $step, $manager);
        }

        // Carried to the town: no "En livraison" by an Abidjan courier.
        $this->assertSame([OrderStatus::Delivered], $statuses->availableSteps($order, $manager));
        $statuses->move($order, OrderStatus::Delivered, $manager);

        $this->assertSame(
            [OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Shipped, OrderStatus::Delivered],
            $order->statusHistory()->pluck('to_status')->all(),
        );
    }

    public function test_a_step_cannot_be_skipped(): void
    {
        $this->expectException(OrderStatusException::class);

        app(OrderStatusManager::class)->move($this->order(), OrderStatus::Delivered, User::factory()->staff(Role::Manager)->create());
    }

    public function test_pickers_only_prepare_and_ship(): void
    {
        $order = $this->order();
        $manager = User::factory()->staff(Role::Manager)->create();
        $picker = User::factory()->staff(Role::Picker)->create();
        $statuses = app(OrderStatusManager::class);

        $this->assertSame([], $statuses->availableSteps($order, $picker));
        $statuses->move($order, OrderStatus::Confirmed, $manager);
        $this->assertSame([OrderStatus::Preparing], $statuses->availableSteps($order, $picker));

        // Abidjan: once prepared, the courier takes over.
        $statuses->move($order, OrderStatus::Preparing, $picker);
        $this->assertSame([], $statuses->availableSteps($order, $picker));

        // Interior: the picker hands the parcel to the carrier.
        $parcel = $this->order(interior: true);
        $statuses->move($parcel, OrderStatus::Confirmed, $manager);
        $statuses->move($parcel, OrderStatus::Preparing, $picker);
        $statuses->move($parcel, OrderStatus::Shipped, $picker);
        $this->assertSame(OrderStatus::Shipped, $parcel->status);

        $this->expectException(OrderStatusException::class);
        $statuses->move($order, OrderStatus::OutForDelivery, $picker);
    }

    public function test_cancelling_needs_a_reason_and_puts_the_stock_back(): void
    {
        $order = $this->order(quantity: 3);
        $this->actingAs($manager = User::factory()->staff(Role::Manager)->create());
        $this->assertSame(7, $this->product->defaultVariant->fresh()->stock);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction(TestAction::make('to_annulee'), ['note' => 'Client injoignable'])
            ->assertNotified('Commande « Annulée »');

        $order->refresh();
        $this->assertSame([OrderStatus::Cancelled, PaymentStatus::Cancelled], [$order->status, $order->payment_status]);
        $this->assertSame(10, $this->product->defaultVariant->fresh()->stock);
        $release = $this->product->defaultVariant->stockMovements()->first();
        $this->assertSame([StockMovementReason::Release, 3], [$release->reason, $release->quantity]);
        $this->assertTrue($release->user->is($manager));
        $this->assertSame('Client injoignable', $order->statusHistory()->reorder('id', 'desc')->value('note'));
    }

    public function test_only_a_super_admin_goes_back_one_step_with_a_reason(): void
    {
        $order = $this->order();
        $statuses = app(OrderStatusManager::class);
        $manager = User::factory()->staff(Role::Manager)->create();
        $superAdmin = User::factory()->staff(Role::SuperAdmin)->create();
        foreach ([OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OutForDelivery, OrderStatus::Delivered] as $step) {
            $statuses->move($order, $step, $manager);
        }

        $this->assertFalse($statuses->mayRollBack($order, $manager));

        // One step back in the Abidjan flow each time: delivered → on the way → preparation.
        $statuses->rollBack($order, $superAdmin, 'Livraison saisie par erreur');
        $this->assertSame(OrderStatus::OutForDelivery, $order->status);
        $statuses->rollBack($order, $superAdmin, 'Le livreur n’est pas encore parti');

        $order->refresh();
        $this->assertSame([OrderStatus::Preparing, PaymentStatus::Pending], [$order->status, $order->payment_status]);
        $this->assertSame(0, $this->product->fresh()->sold_count);
    }

    public function test_the_view_page_offers_the_next_steps_and_the_order_slip(): void
    {
        $order = $this->order();
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionVisible('to_confirmee')
            ->assertActionVisible('to_annulee')
            ->assertActionHidden('to_livree')
            ->callAction('to_confirmee')
            ->assertActionVisible('to_en_preparation')
            ->callAction('slip')
            ->assertFileDownloaded("bon-de-commande-{$order->number}.pdf");

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }

    /**
     * An order delivered in Abidjan (Cocody), or shipped to Bouaké when $interior.
     */
    private function order(int $quantity = 1, bool $interior = false): Order
    {
        $zone = $interior
            ? DeliveryZone::firstOrCreate(['name' => 'Intérieur du pays'], ['fee' => 5000, 'is_active' => true, 'delivery_mode' => DeliveryMode::Interior])
            : DeliveryZone::firstOrCreate(['name' => 'Zone 1'], ['fee' => 1500, 'is_active' => true]);
        $commune = $zone->communes()->firstOrCreate(['name' => $interior ? 'Intérieur' : 'Cocody']);
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => $this->product->defaultVariant->id, 'quantity' => $quantity]);

        return app(PlaceOrder::class)->handle($cart, [
            'customer_name' => 'Koffi Yao', 'phone' => '+2250701020304', 'email' => null, 'commune_id' => $commune->id,
            'destination_city' => $interior ? 'Bouaké' : null,
            'district' => 'Riviera', 'landmark' => null, 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
        ]);
    }
}
