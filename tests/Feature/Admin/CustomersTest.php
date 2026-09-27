<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CustomersTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create(['name' => 'Gestionnaire']);
        $this->actingAs($this->manager);
    }

    public function test_accounts_and_guests_grouped_by_phone_are_listed_with_their_spending(): void
    {
        $awa = User::factory()->create(['name' => 'Awa Koné', 'phone' => '+2250701020304']);
        $this->order(['user_id' => $awa->id, 'phone' => '+2250701020304', 'total' => 30000], paid: true);
        // Placed as a guest with the account's phone: it belongs to Awa.
        $this->order(['phone' => '+2250701020304', 'total' => 12000]);

        $this->order(['customer_name' => 'Yao Kouassi', 'phone' => '+2250505050505', 'total' => 20000], paid: true);
        $this->order(['customer_name' => 'Yao Kouassi', 'phone' => '+2250505050505', 'total' => 8000], paid: true);

        $customers = Customer::orderBy('name')->get();

        $this->assertSame(['Awa Koné', 'Yao Kouassi'], $customers->pluck('name')->all());
        $this->assertSame([[2, 30000, false], [2, 28000, true]], $customers->map(fn (Customer $customer) => [$customer->orders_count, $customer->total_spent, $customer->isGuest()])->all());
        $this->assertLessThan(0, $customers[1]->id);

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords($customers)
            ->assertDontSeeText('Gestionnaire')
            ->searchTable('05 05 05 05 05')
            ->assertCanSeeTableRecords([$customers[1]])
            ->assertCanNotSeeTableRecords([$customers[0]])
            ->searchTable('Awa')
            ->assertCanSeeTableRecords([$customers[0]])
            ->assertCanNotSeeTableRecords([$customers[1]]);
    }

    public function test_the_customer_page_shows_details_addresses_and_orders(): void
    {
        $awa = User::factory()->create(['name' => 'Awa Koné', 'phone' => '+2250701020304', 'email' => 'awa@exemple.ci']);
        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $awa->addresses()->create(['label' => 'Maison', 'recipient_name' => 'Awa Koné', 'phone' => '0701020304', 'commune_id' => $zone->communes()->create(['name' => 'Cocody'])->id, 'district' => 'Riviera 2', 'is_default' => true]);
        $order = $this->order(['user_id' => $awa->id, 'phone' => '+2250701020304', 'total' => 30000], paid: true);

        $this->get(CustomerResource::getUrl('view', ['record' => $awa->id]))
            ->assertOk()
            ->assertSeeText('awa@exemple.ci')
            ->assertSeeText('07 01 02 03 04')
            ->assertSeeText('Maison')
            ->assertSeeText('Riviera 2')
            ->assertSeeText($order->number)
            ->assertSeeText("30\u{00A0}000\u{00A0}FCFA");

        $guest = $this->order(['customer_name' => 'Yao Kouassi', 'phone' => '+2250505050505']);
        Livewire::test(ViewCustomer::class, ['record' => -$guest->id])
            ->assertSeeText('Invité (sans compte)')
            ->assertSeeText($guest->number)
            ->assertDontSeeText('Adresses');
    }

    public function test_the_csv_export_is_recorded_in_the_audit_log(): void
    {
        $this->order(['customer_name' => 'Yao Kouassi', 'phone' => '+2250505050505', 'total' => 20000], paid: true);

        Livewire::test(ListCustomers::class)
            ->callAction('export')
            ->assertFileDownloaded(contentType: 'text/csv; charset=UTF-8');

        $log = Activity::where('description', 'Export CSV des clients')->sole();
        $this->assertTrue($log->causer->is($this->manager));
        $this->assertSame(1, $log->properties['lignes']);
    }

    public function test_pickers_do_not_see_customers(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        $this->get(CustomerResource::getUrl('index'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(array $attributes, bool $paid = false): Order
    {
        static $number = 0;
        $number++;

        return Order::create([
            'number' => sprintf('KM-261001-%04d', $number),
            'status' => $paid ? OrderStatus::Delivered : OrderStatus::Received,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => $paid ? PaymentStatus::Paid : PaymentStatus::Pending,
            'customer_name' => 'Awa Koné',
            'commune_name' => 'Cocody',
            'zone_name' => 'Zone 1',
            'district' => 'Riviera',
            'subtotal' => $attributes['total'] ?? 10000,
            'shipping_fee' => 0,
            'total' => 10000,
            'terms_accepted_at' => now(),
            ...$attributes,
        ]);
    }
}
