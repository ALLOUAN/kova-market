<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Account\AccountEraser;
use App\Services\Checkout\PlaceOrder;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAreaTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
        $this->customer = User::factory()->customer()->create(['name' => 'Aya Kouassi', 'phone' => '0701020304']);
    }

    public function test_guests_are_asked_to_sign_in_and_come_back_to_the_page(): void
    {
        $this->get('/compte/commandes')->assertRedirect(route('home', ['connexion' => 1]));
        $this->get('/?connexion=1')->assertSee('getElementById(\'signinModal\')).show()', false);

        $this->post('/login', ['login' => '0701020304', 'password' => 'password'])->assertRedirect('/compte/commandes');
    }

    public function test_the_customer_sees_their_orders_and_only_theirs(): void
    {
        $mine = $this->placeOrder($this->customer);
        $theirs = $this->placeOrder(User::factory()->customer()->create());

        $this->actingAs($this->customer)->get('/compte/commandes')->assertOk()->assertSeeText($mine->number)->assertDontSeeText($theirs->number);
        $this->get("/compte/commandes/{$mine->number}")->assertOk()->assertSeeText('Reçue')->assertSeeText('Riviera');
        $this->get("/compte/commandes/{$theirs->number}")->assertNotFound();
        $this->get('/compte')->assertOk()->assertSeeText($mine->number);
    }

    public function test_the_address_book_keeps_a_single_default_address(): void
    {
        $this->actingAs($this->customer);
        $address = fn (string $label, bool $default = false) => [
            'label' => $label, 'recipient_name' => 'Aya', 'phone' => '07 01 02 03 04',
            'commune_id' => $this->cocody->id, 'district' => 'Riviera', 'is_default' => $default,
        ];

        $this->post('/compte/adresses', $address('Maison'))->assertRedirect('/compte/adresses');
        $this->post('/compte/adresses', $address('Bureau', true));

        $this->assertSame(['Bureau'], $this->customer->addresses()->where('is_default', true)->pluck('label')->all());
        $this->assertSame('+2250701020304', Address::first()->phone);

        $this->delete('/compte/adresses/'.Address::firstWhere('label', 'Bureau')->id);
        $this->assertTrue(Address::sole()->is_default);
    }

    public function test_an_address_of_another_customer_is_out_of_reach(): void
    {
        $other = Address::create(['user_id' => User::factory()->create()->id, 'label' => 'X', 'recipient_name' => 'X', 'phone' => '0501020304', 'district' => 'X']);

        $this->actingAs($this->customer);
        $this->get("/compte/adresses/{$other->id}/modifier")->assertNotFound();
        $this->delete("/compte/adresses/{$other->id}")->assertNotFound();
        $this->assertModelExists($other);
    }

    public function test_the_checkout_uses_the_default_address_and_can_save_a_new_one(): void
    {
        Address::create(['user_id' => $this->customer->id, 'label' => 'Maison', 'recipient_name' => 'Aya K.', 'phone' => '0701020304', 'commune_id' => $this->cocody->id, 'district' => 'Angré', 'landmark' => 'Château d’eau', 'is_default' => true]);
        $this->actingAs($this->customer);
        $this->post('/panier/articles', ['product_id' => Product::factory()->create()->id]);

        $this->get('/commande')->assertSee('value="Angré"', false)->assertSee('value="Aya K."', false);

        $this->post('/commande', [
            'customer_name' => 'Aya', 'phone' => '0701020304', 'commune_id' => $this->cocody->id, 'district' => 'Deux Plateaux',
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'terms' => '1', 'save_address' => '1',
        ])->assertRedirect();

        $this->assertSame(2, $this->customer->addresses()->count());
        $this->assertTrue(Address::firstWhere('label', 'Maison')->is_default);
    }

    public function test_profile_password_and_preferences_are_updated(): void
    {
        $this->actingAs($this->customer);

        $this->put('/user/profile-information', ['name' => 'Aya K.', 'phone' => '05 06 07 08 09', 'email' => 'aya@example.ci'])->assertSessionHasNoErrors();
        $this->put('/user/password', ['current_password' => 'password', 'password' => 'nouveau-mdp', 'password_confirmation' => 'nouveau-mdp'])->assertSessionHasNoErrors();
        $this->post('/compte/preferences', ['marketing_opt_in' => '1']);

        $this->customer->refresh();
        $this->assertSame(['Aya K.', '+2250506070809', 'aya@example.ci', true], [$this->customer->name, $this->customer->phone, $this->customer->email, $this->customer->marketing_opt_in]);
        $this->get('/compte')->assertSeeText('Mes informations');
    }

    public function test_the_customer_downloads_their_data(): void
    {
        $order = $this->placeOrder($this->customer);

        $response = $this->actingAs($this->customer)->get('/compte/donnees')->assertOk()->assertDownload('mes-donnees-kova-market.json');
        $data = json_decode($response->streamedContent(), true);

        $this->assertSame('Aya Kouassi', $data['compte']['name']);
        $this->assertSame($order->number, $data['commandes'][0]['numero']);
    }

    public function test_deleting_the_account_anonymises_it_but_waits_for_open_orders(): void
    {
        $order = $this->placeOrder($this->customer);
        $this->actingAs($this->customer);

        $this->delete('/compte', ['password' => 'password'])->assertSessionHasErrors(['password'], null, 'deleteAccount');
        $this->assertSame('Aya Kouassi', $this->customer->fresh()->name);

        $this->seed(RolesAndPermissionsSeeder::class);
        app(OrderStatusManager::class)->move($order, OrderStatus::Cancelled, User::factory()->staff(Role::Manager)->create(), 'Test');

        $this->delete('/compte', ['password' => 'password'])->assertRedirect('/');

        $this->assertGuest();
        $this->assertSame([AccountEraser::ANONYMOUS, null, null], [$this->customer->fresh()->name, $this->customer->fresh()->phone, $this->customer->fresh()->email]);
        $this->assertSame([AccountEraser::ANONYMOUS, '—'], [$order->fresh()->customer_name, $order->fresh()->district]);
        $this->assertSame(1, Order::count());
    }

    public function test_public_tracking_needs_the_number_and_the_phone_and_hides_the_address(): void
    {
        $order = $this->placeOrder($this->customer);

        $this->post('/suivi', ['number' => strtolower($order->number), 'phone' => '07 01 02 03 04'])
            ->assertOk()
            ->assertSeeText("Commande {$order->number}")
            ->assertSeeText('Livraison à Cocody.')
            ->assertDontSeeText('Riviera');

        $this->post('/suivi', ['number' => $order->number, 'phone' => '0599999999'])->assertSeeText('Commande introuvable.');
        $this->post('/suivi', ['number' => 'KM-000000-0000', 'phone' => '0701020304'])->assertSeeText('Commande introuvable.');
    }

    private function placeOrder(User $user): Order
    {
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => Product::factory()->create()->defaultVariant->id, 'quantity' => 1]);

        return app(PlaceOrder::class)->handle($cart, [
            'customer_name' => $user->name, 'phone' => $user->phone, 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Riviera', 'landmark' => null, 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
        ], $user);
    }
}
