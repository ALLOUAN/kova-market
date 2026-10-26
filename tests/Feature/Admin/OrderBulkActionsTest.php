<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ventes › Commandes: deleting a selection (cancelled orders only, into the trash), restoring it, the "Traitement"
 * filter and the colour of handled orders.
 */
class OrderBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_only_cancelled_orders_of_a_selection_are_deleted_and_they_can_be_restored(): void
    {
        $cancelled = $this->order(OrderStatus::Cancelled);
        $delivered = $this->order(OrderStatus::Delivered);
        $received = $this->order(OrderStatus::Received);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(ListOrders::class)
            ->selectTableRecords([$cancelled, $delivered, $received])
            ->callAction(TestAction::make('deleteSelected')->table()->bulk())
            ->assertNotified('1 commande(s) supprimée(s)');

        $this->assertSoftDeleted($cancelled);
        $this->assertNotSoftDeleted($delivered);
        $this->assertNotSoftDeleted($received);

        Livewire::test(ListOrders::class)
            ->assertCanNotSeeTableRecords([$cancelled])
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$cancelled])
            ->assertCanNotSeeTableRecords([$delivered])
            ->selectTableRecords([$cancelled])
            ->callAction(TestAction::make('restoreSelected')->table()->bulk());

        $this->assertNotSoftDeleted($cancelled);
    }

    public function test_a_picker_sees_the_orders_but_cannot_delete_them(): void
    {
        $this->order(OrderStatus::Cancelled);
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        Livewire::test(ListOrders::class)
            ->assertActionHidden(TestAction::make('deleteSelected')->table()->bulk())
            ->assertActionHidden(TestAction::make('restoreSelected')->table()->bulk());
    }

    public function test_handled_orders_are_filtered_and_coloured(): void
    {
        $delivered = $this->order(OrderStatus::Delivered);
        $cancelled = $this->order(OrderStatus::Cancelled);
        $preparing = $this->order(OrderStatus::Preparing);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(ListOrders::class)
            ->assertSeeHtml('kova-order-row--delivered')
            ->assertSeeHtml('kova-order-row--cancelled')
            ->filterTable('handled', true)
            ->assertCanSeeTableRecords([$delivered, $cancelled])
            ->assertCanNotSeeTableRecords([$preparing])
            ->filterTable('handled', false)
            ->assertCanSeeTableRecords([$preparing])
            ->assertCanNotSeeTableRecords([$delivered, $cancelled]);
    }

    private function order(OrderStatus $status): Order
    {
        static $number = 0;
        $number++;

        return Order::create([
            'number' => sprintf('KM-261026-%04d', $number),
            'status' => $status,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => $status === OrderStatus::Delivered ? PaymentStatus::Paid : PaymentStatus::Pending,
            'customer_name' => 'Awa Koné',
            'phone' => '+2250701020304',
            'commune_name' => 'Cocody',
            'zone_name' => 'Zone 1',
            'district' => 'Riviera',
            'subtotal' => 10000,
            'shipping_fee' => 0,
            'total' => 10000,
            'terms_accepted_at' => now(),
        ]);
    }
}
