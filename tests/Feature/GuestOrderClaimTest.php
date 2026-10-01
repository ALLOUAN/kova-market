<?php

namespace Tests\Feature;

use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestOrderClaimTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $sms = [];

    private User $customer;

    private Commune $commune;

    protected function setUp(): void
    {
        parent::setUp();

        // These journeys are checked with SMS switched on; WhatsApp codes: WhatsAppTest.
        Setting::store(['notifications.sms' => '1', 'notifications.whatsapp' => '0']);

        $sent = &$this->sms;
        $this->app->instance(SmsGateway::class, new class($sent) implements SmsGateway
        {
            /** @param list<string> $sent */
            public function __construct(private array &$sent) {}

            public function send(string $phone, string $message): void
            {
                $this->sent[] = $message;
            }
        });

        $this->commune = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true])->communes()->create(['name' => 'Cocody']);
        $this->customer = User::factory()->customer()->create(['phone' => '0701020304']);
    }

    public function test_guest_orders_with_the_same_phone_join_the_account_after_the_sms_code(): void
    {
        $mine = [$this->guestOrder('KM-260920-0001', '+2250701020304'), $this->guestOrder('KM-260921-0002', '+2250701020304')];
        $someoneElse = $this->guestOrder('KM-260922-0003', '+2250505050505');

        $this->actingAs($this->customer)->get('/compte')->assertOk()->assertSeeText('2 commandes')->assertSeeText('Recevoir un code par SMS');

        $this->post(route('account.guest-orders.claim'))->assertSessionHas('claim_code_sent');
        $this->assertCount(1, $this->sms);
        $this->assertStringContainsString('2 commande(s)', $this->sms[0]);

        // Nothing is attached before the code.
        $this->assertNull($mine[0]->fresh()->user_id);

        $this->post(route('account.guest-orders.confirm'), ['code' => $this->code()])
            ->assertRedirect(route('account.orders'))
            ->assertSessionHas('account_status', '2 commande(s) ajoutée(s) à votre compte.');

        $this->assertSame([$this->customer->id, $this->customer->id], [$mine[0]->fresh()->user_id, $mine[1]->fresh()->user_id]);
        $this->assertNull($someoneElse->fresh()->user_id);
        $this->get('/compte/commandes')->assertSeeText('KM-260920-0001');
        $this->get('/compte')->assertDontSeeText('Recevoir un code par SMS');
    }

    public function test_a_wrong_code_attaches_nothing(): void
    {
        $order = $this->guestOrder('KM-260920-0001', '+2250701020304');
        $this->actingAs($this->customer)->post(route('account.guest-orders.claim'));

        $this->post(route('account.guest-orders.confirm'), ['code' => $this->code() === '000000' ? '111111' : '000000'])
            ->assertSessionHasErrorsIn('claim', 'code');

        $this->assertNull($order->fresh()->user_id);
    }

    public function test_no_code_is_sent_when_there_is_nothing_to_attach(): void
    {
        $this->actingAs($this->customer)->get('/compte')->assertDontSeeText('Recevoir un code par SMS');
        $this->post(route('account.guest-orders.claim'));

        $this->assertSame([], $this->sms);
    }

    private function guestOrder(string $number, string $phone): Order
    {
        return Order::create([
            'number' => $number, 'status' => 'livree', 'payment_method' => 'paiement_livraison', 'payment_status' => 'paye',
            'customer_name' => 'Awa', 'phone' => $phone, 'commune_id' => $this->commune->id, 'commune_name' => 'Cocody',
            'zone_name' => 'Zone 1', 'district' => 'Riviera', 'subtotal' => 1000, 'shipping_fee' => 0, 'discount' => 0,
            'total' => 1000, 'marketing_opt_in' => false, 'terms_accepted_at' => now(),
        ]);
    }

    private function code(): string
    {
        preg_match('/\b(\d{6})\b/', end($this->sms), $matches);

        return $matches[1];
    }
}
