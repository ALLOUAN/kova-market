<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\StockMovementReason;
use App\Enums\TransactionStatus;
use App\Events\OrderPlaced;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Widgets\PaymentsOverview;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Orders\OrderStatusManager;
use App\Services\Payments\OnlinePayments;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Online payment through CinetPay (F-060 to F-067), CinetPay's API faked as documented: OAuth token, payment
 * initiation, status check. Journey: product → cart → order → initiation → redirection → notification → status
 * check → order paid → confirmation.
 */
class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://api.cinetpay.test';

    private Commune $cocody;

    private Product $product;

    /** What the faked status check answers: [code, status] (null = CinetPay unreachable). */
    private ?array $cinetPayStatus = [2001, 'INITIATED'];

    private int $initiations = 0;

    private bool $refuseInitiation = false;

    /** The status check answers about another transaction when set. */
    private ?string $statusAboutAnotherTransaction = null;

    /** The next status check answers "token expired" (1003) this many times. */
    private int $expiredTokenAnswers = 0;

    private int $logins = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.cinetpay' => ['api_key' => 'test-key', 'api_password' => 'test-password', 'base_url' => self::API, 'currency' => 'XOF', 'fallback_email' => 'paiements@kova.test']]);

        $this->cocody = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true])->communes()->create(['name' => 'Cocody']);
        $this->product = Product::factory()->create(['name' => 'Enceinte JBL', 'price' => 45000, 'stock' => 10]);

        Http::fake([
            self::API.'/v1/oauth/login' => function () {
                $this->logins++;

                return Http::response(['code' => 200, 'status' => 'OK', 'access_token' => 'jwt-token-'.$this->logins, 'expires_in' => 86400]);
            },
            self::API.'/v1/payment' => function (Request $request) {
                if ($this->refuseInitiation) {
                    return Http::response(['code' => 200, 'status' => 'OK', 'details' => ['code' => 1004, 'status' => 'INVALID_PARAMS', 'message' => 'Invalid params']]);
                }

                $this->initiations++;

                return Http::response([
                    'code' => 200, 'status' => 'OK',
                    'payment_token' => 'pay-token-'.$this->initiations,
                    'notify_token' => 'notify-secret-'.$this->initiations,
                    'transaction_id' => 'cp-tx-'.$this->initiations,
                    'merchant_transaction_id' => $request['merchant_transaction_id'],
                    'payment_url' => 'https://secure.cinetpay.net/payment/pay-token-'.$this->initiations,
                    'details' => ['code' => 2001, 'status' => 'INITIATED', 'must_be_redirected' => true],
                ]);
            },
            self::API.'/v1/payment/*' => function (Request $request) {
                if ($this->cinetPayStatus === null) {
                    throw new ConnectionException('CinetPay down');
                }

                if ($this->expiredTokenAnswers > 0) {
                    $this->expiredTokenAnswers--;

                    return Http::response(['code' => 1003, 'status' => 'EXPIRED_TOKEN']);
                }

                $payment = Payment::where('payment_token', basename($request->url()))->first();

                return Http::response([
                    'code' => $this->cinetPayStatus[0], 'status' => $this->cinetPayStatus[1],
                    'merchant_transaction_id' => $this->statusAboutAnotherTransaction ?? $payment?->merchant_transaction_id,
                    'transaction_id' => $payment?->gateway_transaction_id,
                    'payment_method' => 'OM',
                    'user' => ['name' => 'Awa Kone', 'email' => 'awa@example.ci', 'phone_number' => '+2250701020304'],
                ]);
            },
        ]);
    }

    public function test_online_payment_is_offered_only_once_cinetpay_is_configured(): void
    {
        $this->addToCart();
        $this->get('/commande')->assertOk()->assertSeeText('Paiement en ligne')->assertSeeText('CinetPay');

        config(['services.cinetpay.api_key' => null]);

        $this->get('/commande')->assertOk()->assertDontSeeText('Paiement en ligne');
        $this->placeOrder()->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
    }

    public function test_the_whole_journey_from_the_cart_to_the_confirmed_payment(): void
    {
        Event::fake([OrderPlaced::class]);
        $this->addToCart(2);

        // The order is placed (stock reserved) and the customer sent to CinetPay's page.
        $this->placeOrder()->assertRedirect('https://secure.cinetpay.net/payment/pay-token-1');

        $order = Order::sole();
        $payment = Payment::sole();
        $this->assertSame([PaymentStatus::Pending, OrderStatus::Received], [$order->payment_status, $order->status]);
        $this->assertSame([TransactionStatus::Initiated, 91500, 'XOF'], [$payment->status, $payment->amount, $payment->currency]);
        $this->assertSame(8, $this->product->defaultVariant->fresh()->stock);
        Event::assertNotDispatched(OrderPlaced::class);

        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/v1/payment'
            && $request->hasHeader('Authorization', 'Bearer jwt-token-1')
            && $request['amount'] === 91500
            && $request['currency'] === 'XOF'
            && $request['merchant_transaction_id'] === $payment->merchant_transaction_id
            && strlen($request['merchant_transaction_id']) <= 30
            && $request['client_email'] === 'paiements@kova.test'
            && $request['client_phone_number'] === '+2250701020304'
            && $request['notify_url'] === route('payments.notify')
            && $request['success_url'] === route('payments.return', $payment)
            && strlen($request['success_url']) <= 120);

        // CinetPay notifies; the outcome is taken from its status check.
        $this->cinetPayStatus = [100, 'SUCCESS'];
        $this->postJson('/paiement/cinetpay/notification', ['notify_token' => 'notify-secret-1', 'merchant_transaction_id' => $payment->merchant_transaction_id, 'transaction_id' => 'cp-tx-1'])
            ->assertOk()->assertSee('OK');

        $payment->refresh();
        $this->assertSame([TransactionStatus::Succeeded, 'OM'], [$payment->status, $payment->operator]);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        Event::assertDispatchedTimes(OrderPlaced::class, 1);

        // A repeated notification changes nothing and announces nothing twice.
        $this->postJson('/paiement/cinetpay/notification', ['notify_token' => 'notify-secret-1', 'merchant_transaction_id' => $payment->merchant_transaction_id]);
        Event::assertDispatchedTimes(OrderPlaced::class, 1);

        // Back from CinetPay: the confirmation says it.
        $this->get(route('payments.return', $payment))->assertRedirect(route('checkout.confirmation', $order));
        $this->get(route('checkout.confirmation', $order))->assertOk()->assertSeeText('Paiement reçu : votre commande est enregistrée.');
    }

    public function test_a_notification_without_the_right_token_is_ignored(): void
    {
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [100, 'SUCCESS'];

        $this->postJson('/paiement/cinetpay/notification', ['notify_token' => 'forged', 'merchant_transaction_id' => $payment->merchant_transaction_id])->assertOk();
        $this->postJson('/paiement/cinetpay/notification', ['merchant_transaction_id' => $payment->merchant_transaction_id])->assertOk();

        $this->assertSame(TransactionStatus::Initiated, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->order->fresh()->payment_status);
        Http::assertNotSent(fn (Request $request) => str_starts_with($request->url(), self::API.'/v1/payment/'));
    }

    public function test_a_failed_payment_leaves_the_order_waiting_and_the_customer_can_try_again(): void
    {
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [2010, 'FAILED'];

        $this->get(route('payments.return', $payment))->assertRedirect(route('checkout.confirmation', $payment->order))->assertSessionHas('payment_error');

        $this->assertSame([TransactionStatus::Failed, 'CinetPay : FAILED'], [$payment->fresh()->status, $payment->fresh()->failure_reason]);
        $this->get(route('checkout.confirmation', $payment->order))->assertOk()->assertSeeText('Payer maintenant');

        $this->post(route('payments.pay', $payment->order))->assertRedirect('https://secure.cinetpay.net/payment/pay-token-2');
        $this->assertSame(2, $payment->order->payments()->count());
    }

    public function test_nothing_changes_while_cinetpay_cannot_confirm(): void
    {
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = null;

        $this->postJson('/paiement/cinetpay/notification', ['notify_token' => 'notify-secret-1', 'merchant_transaction_id' => $payment->merchant_transaction_id])->assertOk();
        $this->get(route('payments.return', $payment))->assertRedirect();

        $this->assertSame(TransactionStatus::Initiated, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->order->fresh()->payment_status);
    }

    public function test_a_refused_initiation_shows_the_reason_and_keeps_the_order_payable(): void
    {
        $this->refuseInitiation = true;
        $this->addToCart();

        $response = $this->placeOrder();

        $order = Order::sole();
        $response->assertRedirect(route('checkout.confirmation', $order))->assertSessionHas('payment_error');
        $this->assertSame(TransactionStatus::Failed, Payment::sole()->status);
        $this->assertTrue($order->awaitsOnlinePayment());
    }

    public function test_unpaid_orders_are_cancelled_after_the_timeout_and_their_stock_released(): void
    {
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [2001, 'INITIATED'];

        $this->travel(OnlinePayments::DEFAULT_TIMEOUT - 5)->minutes();
        $this->artisan('payments:expire-unpaid')->assertSuccessful();
        $this->assertSame(OrderStatus::Received, $payment->order->fresh()->status);

        $this->travel(10)->minutes();
        $this->artisan('payments:expire-unpaid')->assertSuccessful();

        $order = $payment->order->fresh();
        $this->assertSame([OrderStatus::Cancelled, PaymentStatus::Cancelled], [$order->status, $order->payment_status]);
        $this->assertSame(TransactionStatus::Cancelled, $payment->fresh()->status);
        $this->assertSame(10, $this->product->defaultVariant->fresh()->stock);
        $this->assertSame(StockMovementReason::Release, $this->product->defaultVariant->stockMovements()->first()->reason);
        $this->assertNull($order->statusHistory()->latest('id')->first()->user_id);
    }

    public function test_the_sweep_does_not_cancel_an_order_paid_meanwhile(): void
    {
        Event::fake([OrderPlaced::class]);
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [100, 'SUCCESS'];

        $this->travel(OnlinePayments::DEFAULT_TIMEOUT + 1)->minutes();
        $this->artisan('payments:expire-unpaid')->assertSuccessful();

        $this->assertSame([OrderStatus::Received, PaymentStatus::Paid], [$payment->order->fresh()->status, $payment->order->fresh()->payment_status]);
        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_cash_on_delivery_orders_are_never_expired(): void
    {
        $this->addToCart();
        $this->placeOrder(['payment_method' => 'paiement_livraison']);

        $this->travel(2)->hours();
        $this->artisan('payments:expire-unpaid');

        $this->assertSame(OrderStatus::Received, Order::sole()->status);
    }

    public function test_the_back_office_records_a_refund(): void
    {
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [100, 'SUCCESS'];
        app(OnlinePayments::class)->synchronize($payment, 'test');

        app(OnlinePayments::class)->recordRefund($payment->fresh(), User::factory()->create(), 'Produit indisponible');

        $this->assertSame(TransactionStatus::Refunded, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $payment->order->fresh()->payment_status);
    }

    public function test_another_browser_coming_back_sees_the_outcome_only(): void
    {
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [100, 'SUCCESS'];

        $this->flushSession();
        $this->withCookies([]);

        $this->get(route('payments.return', $payment))
            ->assertOk()
            ->assertSeeText($payment->order->number)
            ->assertSeeText('Paiement reçu')
            ->assertDontSeeText('Riviera 2')
            ->assertDontSeeText('0701020304');
    }

    public function test_amounts_are_rounded_up_to_five_francs_as_cinetpay_requires(): void
    {
        $this->product->defaultVariant->update(['price' => 45003]);
        $this->product->syncFromVariants();

        $payment = $this->orderPaidOnline();

        // 45 003 + 1 500 delivery = 46 503 FCFA ordered, 46 505 asked to CinetPay; the 2 francs are in the journal.
        $this->assertSame(46503, $payment->order->total);
        $this->assertSame(46505, $payment->amount);
        $this->assertSame(2, collect($payment->events)->firstWhere('event', 'initiated')['rounded_by']);
    }

    public function test_the_mobile_app_gets_the_payment_address_with_the_order(): void
    {
        $token = $this->postJson('/api/v1/cart/items', ['product_id' => $this->product->id])->json('data.token');

        $this->postJson('/api/v1/orders', [
            'customer_name' => 'Awa Koné', 'phone' => '0701020304', 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'payment_method' => 'cinetpay', 'terms' => true,
        ], [CartManager::HEADER => $token])
            ->assertCreated()
            ->assertJsonPath('payment.url', 'https://secure.cinetpay.net/payment/pay-token-1')
            ->assertJsonPath('data.payment_status.code', 'en_attente');
    }

    public function test_a_payment_received_after_the_cancellation_is_kept_for_a_refund(): void
    {
        Event::fake([OrderPlaced::class]);
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [2001, 'INITIATED'];
        $this->travel(OnlinePayments::DEFAULT_TIMEOUT + 1)->minutes();
        $this->artisan('payments:expire-unpaid');

        // The customer paid on a CinetPay page opened before the cancellation.
        $payment->forceFill(['status' => TransactionStatus::Pending])->save();
        $this->cinetPayStatus = [100, 'SUCCESS'];
        app(OnlinePayments::class)->synchronize($payment->fresh(), 'test');

        $this->assertSame(TransactionStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame([OrderStatus::Cancelled, PaymentStatus::Cancelled], [$payment->order->fresh()->status, $payment->order->fresh()->payment_status]);
        $this->assertDatabaseHas('activity_log', ['description' => 'Paiement reçu sur une commande annulée : à rembourser']);
        Event::assertNotDispatched(OrderPlaced::class);
    }

    public function test_a_status_answer_about_another_transaction_is_ignored(): void
    {
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [100, 'SUCCESS'];
        $this->statusAboutAnotherTransaction = 'KM-SOMEONE-ELSE';

        app(OnlinePayments::class)->synchronize($payment, 'test');

        $this->assertSame(TransactionStatus::Initiated, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->order->fresh()->payment_status);
    }

    public function test_an_expired_token_is_renewed_and_the_call_retried_once(): void
    {
        $payment = $this->orderPaidOnline();
        $this->assertSame(1, $this->logins);
        $this->expiredTokenAnswers = 1;

        app(OnlinePayments::class)->synchronize($payment, 'test');

        $this->assertSame(2, $this->logins);
        $this->assertSame(TransactionStatus::Pending, $payment->fresh()->status);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer jwt-token-2'));
    }

    public function test_the_order_page_of_the_back_office_lists_its_payments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $payment = $this->orderPaidOnline();
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $payment->order, 'pageClass' => ViewOrder::class])
            ->assertOk()
            ->assertSee($payment->merchant_transaction_id)
            ->callAction(TestAction::make('check')->table($payment));

        $this->assertSame(TransactionStatus::Pending, $payment->fresh()->status);
    }

    public function test_the_payments_space_lists_checks_and_refunds_payments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $open = $this->orderPaidOnline();
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $this->get(PaymentResource::getUrl('index'))->assertOk();
        Livewire::test(PaymentsOverview::class)->assertSeeText('Encaissé aujourd’hui')->assertSeeText('En attente de la réponse de CinetPay');

        Livewire::test(ListPayments::class)
            ->assertCanSeeTableRecords([$open])
            ->set('activeTab', 'succeeded')
            ->assertCanNotSeeTableRecords([$open])
            ->set('activeTab', 'open')
            ->assertCanSeeTableRecords([$open]);

        $this->cinetPayStatus = [100, 'SUCCESS'];
        Livewire::test(ViewPayment::class, ['record' => $open->merchant_transaction_id])
            ->assertSeeText($open->order->number)
            ->callAction('check');
        $this->assertSame(TransactionStatus::Succeeded, $open->fresh()->status);

        // Cancelled after being paid: the payments space flags the refund owed.
        app(OrderStatusManager::class)->move($open->order->fresh(), OrderStatus::Cancelled, auth()->user(), 'Rupture de stock');
        $this->assertSame(1, Payment::query()->toRefund()->count());
        $this->assertSame('1', PaymentResource::getNavigationBadge());

        Livewire::test(ListPayments::class)
            ->set('activeTab', 'to_refund')
            ->assertCanSeeTableRecords([$open])
            ->callAction(TestAction::make('refund')->table($open->fresh()), ['reason' => 'Commande annulée']);

        $this->assertSame(TransactionStatus::Refunded, $open->fresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $open->order->fresh()->payment_status);
        $this->assertNull(PaymentResource::getNavigationBadge());

        $journal = $this->get(PaymentResource::getUrl('view', ['record' => $open]))->assertOk();
        $journal->assertSeeText('Paiement confirmé par CinetPay')->assertSeeText('Remboursement enregistré');
    }

    public function test_the_payments_export_lists_the_payments_shown(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $payment = $this->orderPaidOnline();
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $export = Livewire::test(ListPayments::class)->callAction('export')->assertFileDownloaded('paiements-'.now()->format('Y-m-d').'.csv');

        $csv = base64_decode($export->effects['download']['content']);
        $this->assertStringContainsString('Référence KOVA', $csv);
        $this->assertStringContainsString($payment->merchant_transaction_id.';', $csv);
        $this->assertStringContainsString($payment->order->number, $csv);
    }

    public function test_the_payments_space_is_read_only_without_the_orders_management_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $payment = $this->orderPaidOnline();
        $this->cinetPayStatus = [100, 'SUCCESS'];
        app(OnlinePayments::class)->synchronize($payment, 'test');

        $this->actingAs(User::factory()->staff(Role::Picker)->create());
        $this->get(PaymentResource::getUrl('index'))->assertOk();
        Livewire::test(ListPayments::class)->assertActionHidden(TestAction::make('refund')->table($payment->fresh()));

        $this->actingAs(User::factory()->staff(Role::Courier)->create());
        $this->get(PaymentResource::getUrl('index'))->assertForbidden();
    }

    private function orderPaidOnline(): Payment
    {
        $this->addToCart();
        $this->placeOrder();

        return Payment::sole();
    }

    private function addToCart(int $quantity = 1): void
    {
        $this->post('/panier/articles', ['product_id' => $this->product->id, 'quantity' => $quantity]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function placeOrder(array $overrides = []): TestResponse
    {
        return $this->post('/commande', [
            'customer_name' => 'Awa Koné', 'phone' => '07 01 02 03 04', 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'payment_method' => 'cinetpay', 'terms' => '1', ...$overrides,
        ]);
    }
}
