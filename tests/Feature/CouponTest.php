<?php

namespace Tests\Feature;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Filament\Resources\Coupons\CouponResource;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
    }

    public function test_a_percentage_code_is_rounded_and_recorded_with_the_order(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'BIENVENUE', 'type' => CouponType::Percentage, 'value' => 15]);
        $this->addToCart(Product::factory()->create(['price' => 33333]));

        $this->applyCoupon('bienvenue ')->assertRedirect('/panier')->assertSessionHas('cart_status');
        // 15 % of 33 333 = 4 999.95, rounded to 5 000.
        $this->get('/panier')->assertOk()->assertSeeText('Remise (BIENVENUE)')->assertSeeText("5\u{00A0}000\u{00A0}FCFA")->assertSeeText("28\u{00A0}333\u{00A0}FCFA");

        $this->placeOrder();

        $order = Order::sole();
        $this->assertSame([33333, 5000, 1500, 29833, 'BIENVENUE'], [$order->subtotal, $order->discount, $order->shipping_fee, $order->total, $order->coupon_code]);
        $this->assertSame(1, $coupon->fresh()->times_used);
        $this->assertSame(['+2250701020304', 5000], [$order->couponUsage->phone, $order->couponUsage->amount]);
        $this->assertNull(Cart::sole()->coupon_id);
        $this->get("/commande/{$order->number}/merci")->assertSeeText('Remise (BIENVENUE)');
    }

    public function test_a_targeted_code_only_counts_the_eligible_lines(): void
    {
        $audio = Category::factory()->create();
        $headphones = Category::factory()->create(['parent_id' => $audio->id]);
        Coupon::factory()->create(['code' => 'AUDIO', 'type' => CouponType::Fixed, 'value' => 50000, 'target' => CouponTarget::Categories])
            ->categories()->attach($audio);

        $this->addToCart(Product::factory()->create(['category_id' => $headphones->id, 'price' => 20000]));
        $this->addToCart(Product::factory()->create(['price' => 100000]));
        $this->applyCoupon('AUDIO');
        $this->placeOrder();

        // The fixed amount never exceeds the eligible lines (the sub-category belongs to the targeted one).
        $this->assertSame([120000, 20000, 1500, 101500], [Order::sole()->subtotal, Order::sole()->discount, Order::sole()->shipping_fee, Order::sole()->total]);
    }

    public function test_a_free_delivery_code_waives_the_fee(): void
    {
        Coupon::factory()->create(['code' => 'LIVRAISON', 'type' => CouponType::FreeShipping, 'value' => 0]);
        $this->addToCart(Product::factory()->create(['price' => 30000]));

        $this->post('/panier/commune', ['commune_id' => $this->cocody->id]);
        $this->applyCoupon('LIVRAISON');
        $this->get('/panier')->assertSeeText('Offerte')->assertSeeText("30\u{00A0}000\u{00A0}FCFA");
        $this->placeOrder();

        $order = Order::sole();
        $this->assertSame([0, 0, 30000, 'LIVRAISON'], [$order->discount, $order->shipping_fee, $order->total, $order->coupon_code]);
        $this->assertSame(1500, $order->couponUsage->amount);
    }

    public function test_each_refusal_gives_its_cause(): void
    {
        $shoes = Product::factory()->create(['price' => 15000]);
        $this->addToCart($shoes);

        Coupon::factory()->create(['code' => 'FINI', 'ends_at' => now()->subDay()]);
        Coupon::factory()->create(['code' => 'DEMAIN', 'starts_at' => now()->addDay()->setTime(8, 0)]);
        Coupon::factory()->create(['code' => 'OFF', 'is_active' => false]);
        Coupon::factory()->create(['code' => 'GROS', 'minimum_subtotal' => 20000]);
        Coupon::factory()->create(['code' => 'EPUISE', 'usage_limit' => 100])->forceFill(['times_used' => 100])->save();
        Coupon::factory()->create(['code' => 'AUTRE', 'target' => CouponTarget::Products])->products()->attach(Product::factory()->create());

        $expected = [
            'INCONNU' => 'Ce code promo n’existe pas.',
            'FINI' => 'Ce code promo a expiré.',
            'DEMAIN' => 'Ce code promo sera valable à partir du '.now()->addDay()->format('d/m/Y').' à 08h00.',
            'OFF' => 'Ce code promo n’est plus valable.',
            'GROS' => "Ce code promo demande un minimum d’achat de 20\u{00A0}000\u{00A0}FCFA (hors livraison).",
            'EPUISE' => 'Ce code promo a atteint son nombre maximal d’utilisations.',
            'AUTRE' => 'Aucun article de votre panier n’est concerné par ce code promo.',
        ];

        foreach ($expected as $code => $message) {
            $this->applyCoupon($code)->assertSessionHas('coupon_error', $message);
        }

        $this->assertNull(Cart::sole()->coupon_id);
    }

    public function test_removing_an_article_below_the_minimum_removes_the_discount(): void
    {
        Coupon::factory()->create(['code' => 'GROS', 'type' => CouponType::Fixed, 'value' => 3000, 'minimum_subtotal' => 20000]);
        $this->addToCart(Product::factory()->create(['price' => 15000]), 2);
        $this->applyCoupon('GROS');
        $this->get('/panier')->assertSeeText('Remise (GROS)');

        $this->patch('/panier/articles/'.Cart::sole()->items()->sole()->id, ['quantity' => 1]);

        $this->get('/panier')->assertDontSeeText('Remise (GROS)')->assertSeeText('Ce code promo demande un minimum d’achat');
        $this->get('/commande')->assertRedirect('/panier');
        $this->placeOrder()->assertRedirect('/panier')->assertSessionHas('cart_error');
        $this->assertSame(0, Order::count());
    }

    public function test_a_code_capped_at_100_uses_refuses_the_101st(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'CENT', 'usage_limit' => 100, 'usage_limit_per_customer' => null]);
        $coupon->forceFill(['times_used' => 99])->save();
        $this->addToCart(Product::factory()->create(['price' => 10000]));
        $this->applyCoupon('CENT');

        // Another customer takes the 100th use while this cart still holds the code.
        $coupon->forceFill(['times_used' => 100])->save();

        $this->placeOrder()->assertRedirect('/panier')
            ->assertSessionHas('cart_error', 'Code promo CENT : Ce code promo a atteint son nombre maximal d’utilisations. Retirez-le du panier pour commander sans remise.');
        $this->assertSame(0, Order::count());
        $this->assertSame(1, Cart::sole()->items()->count());
    }

    public function test_the_per_customer_cap_follows_the_phone_number(): void
    {
        Coupon::factory()->create(['code' => 'UNEFOIS', 'usage_limit_per_customer' => 1]);

        $this->addToCart(Product::factory()->create(['price' => 10000]));
        $this->applyCoupon('UNEFOIS');
        $this->placeOrder(['phone' => '07 01 02 03 04'])->assertRedirect();

        // Same phone from another browser: the guest cart accepts the code, the order does not.
        $this->withCookie(CartManager::COOKIE, '');
        $this->addToCart(Product::factory()->create(['price' => 10000]));
        $this->applyCoupon('UNEFOIS')->assertSessionMissing('coupon_error');
        $this->placeOrder(['phone' => '+225 0701020304'])->assertSessionHas('cart_error', 'Code promo UNEFOIS : Vous avez déjà utilisé ce code promo. Retirez-le du panier pour commander sans remise.');

        // A signed-in customer with that phone is told in the cart already.
        $this->actingAs(User::factory()->create(['phone' => '+2250701020304']));
        $this->addToCart(Product::factory()->create(['price' => 10000]));
        $this->applyCoupon('UNEFOIS')->assertSessionHas('coupon_error', 'Vous avez déjà utilisé ce code promo.');

        $this->assertSame(1, Order::count());
    }

    public function test_a_cancelled_order_gives_its_use_back(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $coupon = Coupon::factory()->create(['code' => 'RETOUR', 'usage_limit' => 1]);
        $this->addToCart(Product::factory()->create(['price' => 10000]));
        $this->applyCoupon('RETOUR');
        $this->placeOrder();

        app(OrderStatusManager::class)->move(Order::sole(), OrderStatus::Cancelled, User::factory()->staff(Role::Manager)->create(), 'Client injoignable');

        $this->assertSame(0, $coupon->fresh()->times_used);
        $this->assertSame(0, CouponUsage::count());
        $this->assertSame('RETOUR', Order::sole()->coupon_code);
    }

    public function test_public_codes_are_listed_in_the_cart(): void
    {
        Coupon::factory()->create(['code' => 'VISIBLE', 'is_public' => true, 'description' => 'Pour la rentrée']);
        Coupon::factory()->create(['code' => 'SECRET']);
        Coupon::factory()->create(['code' => 'PASSE', 'is_public' => true, 'ends_at' => now()->subHour()]);
        $this->addToCart(Product::factory()->create());

        $this->get('/panier')->assertOk()
            ->assertSeeText('Voir les codes disponibles (1)')
            ->assertSee('value="VISIBLE"', false)
            ->assertSeeText('Pour la rentrée')
            ->assertDontSee('value="SECRET"', false)
            ->assertDontSee('value="PASSE"', false);
    }

    public function test_managers_create_codes_and_used_codes_cannot_be_deleted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(CreateCoupon::class)
            ->fillForm([
                'code' => 'rentree',
                'type' => CouponType::Fixed->value,
                'value' => 5000,
                'minimum_subtotal' => 25000,
                'usage_limit' => 100,
                'usage_limit_per_customer' => 1,
                'target' => CouponTarget::All->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $coupon = Coupon::sole();
        $this->assertSame(['RENTREE', CouponType::Fixed, 5000, 25000], [$coupon->code, $coupon->type, $coupon->value, $coupon->minimum_subtotal]);

        $unused = Coupon::factory()->create();
        $this->addToCart(Product::factory()->create(['price' => 30000]));
        $this->applyCoupon('RENTREE');
        $this->placeOrder();

        Livewire::test(ListCoupons::class)
            ->assertActionHidden(TestAction::make('delete')->table($coupon))
            ->assertActionVisible(TestAction::make('delete')->table($unused));

        $this->actingAs(User::factory()->staff(Role::Picker)->create());
        $this->get(CouponResource::getUrl('index'))->assertForbidden();
    }

    private function applyCoupon(string $code): TestResponse
    {
        return $this->post('/panier/code-promo', ['code' => $code]);
    }

    private function placeOrder(array $overrides = []): TestResponse
    {
        return $this->post('/commande', [
            'customer_name' => 'Koffi Yao',
            'phone' => '0701020304',
            'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2',
            'payment_method' => 'paiement_livraison',
            'terms' => '1',
            ...$overrides,
        ]);
    }

    private function addToCart(Product $product, int $quantity = 1): void
    {
        $this->post('/panier/articles', ['product_id' => $product->id, 'quantity' => $quantity]);

        if ($cart = Cart::whereNull('user_id')->latest('id')->first()) {
            $this->withCookie(CartManager::COOKIE, $cart->token);
        }
    }
}
