<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Filament\Resources\Couriers\Pages\CreateCourier;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CourierCredentials;
use App\Notifications\DeliveryForCourier;
use App\Services\Checkout\PlaceOrder;
use App\Services\Delivery\CourierAccounts;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CourierTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private DeliveryZone $zone1;

    private DeliveryZone $zone2;

    private Commune $cocody;

    private Commune $yopougon;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create();
        $this->zone1 = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $this->zone2 = DeliveryZone::create(['name' => 'Zone 2', 'fee' => 2000, 'is_active' => true]);
        $this->cocody = $this->zone1->communes()->create(['name' => 'Cocody']);
        $this->yopougon = $this->zone2->communes()->create(['name' => 'Yopougon']);
        $this->product = Product::factory()->create(['name' => 'Enceinte', 'price' => 40000, 'stock' => 10]);
    }

    public function test_managers_create_courier_accounts_and_the_credentials_go_by_sms(): void
    {
        Notification::fake();
        $this->actingAs($this->manager);

        Livewire::test(CreateCourier::class)
            ->fillForm(['name' => 'Moussa Traoré', 'phone' => '05 06 07 08 09', 'transport' => 'moto', 'zones' => [$this->zone1->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $courier = Courier::sole();
        $this->assertTrue($courier->user->hasRole(Role::Courier->value));
        $this->assertTrue($courier->user->must_change_password);
        $this->assertSame([$this->zone1->id], $courier->zones->modelKeys());

        $password = null;
        Notification::assertSentOnDemand(CourierCredentials::class, function (CourierCredentials $notification, array $channels, object $notifiable) use (&$password) {
            $password = $notification->temporaryPassword;

            return $notifiable->routes['sms'] === '+2250506070809'
                && str_contains($notification->toSms($notifiable), 'Identifiant : +225 05 06 07 08 09. Mot de passe provisoire : '.$password);
        });

        // First sign-in: the temporary password must be replaced before anything else.
        $this->post('/livreur/deconnexion');
        $this->post('/livreur/connexion', ['phone' => '0506070809', 'password' => $password])->assertRedirect('/livreur');
        $this->get('/livreur')->assertRedirect('/livreur/mot-de-passe');
        $this->put('/livreur/mot-de-passe', ['password' => 'MonSecret2026', 'password_confirmation' => 'MonSecret2026'])->assertRedirect('/livreur');
        $this->get('/livreur')->assertOk()->assertSeeText('Bonjour Moussa');
    }

    public function test_customers_and_staff_cannot_enter_the_courier_area(): void
    {
        $this->post('/livreur/connexion', ['phone' => $this->manager->phone ?? 'x', 'password' => 'password'])->assertSessionHasErrors('phone');

        $this->actingAs(User::factory()->create());
        $this->get('/livreur')->assertForbidden();

        auth()->logout();
        $this->get('/livreur')->assertRedirect('/livreur/connexion');
    }

    public function test_first_to_accept_mode_one_courier_wins_and_other_zones_get_404(): void
    {
        Notification::fake();
        [$awa, $awaUser] = $this->courier('Awa', [$this->zone1]);
        [$ibrahim, $ibrahimUser] = $this->courier('Ibrahim', [$this->zone1]);
        [, $kofiUser] = $this->courier('Kofi', [$this->zone2]);

        $order = $this->confirmedOrder();

        Notification::assertSentTo([$awaUser, $ibrahimUser], DeliveryForCourier::class);
        Notification::assertNotSentTo($kofiUser, DeliveryForCourier::class);

        $this->actingAs($awaUser)->get('/livreur')->assertSeeText($order->number)->assertSeeText('Je prends cette livraison');
        $this->actingAs($kofiUser)->get("/livreur/commandes/{$order->number}")->assertNotFound();

        $this->actingAs($awaUser)->post("/livreur/commandes/{$order->number}/prendre")->assertRedirect("/livreur/commandes/{$order->number}");
        $this->actingAs($ibrahimUser)->post("/livreur/commandes/{$order->number}/prendre")
            ->assertSessionHas('courier_error', 'Un autre livreur vient de prendre cette livraison.');

        $this->assertTrue($order->fresh()->courier->is($awa));
        // Once taken, the order is no longer Ibrahim's to open.
        $this->actingAs($ibrahimUser)->get("/livreur/commandes/{$order->number}")->assertNotFound();
        $this->assertSame(0, app(DeliveryDispatcher::class)->queueFor($ibrahim)->count());
    }

    public function test_automatic_mode_gives_the_order_to_the_least_busy_courier_of_the_zone(): void
    {
        Notification::fake();
        Setting::store(['delivery.assignment_mode' => DeliveryDispatcher::AUTOMATIC]);
        [$awa] = $this->courier('Awa', [$this->zone1]);
        [$ibrahim, $ibrahimUser] = $this->courier('Ibrahim', [$this->zone1]);

        $first = $this->confirmedOrder();
        $second = $this->confirmedOrder();

        $this->assertTrue($first->fresh()->courier->is($awa));
        $this->assertTrue($second->fresh()->courier->is($ibrahim));
        Notification::assertSentOnDemand(DeliveryForCourier::class, fn ($notification, array $channels, object $notifiable) => $notifiable->routes['sms'] === $ibrahimUser->phone && $notification->event === DeliveryForCourier::ASSIGNED);
    }

    public function test_a_courier_delivers_and_records_the_cash_collected(): void
    {
        [$awa, $awaUser] = $this->courier('Awa', [$this->zone1]);
        $order = $this->confirmedOrder();
        app(DeliveryDispatcher::class)->assign($order, $awa);

        // Not shipped yet: the courier waits.
        $this->actingAs($awaUser)->get("/livreur/commandes/{$order->number}")->assertOk()->assertSeeText('La commande est en préparation');

        $this->ship($order);
        $this->actingAs($awaUser)->get("/livreur/commandes/{$order->number}")
            ->assertSeeText('Je pars livrer')
            ->assertSee('href="tel:+2250701020304"', false)
            ->assertSee('https://wa.me/2250701020304', false)
            ->assertSee('https://www.google.com/maps/search/', false)
            ->assertSeeText("41\u{00A0}500\u{00A0}FCFA");

        $this->post("/livreur/commandes/{$order->number}/en-route")->assertRedirect();
        $this->assertSame(OrderStatus::OutForDelivery, $order->fresh()->status);

        $this->post("/livreur/commandes/{$order->number}/livree", ['cash_collected' => 41500])->assertRedirect('/livreur');

        $order->refresh();
        $this->assertSame([OrderStatus::Delivered, PaymentStatus::Paid, 41500], [$order->status, $order->payment_status, $order->cash_collected]);
        $this->assertTrue($order->statusHistory()->reorder('id', 'desc')->first()->user->is($awaUser));
        $this->get('/livreur')->assertSeeText('Livrées aujourd’hui')->assertSeeText("41\u{00A0}500\u{00A0}FCFA");
    }

    public function test_a_failed_delivery_needs_a_reason_and_puts_the_stock_back(): void
    {
        [$awa, $awaUser] = $this->courier('Awa', [$this->zone1]);
        $order = $this->confirmedOrder(quantity: 2);
        app(DeliveryDispatcher::class)->assign($order, $awa);
        $this->ship($order);
        app(OrderStatusManager::class)->move($order, OrderStatus::OutForDelivery, $awaUser);

        $this->actingAs($awaUser)->post("/livreur/commandes/{$order->number}/echec")->assertSessionHasErrors('reason');
        $this->post("/livreur/commandes/{$order->number}/echec", ['reason' => 'Client injoignable'])->assertRedirect('/livreur');

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame('Échec de livraison : Client injoignable', $order->statusHistory()->reorder('id', 'desc')->first()->note);
        $this->assertSame(10, $this->product->defaultVariant->fresh()->stock);
    }

    public function test_a_courier_cannot_move_someone_elses_delivery(): void
    {
        [$awa] = $this->courier('Awa', [$this->zone1]);
        [, $ibrahimUser] = $this->courier('Ibrahim', [$this->zone1]);
        $order = $this->confirmedOrder();
        app(DeliveryDispatcher::class)->assign($order, $awa);
        $this->ship($order);

        $this->actingAs($ibrahimUser)->post("/livreur/commandes/{$order->number}/en-route")->assertNotFound();
        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
    }

    public function test_a_suspended_courier_is_signed_out_and_their_deliveries_go_back_to_the_queue(): void
    {
        [$awa, $awaUser] = $this->courier('Awa', [$this->zone1]);
        $order = $this->confirmedOrder();
        app(DeliveryDispatcher::class)->assign($order, $awa);
        $this->actingAs($awaUser)->get('/livreur')->assertOk();

        app(CourierAccounts::class)->suspend($awa, $this->manager);
        $awaUser->refresh(); // as the next request would reload it

        $this->assertNull($order->fresh()->courier_id);
        $this->get('/livreur')->assertRedirect('/livreur/connexion');
        $this->assertGuest();
        $this->post('/livreur/connexion', ['phone' => $awaUser->phone, 'password' => 'password'])
            ->assertSessionHasErrors(['phone' => 'Votre compte livreur est suspendu. Contactez la boutique.']);
        // The storefront sign-in refuses the account too.
        $this->post('/login', ['login' => $awaUser->phone, 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_the_back_office_gives_an_order_to_another_courier(): void
    {
        Notification::fake();
        [$awa] = $this->courier('Awa', [$this->zone1]);
        [$kofi] = $this->courier('Kofi', [$this->zone2]);
        $order = $this->confirmedOrder();
        app(DeliveryDispatcher::class)->assign($order, $awa);

        $this->actingAs($this->manager);
        Livewire::test(ViewOrder::class, ['record' => $order->number])
            ->callAction('assignCourier', ['courier_id' => $kofi->id])
            ->assertHasNoActionErrors();

        $this->assertTrue($order->fresh()->courier->is($kofi));

        Livewire::test(ViewOrder::class, ['record' => $order->number])->callAction('releaseCourier');
        $this->assertNull($order->fresh()->courier_id);
    }

    public function test_the_courier_app_is_installable(): void
    {
        $this->get('/livreur/manifest.webmanifest')->assertOk()
            ->assertJsonPath('scope', '/livreur/')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.1.sizes', '512x512');
        $this->get('/livreur/sw.js')->assertOk()->assertHeader('Service-Worker-Allowed', '/livreur/');
        $this->assertFileExists(public_path('assets/images/courier/icon-512.png'));
        $this->get('/livreur/connexion')->assertOk()->assertSee('rel="manifest"', false);
    }

    public function test_the_sign_in_page_shows_the_brand_help_and_errors(): void
    {
        $this->get('/livreur/connexion')
            ->assertOk()
            ->assertSeeText('Espace livreur')
            ->assertSee(config('storefront.logo_small'), false)
            ->assertSee('data-toggle-password', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSeeText('Mot de passe oublié ou compte bloqué ?')
            ->assertSee('href="tel:', false);

        $this->from('/livreur/connexion')->post('/livreur/connexion', ['phone' => '0700000000', 'password' => 'mauvais'])
            ->assertRedirect('/livreur/connexion');

        $this->get('/livreur/connexion')
            ->assertSee('role="alert"', false)
            ->assertSeeText('Numéro ou mot de passe incorrect.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('value="0700000000"', false);
    }

    /**
     * @param  list<DeliveryZone>  $zones
     * @return array{0: Courier, 1: User}
     */
    private function courier(string $name, array $zones): array
    {
        $user = User::factory()->create(['name' => $name.' Diallo', 'phone' => '07'.random_int(10000000, 99999999)]);
        $user->assignRole(Role::Courier->value);
        $courier = $user->courier()->create(['transport' => 'moto']);
        $courier->zones()->sync(collect($zones)->map->getKey()->all());

        return [$courier, $user];
    }

    private function confirmedOrder(int $quantity = 1): Order
    {
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => $this->product->defaultVariant->id, 'quantity' => $quantity]);

        $order = app(PlaceOrder::class)->handle($cart, [
            'customer_name' => 'Koffi Yao', 'phone' => '+2250701020304', 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'landmark' => 'Près de la pharmacie', 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
        ]);

        return app(OrderStatusManager::class)->move($order, OrderStatus::Confirmed, $this->manager);
    }

    private function ship(Order $order): void
    {
        $statuses = app(OrderStatusManager::class);
        $statuses->move($order, OrderStatus::Preparing, $this->manager);
        $statuses->move($order, OrderStatus::Shipped, $this->manager);
    }
}
