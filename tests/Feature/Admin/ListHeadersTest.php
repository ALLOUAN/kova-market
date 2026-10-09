<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Widgets\CustomersOverview;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Widgets\OrdersOverview;
use App\Filament\Resources\Payments\Widgets\PaymentsOverview;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Header bands and tabs of the back-office lists (orders, payments, customers).
 */
class ListHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_orders_list_says_what_to_handle_and_its_tabs_filter_the_steps(): void
    {
        $received = $this->order(OrderStatus::Received);
        $unpaidOnline = $this->order(OrderStatus::Received, ['payment_method' => PaymentMethod::Online]);
        $onTheWay = $this->order(OrderStatus::OutForDelivery);
        $cancelled = $this->order(OrderStatus::Cancelled);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $this->get(ListOrders::getUrl())->assertOk()->assertSeeText('Commandes')->assertSeeText('À traiter');
        Livewire::test(OrdersOverview::class)
            ->assertSeeText('4 commandes aujourd’hui')
            ->assertSeeText('1 à traiter')
            ->assertSeeText('Ventes du jour');

        Livewire::test(ListOrders::class, ['activeTab' => 'to_handle'])
            ->assertCanSeeTableRecords([$received])
            ->assertCanNotSeeTableRecords([$unpaidOnline, $onTheWay, $cancelled]);
        Livewire::test(ListOrders::class, ['activeTab' => 'on_the_way'])->assertCanSeeTableRecords([$onTheWay])->assertCanNotSeeTableRecords([$received]);
        Livewire::test(ListOrders::class, ['activeTab' => 'cancelled'])->assertCanSeeTableRecords([$cancelled])->assertCanNotSeeTableRecords([$received]);
    }

    public function test_a_picker_sees_the_orders_band_without_the_sales(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        Livewire::test(OrdersOverview::class)->assertSeeText('À traiter')->assertDontSeeText('Ventes du jour');
    }

    public function test_the_payments_and_customers_bands(): void
    {
        $this->order(OrderStatus::Delivered);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(PaymentsOverview::class)->assertSeeText('Paiements en ligne')->assertSeeText('À rembourser')->assertSeeText('Aucun remboursement en attente');
        Livewire::test(CustomersOverview::class)->assertSeeText('Clients')->assertSeeText('Nouveaux ce mois')->assertSeeText('Clients fidèles');
        Livewire::test(ListCustomers::class, ['activeTab' => 'guests'])->assertOk();
        Livewire::test(ListCustomers::class, ['activeTab' => 'loyal'])->assertOk();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(OrderStatus $status, array $attributes = []): Order
    {
        return Order::forceCreate([
            'number' => 'KM-261007-'.str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT), 'status' => $status,
            'payment_method' => PaymentMethod::CashOnDelivery, 'payment_status' => PaymentStatus::Pending, 'source' => 'web',
            'customer_name' => 'Client', 'phone' => '+22507010203'.str_pad((string) (Order::count() + 1), 2, '0', STR_PAD_LEFT),
            'commune_name' => 'Cocody', 'zone_name' => 'Zone 1', 'district' => 'Riviera', 'subtotal' => 10000, 'shipping_fee' => 0,
            'discount' => 0, 'total' => 10000, 'marketing_opt_in' => false, 'terms_accepted_at' => now(), ...$attributes,
        ]);
    }
}
