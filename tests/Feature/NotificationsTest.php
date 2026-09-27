<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Enums\StockMovementReason;
use App\Models\Cart;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\LowStockForStaff;
use App\Notifications\NewOrderForStaff;
use App\Notifications\OrderUpdateForCustomer;
use App\Services\Catalog\StockManager;
use App\Services\Checkout\PlaceOrder;
use App\Services\Orders\OrderStatusManager;
use App\Services\Sms\SmsGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $picker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create(['email' => 'gestion@kovamarket.ci']);
        $this->picker = User::factory()->staff(Role::Picker)->create();
    }

    public function test_a_new_order_notifies_the_customer_by_sms_and_the_order_staff(): void
    {
        Notification::fake();

        $order = $this->placeOrder(email: null);

        Notification::assertSentOnDemand(
            OrderUpdateForCustomer::class,
            fn (OrderUpdateForCustomer $notification, array $channels, AnonymousNotifiable $notifiable) => $notification->event === OrderUpdateForCustomer::PLACED
                && $notifiable->routes['sms'] === '+2250701020304'
                && $channels === [SmsChannel::class],
        );
        Notification::assertSentTo($this->manager, NewOrderForStaff::class);
        Notification::assertSentTo($this->manager, DatabaseNotification::class);
        Notification::assertNotSentTo($this->picker, NewOrderForStaff::class);
        $this->assertSame('KM', substr($order->number, 0, 2));
    }

    public function test_the_customer_gets_the_email_too_when_they_gave_one(): void
    {
        Notification::fake();

        $this->placeOrder(email: 'client@example.ci');

        Notification::assertSentOnDemand(
            OrderUpdateForCustomer::class,
            fn ($notification, array $channels) => $channels === [SmsChannel::class, 'mail'],
        );
    }

    public function test_each_step_follows_the_notification_table_and_corrections_stay_silent(): void
    {
        $order = $this->placeOrder(email: 'client@example.ci');
        Notification::fake();
        $statuses = app(OrderStatusManager::class);

        $statuses->move($order, OrderStatus::Confirmed, $this->manager);
        $statuses->move($order, OrderStatus::Preparing, $this->manager);

        $sent = collect(Notification::sentNotifications()[AnonymousNotifiable::class] ?? [])->flatten(2)->pluck('channels');
        $this->assertEquals([[SmsChannel::class, 'mail'], ['mail']], $sent->values()->all());

        Notification::fake();
        $statuses->rollBack($order, User::factory()->staff(Role::SuperAdmin)->create(), 'Erreur');
        $this->assertSame([], Notification::sentNotifications()[AnonymousNotifiable::class] ?? []);
    }

    public function test_a_cancellation_alerts_the_back_office(): void
    {
        $order = $this->placeOrder(email: null);
        Notification::fake();

        app(OrderStatusManager::class)->move($order, OrderStatus::Cancelled, $this->manager, 'Client injoignable');

        Notification::assertSentOnDemand(OrderUpdateForCustomer::class, fn ($notification) => $notification->event === 'annulee');
        Notification::assertSentTo($this->manager, DatabaseNotification::class);
    }

    public function test_sms_texts_go_through_the_configured_gateway(): void
    {
        $gateway = new class implements SmsGateway
        {
            /** @var list<array{string, string}> */
            public array $sent = [];

            public function send(string $phone, string $message): void
            {
                $this->sent[] = [$phone, $message];
            }
        };
        $this->app->instance(SmsGateway::class, $gateway);

        $order = $this->placeOrder(email: null);

        $this->assertSame('+2250701020304', $gateway->sent[0][0]);
        $this->assertStringContainsString("commande {$order->number} reçue", $gateway->sent[0][1]);
        $this->assertStringContainsString("45\u{00A0}000\u{00A0}FCFA", $gateway->sent[0][1]);
    }

    public function test_the_low_stock_alert_is_sent_once_when_the_threshold_is_crossed(): void
    {
        Notification::fake();
        $variant = Product::factory()->create(['stock' => 5])->defaultVariant;
        $stock = app(StockManager::class);

        $stock->adjust($variant, -1, StockMovementReason::Adjustment); // 4: above the threshold of 3
        $stock->adjust($variant, -1, StockMovementReason::Adjustment); // 3: crosses it
        $stock->adjust($variant, -1, StockMovementReason::Adjustment); // 2: already under

        Notification::assertSentToTimes($this->manager, LowStockForStaff::class, 1);
        Notification::assertNotSentTo($this->picker, LowStockForStaff::class);
    }

    private function placeOrder(?string $email): Order
    {
        $commune = DeliveryZone::firstOrCreate(['name' => 'Zone 1'], ['fee' => 0, 'is_active' => true])->communes()->firstOrCreate(['name' => 'Cocody']);
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => Product::factory()->create(['price' => 45000, 'stock' => 20])->defaultVariant->id, 'quantity' => 1]);

        return app(PlaceOrder::class)->handle($cart, [
            'customer_name' => 'Koffi Yao', 'phone' => '+2250701020304', 'email' => $email, 'commune_id' => $commune->id,
            'district' => 'Riviera', 'landmark' => null, 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
        ]);
    }
}
