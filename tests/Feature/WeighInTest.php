<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\SaleUnit;
use App\Enums\StockMovementReason;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\WeighInForCustomer;
use App\Services\Cart\CartManager;
use App\Services\Orders\OrderReceipt;
use App\Services\Orders\WeighIn;
use App\Services\Orders\WeighInException;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Weigh-in at preparation (Mon Marché): the weighed quantity of a line sold by weight is billed instead of the
 * ordered one, within the tolerance, for orders paid on delivery before they leave.
 */
class WeighInTest extends TestCase
{
    use RefreshDatabase;

    private Commune $cocody;

    private User $picker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 500, 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
        $this->picker = User::factory()->staff(Role::Picker)->create();
    }

    public function test_the_order_is_billed_on_the_weighed_quantity(): void
    {
        [$order, $tomatoes] = $this->orderTomatoes(1500);
        $line = $order->items->sole();

        $order = app(WeighIn::class)->record($order, [$line->id => 1620], $this->picker);

        $line = $order->items->sole();
        $this->assertSame([1620, 1500, 1620], [$line->quantity, $line->ordered_quantity, $line->line_total]);
        $this->assertSame([1620, 500, 2120], [$order->subtotal, $order->shipping_fee, $order->total]);
        $this->assertSame('Pesé : 1,62 kg (commandé : 1,5 kg)', $line->weighNote());

        // 120 g more out of the stock, noted "Pesée".
        $variant = $tomatoes->defaultVariant->fresh();
        $this->assertSame(10_000 - 1620, $variant->stock);
        $movement = $variant->stockMovements()->latest('id')->first();
        $this->assertSame([-120, StockMovementReason::Sale, "Pesée {$order->number}"], [$movement->quantity, $movement->reason, $movement->note]);

        // Weighed again, lighter: the difference goes back on sale; the ordered quantity stays the customer's.
        $order = app(WeighIn::class)->record($order, [$line->id => 1400], $this->picker);
        $this->assertSame([1400, 1500, 1900], [$order->items->sole()->quantity, $order->items->sole()->ordered_quantity, $order->total]);
        $this->assertSame(10_000 - 1400, $variant->fresh()->stock);

        $this->assertStringContainsString('Pesé : 1,4 kg (commandé : 1,5 kg)', app(OrderReceipt::class)->html($order));
    }

    public function test_the_customer_is_told_the_new_total(): void
    {
        [$order] = $this->orderTomatoes(1500);
        $line = $order->items->sole();
        Notification::fake();

        $order = app(WeighIn::class)->record($order, [$line->id => 1620], $this->picker);

        Notification::assertSentOnDemand(WeighInForCustomer::class, function (WeighInForCustomer $notification, array $channels, object $notifiable) use ($order) {
            $sms = $notification->toSms($notifiable);

            return $notifiable->routes['sms'] === '+2250701020304'
                && str_contains($sms, "Nouveau total : 2\u{00A0}120\u{00A0}FCFA (au lieu de 2\u{00A0}000\u{00A0}FCFA)")
                && str_contains($sms, "/commande/{$order->number}/recu?signature=");
        });

        // Weighed again at the same weight: nothing new to tell.
        Notification::fake();
        app(WeighIn::class)->record($order, [$line->id => 1620], $this->picker);
        Notification::assertNothingSent();
    }

    public function test_the_weight_stays_within_the_tolerance(): void
    {
        [$order] = $this->orderTomatoes(1500);
        $line = $order->items->sole();

        // 10 % by default: from 1,35 to 1,65 kg.
        $this->assertSame([1350, 1650], app(WeighIn::class)->bounds($line));

        try {
            app(WeighIn::class)->record($order, [$line->id => 2000], $this->picker);
            $this->fail('A weight outside the tolerance must be refused.');
        } catch (WeighInException $exception) {
            $this->assertSame('« Tomates » : entre 1,35 kg et 1,65 kg (écart de 10 % au plus avec la commande).', $exception->getMessage());
        }

        $this->assertSame(1500, $line->fresh()->quantity);

        Setting::store(['orders.weigh_tolerance' => '0']);
        $this->assertFalse(app(WeighIn::class)->applies($order->fresh()));
    }

    public function test_orders_paid_online_or_gone_are_not_weighed(): void
    {
        [$order] = $this->orderTomatoes(1500);

        $order->forceFill(['payment_method' => PaymentMethod::Online, 'payment_status' => PaymentStatus::Paid])->save();
        $this->assertFalse(app(WeighIn::class)->applies($order->fresh()));

        $order->forceFill(['payment_method' => PaymentMethod::CashOnDelivery, 'payment_status' => PaymentStatus::Pending, 'status' => OrderStatus::Shipped])->save();
        $this->assertFalse(app(WeighIn::class)->applies($order->fresh()));

        $this->expectException(WeighInException::class);
        app(WeighIn::class)->record($order->fresh(), [$order->items->sole()->id => 1500], $this->picker);
    }

    public function test_the_picker_weighs_from_the_order_page(): void
    {
        [$order] = $this->orderTomatoes(1500);
        $line = $order->items->sole();
        $this->actingAs($this->picker);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionVisible('weighIn')
            ->callAction('weighIn', ['weighed' => [$line->id => 1.55]])
            ->assertHasNoActionErrors();

        $this->assertSame([1550, 2050], [$line->fresh()->quantity, $order->fresh()->total]);

        // A piece product has nothing to weigh.
        $piece = Product::factory()->create(['price' => 4500, 'stock' => 5]);
        $this->post('/panier/articles', ['product_id' => $piece->id]);
        $this->withCookie(CartManager::COOKIE, Cart::latest('id')->first()->token);
        $this->post('/commande', $this->details());
        $pieceOrder = Order::latest('id')->first();
        Livewire::test(ViewOrder::class, ['record' => $pieceOrder->getRouteKey()])->assertActionHidden('weighIn');
    }

    /**
     * @return array{0: Order, 1: Product}
     */
    private function orderTomatoes(int $grams): array
    {
        $tomatoes = Product::factory()->create(['name' => 'Tomates', 'price' => 1000, 'stock' => 10_000, 'sale_unit' => SaleUnit::Kilogram]);

        $this->post('/panier/articles', ['product_id' => $tomatoes->id, 'quantity' => $grams]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
        $this->post('/commande', $this->details());

        return [Order::sole()->load('items'), $tomatoes];
    }

    /**
     * @return array<string, mixed>
     */
    private function details(): array
    {
        return [
            'customer_name' => 'Awa Koné', 'phone' => '0701020304', 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'landmark' => null, 'payment_method' => 'paiement_livraison', 'terms' => '1',
        ];
    }
}
