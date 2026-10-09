<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DashboardOverview;
use App\Filament\Widgets\OrdersByStatusChart;
use App\Filament\Widgets\OrdersToHandle;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\TopProducts;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\SalesFigures;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The back-office home: what needs doing, then the sales figures, for the roles allowed to see them.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(15, 0));
    }

    public function test_sales_count_orders_paid_or_to_pay_on_delivery_but_not_cancelled_or_unpaid_online(): void
    {
        $this->order(10_000);
        $this->order(20_000, ['payment_method' => PaymentMethod::Online, 'payment_status' => PaymentStatus::Paid]);
        $this->order(99_000, ['status' => OrderStatus::Cancelled]);
        $this->order(99_000, ['payment_method' => PaymentMethod::Online, 'payment_status' => PaymentStatus::Pending]);
        $this->order(5_000, ['created_at' => now()->subDay()]);

        $figures = app(SalesFigures::class);
        $this->assertSame([30_000, 2], [$figures->revenue(today(), now()), $figures->count(today(), now())]);
        $this->assertSame(3, $figures->toHandle());

        $days = $figures->daily(7);
        $this->assertCount(7, $days);
        $this->assertSame(['revenue' => 30_000, 'orders' => 2], $days[today()->toDateString()]);
        $this->assertSame(['revenue' => 5_000, 'orders' => 1], $days[today()->subDay()->toDateString()]);
    }

    public function test_the_manager_sees_the_day_at_a_glance(): void
    {
        $this->order(30_000);
        $this->order(10_000, ['created_at' => now()->subDay()]);
        $this->actingAs(User::factory()->staff(Role::Manager)->create(['name' => 'Awa Koné']));

        $this->get(Dashboard::getUrl())->assertOk()->assertSeeText('Tableau de bord')->assertSeeText('Ajouter un produit')->assertSeeText('Voir la boutique');

        Livewire::test(DashboardOverview::class)
            ->assertSeeText('Bonjour Awa')
            ->assertSeeText("30\u{00A0}000\u{00A0}FCFA")
            ->assertSeeText("Hier : 10\u{00A0}000\u{00A0}FCFA · +200 %")
            ->assertSeeText('À faire maintenant')
            ->assertSeeText('2 commandes à traiter');
        Livewire::test(TopProducts::class)->assertSeeText('Meilleures ventes');
        Livewire::test(RevenueChart::class)->assertSeeText('Chiffre d’affaires');
        Livewire::test(OrdersByStatusChart::class)->assertOk();
        Livewire::test(OrdersToHandle::class)->assertCanSeeTableRecords(Order::all());
    }

    public function test_a_picker_sees_the_orders_to_handle_but_not_the_sales(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        $this->assertTrue(OrdersToHandle::canView());
        $this->assertFalse(TopProducts::canView());
        $this->assertFalse(RevenueChart::canView());
        $this->get(Dashboard::getUrl())->assertOk()->assertDontSeeText('Ajouter un produit')->assertDontSeeText('Chiffre d’affaires du jour')->assertSeeText('À faire maintenant');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(int $total, array $attributes = []): Order
    {
        return Order::forceCreate([
            'number' => 'KM-261001-'.str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT), 'status' => OrderStatus::Received,
            'payment_method' => PaymentMethod::CashOnDelivery, 'payment_status' => PaymentStatus::Pending, 'source' => 'web',
            'customer_name' => 'Client', 'phone' => '+2250701020304', 'commune_name' => 'Cocody', 'zone_name' => 'Zone 1',
            'district' => 'Riviera', 'subtotal' => $total, 'shipping_fee' => 0, 'discount' => 0, 'total' => $total,
            'marketing_opt_in' => false, 'terms_accepted_at' => now(), ...$attributes,
        ]);
    }
}
