<?php

namespace Tests\Feature\Api;

use App\Models\Address;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
        $this->customer = User::factory()->customer()->create(['name' => 'Awa Koné', 'phone' => '0701020304']);
        Sanctum::actingAs($this->customer);
    }

    public function test_an_order_placed_while_signed_in_belongs_to_the_account_and_can_save_its_address(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->assertCreated()->assertJsonPath('data.token', null);

        $number = $this->postJson('/api/v1/orders', [
            'customer_name' => 'Awa Koné', 'phone' => '0701020304', 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'payment_method' => 'paiement_livraison', 'terms' => true, 'save_address' => true,
        ])->assertCreated()->json('data.number');

        $this->getJson('/api/v1/account/orders')
            ->assertOk()
            ->assertJsonPath('data.0.number', $number)
            ->assertJsonPath('data.0.district', 'Riviera 2')
            ->assertJsonPath('meta.total', 1);
        $this->getJson("/api/v1/account/orders/{$number}")->assertOk()->assertJsonPath('data.items.0.quantity', 1);

        $this->getJson('/api/v1/account/addresses')
            ->assertOk()
            ->assertJsonPath('data.0.district', 'Riviera 2')
            ->assertJsonPath('data.0.is_default', true);
    }

    public function test_another_customers_order_does_not_exist(): void
    {
        $order = $this->orderOf(User::factory()->customer()->create());

        $this->getJson("/api/v1/account/orders/{$order->number}")->assertNotFound();
        $this->getJson('/api/v1/account/orders')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_address_book_is_managed_through_the_api(): void
    {
        $details = ['label' => 'Maison', 'recipient_name' => 'Awa Koné', 'phone' => '07 01 02 03 04', 'commune_id' => $this->cocody->id, 'district' => 'Riviera 2'];

        $first = $this->postJson('/api/v1/account/addresses', $details)
            ->assertCreated()
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.phone', '+2250701020304')
            ->json('data.id');
        $second = $this->postJson('/api/v1/account/addresses', [...$details, 'label' => 'Bureau'])->assertCreated()->assertJsonPath('data.is_default', false)->json('data.id');

        $this->postJson("/api/v1/account/addresses/{$second}/default")->assertOk()->assertJsonPath('data.is_default', true);
        $this->assertFalse(Address::find($first)->is_default);

        $this->putJson("/api/v1/account/addresses/{$first}", [...$details, 'district' => 'Angré'])->assertOk()->assertJsonPath('data.district', 'Angré');
        $this->putJson("/api/v1/account/addresses/{$first}", ['label' => ''])->assertUnprocessable()->assertJsonValidationErrors(['label', 'recipient_name']);

        $this->deleteJson("/api/v1/account/addresses/{$second}")->assertNoContent();
        $this->assertTrue(Address::find($first)->is_default);
    }

    public function test_another_customers_address_is_not_reachable(): void
    {
        $address = User::factory()->customer()->create()->addresses()->create([
            'label' => 'Maison', 'recipient_name' => 'Yao', 'phone' => '0501020304', 'commune_id' => $this->cocody->id, 'district' => 'Blockhaus',
        ]);

        $this->deleteJson("/api/v1/account/addresses/{$address->id}")->assertNotFound();
        $this->postJson("/api/v1/account/addresses/{$address->id}/default")->assertNotFound();
        $this->assertModelExists($address);
    }

    public function test_preferences_and_personal_data(): void
    {
        $this->patchJson('/api/v1/account/preferences', ['marketing_opt_in' => true])->assertOk()->assertJsonPath('data.marketing_opt_in', true);
        $this->assertTrue($this->customer->fresh()->marketing_opt_in);

        $this->getJson('/api/v1/account/data')->assertOk()->assertJsonFragment(['name' => 'Awa Koné']);
    }

    public function test_the_account_is_erased_with_its_password(): void
    {
        $this->deleteJson('/api/v1/account', ['password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->deleteJson('/api/v1/account', ['password' => 'password'])->assertNoContent();
        $this->assertNotSame('Awa Koné', $this->customer->fresh()?->name);
    }

    private function orderOf(User $user): Order
    {
        return Order::create([
            'number' => 'KM-260927-0099', 'user_id' => $user->id, 'status' => 'recue', 'payment_method' => 'paiement_livraison',
            'payment_status' => 'en_attente', 'customer_name' => $user->name, 'phone' => '+2250501020304', 'commune_id' => $this->cocody->id,
            'commune_name' => 'Cocody', 'zone_name' => 'Zone 1', 'district' => 'Blockhaus', 'subtotal' => 1000, 'shipping_fee' => 0,
            'discount' => 0, 'total' => 1000, 'marketing_opt_in' => false, 'terms_accepted_at' => now(),
        ]);
    }
}
