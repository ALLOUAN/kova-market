<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\RemittanceMethod;
use App\Enums\Role;
use App\Filament\Resources\Couriers\Pages\EditCourier;
use App\Filament\Resources\Couriers\Pages\ListCouriers;
use App\Filament\Resources\Couriers\RelationManagers\RemittancesRelationManager;
use App\Filament\Resources\Couriers\Widgets\CourierFinanceHistory;
use App\Filament\Resources\Couriers\Widgets\CourierFinanceOverview;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\Courier;
use App\Models\CourierRemittance;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\PlaceOrder;
use App\Services\Delivery\CashSettlement;
use App\Services\Delivery\CourierFinances;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Delivery\RemittanceException;
use App\Services\Delivery\RemittanceReceipt;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Couriers' remittances (F-126): partial payments covering the oldest orders first, cancellation, receipt, the
 * courier's money on their page, and the reason asked when the cash collected differs from the amount due.
 */
class CourierRemittanceTest extends TestCase
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

    public function test_a_partial_payment_covers_the_oldest_orders_first_and_leaves_the_rest_due(): void
    {
        $first = $this->delivered(cash: 25500);
        $second = $this->delivered(cash: 10000);
        $settlement = app(CashSettlement::class);
        $this->assertSame(35500, $settlement->due($this->courier));

        $remittance = $settlement->record($this->courier, 30000, RemittanceMethod::MobileMoney, $this->manager, reference: 'MP2610.1234');

        $this->assertSame(5500, $settlement->due($this->courier));
        $this->assertSame(5500, $remittance->balance_after);
        $first->refresh();
        $second->refresh();
        $this->assertSame([25500, true], [$first->cash_remitted, $first->cash_settled_at !== null]);
        $this->assertSame([4500, null], [$second->cash_remitted, $second->cash_settled_at]);
        $this->assertSame([25500, 4500], $remittance->orders()->orderBy('orders.id')->pluck('courier_remittance_order.amount')->all());

        // The rest settles the second order.
        $settlement->record($this->courier, 5500, RemittanceMethod::Cash, $this->manager);
        $this->assertSame(0, $settlement->due($this->courier));
        $this->assertTrue($second->fresh()->cashSettledBy->is($this->manager));
    }

    public function test_a_payment_cannot_exceed_what_the_courier_owes(): void
    {
        $this->delivered(cash: 10000);

        $this->expectException(RemittanceException::class);
        app(CashSettlement::class)->record($this->courier, 10001, RemittanceMethod::Cash, $this->manager);
    }

    public function test_cancelling_a_payment_makes_its_orders_owe_their_cash_again_and_keeps_it_in_the_history(): void
    {
        $order = $this->delivered(cash: 25500);
        $settlement = app(CashSettlement::class);
        $remittance = $settlement->record($this->courier, 25500, RemittanceMethod::Cash, $this->manager);
        $this->assertSame(0, $settlement->due($this->courier));

        $settlement->cancel($remittance, 'Billets comptés deux fois', $this->manager);

        $this->assertSame(25500, $settlement->due($this->courier));
        $order->refresh();
        $this->assertSame([0, null], [$order->cash_remitted, $order->cash_settled_at]);
        $remittance->refresh();
        $this->assertTrue($remittance->isCancelled());
        $this->assertSame('Billets comptés deux fois', $remittance->cancel_reason);
        $this->assertSame(1, CourierRemittance::count());

        $this->expectException(RemittanceException::class);
        $settlement->cancel($remittance, 'Encore', $this->manager);
    }

    public function test_the_manager_records_a_partial_payment_and_cancels_it_from_the_courier_page(): void
    {
        $this->delivered(cash: 25500);
        $this->actingAs($this->manager);

        Livewire::test(ListCouriers::class)
            ->callAction(TestAction::make('settleCash')->table($this->courier), ['amount' => 20000, 'method' => RemittanceMethod::Cash->value, 'reference' => 'Caisse 1'])
            ->assertHasNoActionErrors();

        $remittance = CourierRemittance::sole();
        $this->assertSame([20000, 'Caisse 1', 5500], [$remittance->amount, $remittance->reference, $remittance->balance_after]);
        $this->assertTrue($remittance->receivedBy->is($this->manager));

        $page = ['ownerRecord' => $this->courier, 'pageClass' => EditCourier::class];
        Livewire::test(RemittancesRelationManager::class, $page)
            ->assertCanSeeTableRecords([$remittance])
            ->callAction(TestAction::make('cancel')->table($remittance), ['reason' => 'Erreur de saisie'])
            ->assertHasNoActionErrors();

        $this->assertTrue($remittance->fresh()->isCancelled());
        $this->assertSame(25500, app(CashSettlement::class)->due($this->courier));

        // More than what is due is refused by the form.
        Livewire::test(ListCouriers::class)
            ->callAction(TestAction::make('settleCash')->table($this->courier), ['amount' => 30000, 'method' => RemittanceMethod::Cash->value])
            ->assertHasActionErrors(['amount']);
    }

    public function test_the_receipt_lists_the_orders_covered(): void
    {
        $order = $this->delivered(cash: 25500);
        $remittance = app(CashSettlement::class)->record($this->courier, 20000, RemittanceMethod::Cash, $this->manager);

        $html = app(RemittanceReceipt::class)->html($remittance);

        $this->assertStringContainsString('Reçu de versement', $html);
        $this->assertStringContainsString($remittance->number(), $html);
        $this->assertStringContainsString($order->number, $html);
        $this->assertStringContainsString('Moussa Traoré', $html);
        $this->assertStringContainsString('Reste dû après ce versement', $html);
        $this->assertStringStartsWith('%PDF', app(RemittanceReceipt::class)->pdf($remittance));
    }

    public function test_the_courier_page_shows_their_money_and_its_history(): void
    {
        $order = $this->delivered(cash: 25500);
        app(CashSettlement::class)->record($this->courier, 20000, RemittanceMethod::Cash, $this->manager);

        $statement = app(CourierFinances::class)->statement($this->courier);
        $this->assertSame(1, $statement['delivered']);
        $this->assertSame($order->total, $statement['orders_total']);
        $this->assertSame(25500, $statement['collected']);
        $this->assertSame(1500, $statement['shipping_fees']);
        $this->assertSame(20000, $statement['remitted']);
        $this->assertSame(5500, $statement['due']);

        $this->assertSame([0, 0], array_values(array_intersect_key(
            app(CourierFinances::class)->statement($this->courier, now()->addDay(), now()->addDays(2)),
            ['delivered' => 0, 'remitted' => 0],
        )));

        $this->actingAs($this->manager);
        Livewire::test(CourierFinanceOverview::class, ['record' => $this->courier])
            ->assertSeeText('Reste à reverser')
            ->assertSeeText('Déjà reversé');
        Livewire::test(CourierFinanceHistory::class, ['record' => $this->courier])
            ->assertSeeText("Encaissé à la livraison de {$order->number}")
            ->assertSeeText('Versement VER-');
        $this->get(EditCourier::getUrl(['record' => $this->courier]))->assertOk()->assertSeeText('Versements');
    }

    public function test_a_picker_cannot_see_the_finances_nor_record_a_payment(): void
    {
        $picker = User::factory()->staff(Role::Picker)->create();

        $this->assertFalse($picker->can('finances.consulter'));
        $this->assertFalse($picker->can('finances.versements'));
        $this->assertTrue($this->manager->can('finances.versements'));
        $this->actingAs($picker);
        $this->assertFalse(CourierFinanceOverview::canView());
        $this->assertFalse(RemittancesRelationManager::canViewForRecord($this->courier, EditCourier::class));
    }

    public function test_the_courier_gives_a_reason_when_the_cash_collected_differs_and_the_store_is_alerted(): void
    {
        $order = $this->outForDelivery();
        $this->actingAs($this->courierUser);

        $this->post("/livreur/commandes/{$order->number}/livree", ['cash_collected' => $order->total - 500])
            ->assertSessionHasErrors('cash_note');
        $this->assertSame(OrderStatus::OutForDelivery, $order->fresh()->status);

        $this->post("/livreur/commandes/{$order->number}/livree", ['cash_collected' => $order->total - 500, 'cash_note' => 'Pas de monnaie'])
            ->assertRedirect('/livreur');

        $order->refresh();
        $this->assertSame([OrderStatus::Delivered, $order->total - 500, 'Pas de monnaie'], [$order->status, $order->cash_collected, $order->cash_note]);
        $alert = $this->cashAlerts()->sole();
        $this->assertStringContainsString("Encaissement différent pour {$order->number}", $alert->data['title']);
        $this->assertStringContainsString('Pas de monnaie', $alert->data['body']);
    }

    public function test_the_exact_amount_needs_no_reason(): void
    {
        $order = $this->outForDelivery();
        $this->actingAs($this->courierUser);

        $this->post("/livreur/commandes/{$order->number}/livree", ['cash_collected' => $order->total])->assertRedirect('/livreur');

        $this->assertNull($order->fresh()->cash_note);
        $this->assertSame(0, $this->cashAlerts()->count());
    }

    private function cashAlerts(): Collection
    {
        return $this->manager->notifications()->get()->filter(fn ($notification) => str_starts_with($notification->data['title'] ?? '', 'Encaissement différent'));
    }

    private function delivered(int $cash): Order
    {
        $order = $this->outForDelivery();
        $order->forceFill(['cash_collected' => $cash])->save();
        app(OrderStatusManager::class)->move($order, OrderStatus::Delivered, $this->courierUser);

        return $order;
    }

    private function outForDelivery(): Order
    {
        $product = Product::factory()->create(['price' => 24000, 'stock' => 5]);
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => $product->defaultVariant->id, 'quantity' => 1]);
        $order = app(PlaceOrder::class)->handle($cart, [
            'customer_name' => 'Awa Koné', 'phone' => '+2250701020304', 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'landmark' => null, 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
        ]);

        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::Confirmed, $this->manager);
        app(DeliveryDispatcher::class)->assign($order, $this->courier);
        $statuses->move($order, OrderStatus::Preparing, $this->manager);
        $statuses->move($order, OrderStatus::OutForDelivery, $this->courierUser);

        return $order->fresh();
    }
}
