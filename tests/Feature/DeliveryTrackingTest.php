<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Filament\Resources\Couriers\Pages\EditCourier;
use App\Filament\Resources\Couriers\Pages\ListCouriers;
use App\Filament\Resources\Couriers\RelationManagers\DeliveriesRelationManager;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\DeliveryDateForCustomer;
use App\Services\Checkout\PlaceOrder;
use App\Services\Delivery\CashSettlement;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DeliveryTrackingTest extends TestCase
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

        $this->courierUser = User::factory()->create(['name' => 'Moussa Traoré', 'phone' => '+2250506070809']);
        $this->courierUser->assignRole(Role::Courier->value);
        $this->courier = $this->courierUser->courier()->create(['transport' => 'moto']);
        $this->courier->zones()->sync([$zone->id]);
    }

    public function test_the_cash_to_hand_over_is_the_cash_of_delivered_orders_not_settled_yet(): void
    {
        $first = $this->delivered(cash: 41500);
        $second = $this->delivered(cash: 41000);
        $this->failed();
        $alreadySettled = $this->delivered(cash: 41500);
        $alreadySettled->forceFill(['cash_settled_at' => now()->subDay()])->save();

        $settlement = app(CashSettlement::class);
        $this->assertSame(82500, $settlement->due($this->courier));

        $this->actingAs($this->manager);
        Livewire::test(ListCouriers::class)
            ->assertTableColumnStateSet('cash_due', 82500, $this->courier)
            ->assertTableColumnStateSet('delivered_count', 3, $this->courier)
            ->assertTableColumnStateSet('failed_count', 1, $this->courier)
            ->assertSeeText('75 % de réussite')
            ->callAction(TestAction::make('settleCash')->table($this->courier));

        $this->assertSame(0, $settlement->due($this->courier));
        $this->assertTrue($first->fresh()->cashSettledBy->is($this->manager));
        $this->assertNotNull($second->fresh()->cash_settled_at);
        $log = Activity::where('description', 'Encaissements reçus de Moussa Traoré')->sole();
        $this->assertSame(82500, $log->properties['montant']);

        Livewire::test(ListCouriers::class)->assertActionHidden(TestAction::make('settleCash')->table($this->courier));
        Livewire::test(DeliveriesRelationManager::class, ['ownerRecord' => $this->courier, 'pageClass' => EditCourier::class])
            ->assertCanSeeTableRecords([$first, $second, $alreadySettled]);
    }

    public function test_the_customer_sees_the_planned_date_and_the_courier(): void
    {
        Notification::fake();
        $order = $this->confirmedOrder();

        $this->actingAs($this->manager);
        Livewire::test(ViewOrder::class, ['record' => $order->number])
            ->callAction('deliveryDate', ['delivery_date' => now()->addDays(2)->format('Y-m-d')])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame(now()->addDays(2)->format('Y-m-d'), $order->delivery_date->format('Y-m-d'));
        Notification::assertSentOnDemand(DeliveryDateForCustomer::class, fn ($notification, array $channels, object $notifiable) => $notifiable->routes['sms'] === '+2250701020304'
            && str_contains($notification->toSms($notifiable), "sera livrée le {$order->delivery_date->translatedFormat('l j F')}"));

        app(DeliveryDispatcher::class)->assign($order, $this->courier);
        auth()->logout();

        $this->post('/suivi', ['number' => $order->number, 'phone' => '07 01 02 03 04'])
            ->assertOk()
            ->assertSeeText('Livraison prévue le '.$order->delivery_date->translatedFormat('l j F'))
            ->assertSeeText('Votre livreur : Moussa Traoré')
            ->assertSee('href="tel:+2250506070809"', false);

        // Once delivered, the courier's contact is no longer shown.
        $this->ship($order);
        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::OutForDelivery, $this->courierUser);
        $statuses->move($order, OrderStatus::Delivered, $this->courierUser);
        $this->post('/suivi', ['number' => $order->number, 'phone' => '07 01 02 03 04'])->assertDontSeeText('Votre livreur');
    }

    private function delivered(int $cash): Order
    {
        $order = $this->confirmedOrder();
        app(DeliveryDispatcher::class)->assign($order, $this->courier);
        $this->ship($order);
        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::OutForDelivery, $this->courierUser);
        $order->forceFill(['cash_collected' => $cash])->save();
        $statuses->move($order, OrderStatus::Delivered, $this->courierUser);

        return $order;
    }

    private function failed(): Order
    {
        $order = $this->confirmedOrder();
        app(DeliveryDispatcher::class)->assign($order, $this->courier);
        $this->ship($order);
        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::OutForDelivery, $this->courierUser);

        return $statuses->move($order, OrderStatus::Cancelled, $this->courierUser, 'Échec de livraison : Client absent');
    }

    private function confirmedOrder(): Order
    {
        $product = Product::factory()->create(['price' => 40000, 'stock' => 5]);
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => $product->defaultVariant->id, 'quantity' => 1]);

        $order = app(PlaceOrder::class)->handle($cart, [
            'customer_name' => 'Koffi Yao', 'phone' => '+2250701020304', 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'landmark' => null, 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
        ]);

        return app(OrderStatusManager::class)->move($order, OrderStatus::Confirmed, $this->manager);
    }

    private function ship(Order $order): void
    {
        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::Preparing, $this->manager);
        $statuses->move($order, OrderStatus::Shipped, $this->manager);
    }
}
