<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RemittanceMethod;
use App\Enums\Role;
use App\Enums\TransactionStatus;
use App\Filament\Pages\Finances;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\PlaceOrder;
use App\Services\Delivery\CashSettlement;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Finance\FinancePeriod;
use App\Services\Finance\FinanceReport;
use App\Services\Orders\OrderStatusManager;
use App\Support\Money;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Finance dashboard (lot 3): figures of the period, per zone and per courier, the page and its export, and the
 * orders list's totals, payment method filter, period shortcuts and export.
 */
class FinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Commune $cocody;

    private Commune $yopougon;

    private Courier $courier;

    private User $courierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create();
        $zone1 = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $zone2 = DeliveryZone::create(['name' => 'Zone 2', 'fee' => 2000, 'is_active' => true]);
        $this->cocody = $zone1->communes()->create(['name' => 'Cocody']);
        $this->yopougon = $zone2->communes()->create(['name' => 'Yopougon']);

        $this->courierUser = User::factory()->create(['name' => 'Moussa Traoré', 'phone' => '+2250506070809']);
        $this->courierUser->assignRole(Role::Courier->value);
        $this->courier = $this->courierUser->courier()->create(['transport' => 'moto']);
        $this->courier->zones()->sync([$zone1->id, $zone2->id]);
    }

    public function test_the_figures_of_the_period(): void
    {
        $delivered = $this->delivered($this->cocody, 20000);          // 20 000 + 1 500
        $this->order($this->yopougon, 10000);                          // 10 000 + 2 000, received, cash on delivery
        $cancelled = $this->order($this->cocody, 5000);
        app(OrderStatusManager::class)->move($cancelled, OrderStatus::Cancelled, $this->manager, 'Rupture');
        $online = $this->order($this->yopougon, 30000, PaymentMethod::Online);  // 30 000 + 2 000, paid online
        $this->paid($online);
        app(CashSettlement::class)->record($this->courier, 15000, RemittanceMethod::Cash, $this->manager);

        $summary = app(FinanceReport::class)->summary(FinancePeriod::make('month'));

        $this->assertSame(4, $summary['orders']);
        $this->assertSame(1, $summary['delivered']);
        $this->assertSame(2, $summary['in_progress']);
        $this->assertSame(1, $summary['cancelled']);
        // Sales: the delivered order, the one received and the one paid online (not the cancelled one).
        $this->assertSame(3, $summary['sales']);
        $this->assertSame(21500 + 12000 + 32000, $summary['revenue']);
        $this->assertSame(1500 + 2000 + 2000, $summary['shipping_fees']);
        $this->assertSame(60000, $summary['products']);
        $this->assertSame(32000, $summary['collected_online']);
        $this->assertSame(21500, $summary['collected_on_delivery']);
        $this->assertSame(53500, $summary['collected']);
        $this->assertSame(15000, $summary['remitted']);
        $this->assertSame(6500, $summary['cash_with_couriers']);
        $this->assertSame($delivered->total, 21500);

        // Last month: nothing.
        $before = app(FinanceReport::class)->summary(FinancePeriod::make('month')->previous());
        $this->assertSame([0, 0, 0], [$before['orders'], $before['revenue'], $before['collected']]);
    }

    public function test_per_zone_and_per_courier(): void
    {
        $this->delivered($this->cocody, 20000);
        $this->delivered($this->yopougon, 10000);
        $this->order($this->yopougon, 5000);
        app(CashSettlement::class)->record($this->courier, 10000, RemittanceMethod::Cash, $this->manager);
        $period = FinancePeriod::make('month');

        $zones = app(FinanceReport::class)->byZone($period)->keyBy('zone');
        $this->assertSame(['orders' => 2, 'revenue' => 12000 + 7000, 'shipping_fees' => 4000], array_diff_key($zones['Zone 2'], ['zone' => 0]));
        $this->assertSame(['orders' => 1, 'revenue' => 21500, 'shipping_fees' => 1500], array_diff_key($zones['Zone 1'], ['zone' => 0]));

        $row = app(FinanceReport::class)->byCourier($period)->sole();
        $this->assertTrue($row['courier']->is($this->courier));
        $this->assertSame([2, 0, 100, 3500, 33500, 10000, 23500], [
            $row['delivered'], $row['failed'], $row['success_rate'], $row['shipping_fees'], $row['collected'], $row['remitted'], $row['due'],
        ]);
    }

    public function test_the_revenue_series_covers_every_day_of_the_month(): void
    {
        $this->delivered($this->cocody, 20000);
        $period = FinancePeriod::make('month');

        $series = app(FinanceReport::class)->series($period);

        $this->assertSame('day', $period->bucket());
        $this->assertCount(now()->daysInMonth, $series);
        $this->assertSame(21500, $series->sum('revenue'));
        $this->assertSame('month', FinancePeriod::make('year')->bucket());
    }

    public function test_the_manager_opens_the_page_and_exports_it(): void
    {
        $this->delivered($this->cocody, 20000);
        $this->actingAs($this->manager);

        $this->get(Finances::getUrl())->assertOk();
        Livewire::test(Finances::class)
            ->assertSeeText('Chiffre d’affaires')
            ->assertSeeText('À la livraison')
            ->assertSeeText('Total encaissé')
            ->assertSeeText('Zone 1')
            ->assertSeeText('Moussa Traoré')
            ->call('choosePeriod', 'year')
            ->assertSeeText('Année '.now()->year)
            ->call('choosePeriod', 'custom')
            ->assertSet('filters.from', now()->startOfMonth()->toDateString())
            ->set('filters.from', now()->subYears(2)->startOfYear()->toDateString())
            ->set('filters.to', now()->subYears(2)->endOfYear()->toDateString())
            ->assertSeeText('Aucune vente sur la période')
            ->callAction('export')
            ->assertFileDownloaded();
    }

    public function test_a_picker_cannot_open_the_finances(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        $this->get(Finances::getUrl())->assertForbidden();
    }

    public function test_the_orders_list_filters_by_payment_method_and_period_and_sums_the_orders_shown(): void
    {
        $cash = $this->order($this->cocody, 20000);
        $online = $this->order($this->yopougon, 30000, PaymentMethod::Online);
        $old = $this->order($this->cocody, 5000);
        $old->forceFill(['created_at' => now()->subYear()])->save();
        $this->actingAs($this->manager);

        Livewire::test(ListOrders::class)
            ->filterTable('payment_method', PaymentMethod::Online->value)
            ->assertCanSeeTableRecords([$online])
            ->assertCanNotSeeTableRecords([$cash, $old]);

        Livewire::test(ListOrders::class)
            ->filterTable('period', ['preset' => 'month'])
            ->assertCanSeeTableRecords([$cash, $online])
            ->assertCanNotSeeTableRecords([$old])
            ->assertSeeText('Ce mois')
            ->assertSeeText(Money::format(21500 + 32000));

        Livewire::test(ListOrders::class)->callAction('export')->assertFileDownloaded('commandes-'.now()->format('Y-m-d').'.csv');
    }

    private function order(Commune $commune, int $price, PaymentMethod $method = PaymentMethod::CashOnDelivery): Order
    {
        $product = Product::factory()->create(['price' => $price, 'stock' => 5]);
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => $product->defaultVariant->id, 'quantity' => 1]);

        return app(PlaceOrder::class)->handle($cart, [
            'customer_name' => 'Koffi Yao', 'phone' => '+2250701020304', 'email' => null, 'commune_id' => $commune->id,
            'district' => 'Riviera 2', 'landmark' => null, 'note' => null,
            'payment_method' => $method->value, 'marketing_opt_in' => false,
        ]);
    }

    private function delivered(Commune $commune, int $price): Order
    {
        $order = $this->order($commune, $price);
        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::Confirmed, $this->manager);
        app(DeliveryDispatcher::class)->assign($order, $this->courier);
        $statuses->move($order, OrderStatus::Preparing, $this->manager);
        $statuses->move($order, OrderStatus::OutForDelivery, $this->courierUser);
        $order->forceFill(['cash_collected' => $order->total])->save();
        $statuses->move($order, OrderStatus::Delivered, $this->courierUser);

        return $order->fresh();
    }

    private function paid(Order $order): void
    {
        Payment::create([
            'order_id' => $order->id, 'provider' => 'cinetpay', 'merchant_transaction_id' => 'KM-'.Str::random(10),
            'amount' => $order->total, 'currency' => 'XOF', 'status' => TransactionStatus::Succeeded, 'paid_at' => now(),
        ]);
        $order->forceFill(['payment_status' => PaymentStatus::Paid])->save();
    }
}
