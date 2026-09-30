<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderUpdateForCustomer;
use App\Services\Cart\CartManager;
use App\Services\Orders\OrderReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The customer's receipt: given at every order and payment (confirmation page, customer area, e-mails, delivery SMS),
 * "Reçu de commande" while payment is due, "Reçu de paiement" once paid.
 */
class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
    }

    public function test_the_customer_gets_the_receipt_on_the_confirmation_page(): void
    {
        $order = $this->placeOrder();

        $this->get(route('checkout.confirmation', $order))->assertSee('href="'.route('orders.receipt', $order).'"', false)->assertSeeText('Télécharger mon reçu (PDF)');

        $this->get(route('orders.receipt', $order))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="recu-'.$order->number.'.pdf"');

        // Nobody else can open it without the signed link.
        $this->flushSession();
        $this->get(route('orders.receipt', $order))->assertNotFound();
        $this->get(app(OrderReceipt::class)->url($order))->assertOk();
    }

    public function test_the_receipt_says_what_is_due_then_that_it_is_paid(): void
    {
        $order = $this->placeOrder();
        $receipt = app(OrderReceipt::class);

        $due = $receipt->html($order);
        $this->assertStringContainsString('Reçu de commande', $due);
        $this->assertStringContainsString('À PAYER', $due);
        $this->assertStringContainsString('À régler au livreur à la réception', $due);
        $this->assertStringContainsString($order->number, $due);
        $this->assertStringContainsString('Riviera 2, Cocody', $due);

        // Delivered: cash on delivery received.
        $order->forceFill(['status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid])->save();
        $paid = $receipt->html($order->fresh());
        $this->assertStringContainsString('Reçu de paiement', $paid);
        $this->assertStringContainsString('PAYÉ LE', $paid);
        $this->assertStringContainsString('Payé à la livraison', $paid);
    }

    public function test_the_order_email_carries_the_receipt_and_the_delivery_sms_its_link(): void
    {
        $order = $this->placeOrder(['email' => 'koffi@exemple.ci']);

        $mail = (new OrderUpdateForCustomer($order, OrderUpdateForCustomer::PLACED))->toMail(OrderUpdateForCustomer::recipientOf($order));
        $this->assertSame('recu-'.$order->number.'.pdf', $mail->rawAttachments[0]['name']);
        $this->assertStringContainsString('Votre reçu est joint à cet e-mail.', implode(' ', $mail->outroLines));

        $sms = (new OrderUpdateForCustomer($order, 'livree'))->toSms(OrderUpdateForCustomer::recipientOf($order));
        $this->assertStringContainsString('/commande/'.$order->number.'/recu?signature=', $sms);

        // Other steps carry no receipt.
        $confirmed = (new OrderUpdateForCustomer($order, 'confirmee'))->toMail(OrderUpdateForCustomer::recipientOf($order));
        $this->assertSame([], $confirmed->rawAttachments);
    }

    public function test_the_customer_area_offers_the_receipt_of_each_order(): void
    {
        $user = User::factory()->customer()->create(['phone' => '0701020304']);
        $order = $this->placeOrder([], $user);

        $this->actingAs($user)->get(route('account.orders.show', $order))->assertSee('href="'.route('orders.receipt', $order).'"', false);
        $this->get(route('orders.receipt', $order))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function placeOrder(array $overrides = [], ?User $user = null): Order
    {
        Notification::fake();
        $product = Product::factory()->create(['name' => 'Enceinte JBL', 'price' => 45000, 'stock' => 10]);

        if ($user) {
            $this->actingAs($user);
        }

        $this->post('/panier/articles', ['product_id' => $product->id, 'quantity' => 1]);
        if ($cart = Cart::whereNull('user_id')->latest('id')->first()) {
            $this->withCookie(CartManager::COOKIE, $cart->token);
        }

        $this->post('/commande', [
            'customer_name' => 'Koffi Yao', 'phone' => '0701020304', 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'landmark' => null, 'payment_method' => 'paiement_livraison', 'terms' => '1', ...$overrides,
        ]);

        return Order::latest('id')->firstOrFail();
    }
}
