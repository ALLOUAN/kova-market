<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\SaleUnit;
use App\Enums\StockMovementReason;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Products sold by weight, volume or local unit (Mon Marché): cart, order, stock and API count base units
 * (grams, millilitres) and price them per displayed unit, on the server.
 */
class SaleUnitTest extends TestCase
{
    use RefreshDatabase;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 500, 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
    }

    public function test_tomatoes_sold_by_the_kilo_go_from_the_cart_to_the_order_and_the_stock(): void
    {
        // 1 000 FCFA/kg, 50 kg in stock (grams).
        $tomatoes = $this->kilo(['name' => 'Tomates fraîches', 'price' => 1000, 'stock' => 50_000]);

        // A bare "add" puts one step (250 g); the quantity then follows the 250 g steps.
        $this->post('/panier/articles', ['product_id' => $tomatoes->id])->assertSessionHas('cart_status', '« Tomates fraîches » a été ajouté au panier (0,25 kg au total).');
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
        $item = Cart::sole()->items()->sole();

        $this->patch("/panier/articles/{$item->id}", ['quantity' => 1800])->assertSessionHas('cart_status', 'Quantité ajustée à 1,75 kg : ce produit se vend par 0,25 kg.');
        $this->assertSame(1750, $item->fresh()->quantity);

        $summary = app(CartManager::class)->summary();
        $this->assertSame([1750, '1,75 kg'], [$summary->subtotal, $summary->lines->sole()->quantityLabel()]);
        // One article in the cart, not 1 750.
        $this->assertSame(1, $summary->count());

        $this->post('/commande', $this->details())->assertRedirect();

        $order = Order::sole();
        $line = $order->items->sole();
        $this->assertSame([1000, 1750, 1750, SaleUnit::Kilogram], [$line->unit_price, $line->quantity, $line->line_total, $line->sale_unit]);
        $this->assertSame(["1,75 kg × 1\u{00A0}000\u{00A0}FCFA/kg", '1,75 kg'], [$line->pricing(), $line->quantityLabel()]);
        $this->assertSame([1750, 500, 2250], [$order->subtotal, $order->shipping_fee, $order->total]);

        // 50 kg − 1,75 kg = 48,25 kg, with a sale movement of 1 750 g.
        $variant = $tomatoes->defaultVariant->fresh();
        $this->assertSame(48_250, $variant->stock);
        $this->assertSame([-1750, StockMovementReason::Sale], [$variant->stockMovements()->first()->quantity, $variant->stockMovements()->first()->reason]);

        // The order keeps its unit when the product is later sold otherwise.
        $tomatoes->update(['sale_unit' => SaleUnit::Piece]);
        $this->assertSame('1,75 kg', $line->fresh()->quantityLabel());
    }

    public function test_the_stock_limits_a_weighed_line_and_a_cancelled_order_gives_it_back(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $fish = $this->kilo(['name' => 'Capitaine', 'price' => 3000, 'stock' => 1900]);

        $this->post('/panier/articles', ['product_id' => $fish->id, 'quantity' => 5000]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
        // 1,9 kg in stock, sold by 250 g: 1,75 kg at most.
        $this->assertSame(1750, Cart::sole()->items()->sole()->quantity);

        $this->post('/commande', $this->details())->assertRedirect();
        $order = Order::sole();
        $this->assertSame(5250, $order->subtotal);
        $this->assertSame(150, $fish->defaultVariant->fresh()->stock);

        // 150 g left, less than the 250 g minimum: no longer orderable.
        $this->post('/panier/articles', ['product_id' => $fish->id])->assertSessionHas('cart_error');

        $manager = User::factory()->staff(Role::Manager)->create();
        app(OrderStatusManager::class)->move($order, OrderStatus::Cancelled, $manager, 'Client injoignable');
        $this->assertSame(1900, $fish->defaultVariant->fresh()->stock);
    }

    public function test_a_delivered_weighed_line_counts_as_one_sale(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $rice = $this->kilo(['name' => 'Riz local', 'price' => 750, 'stock' => 100_000]);

        $this->post('/panier/articles', ['product_id' => $rice->id, 'quantity' => 5000]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
        $this->post('/commande', $this->details());

        $order = Order::sole();
        $this->assertSame(3750, $order->subtotal);

        $manager = User::factory()->staff(Role::Manager)->create();
        foreach ([OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OutForDelivery, OrderStatus::Delivered] as $status) {
            $order = app(OrderStatusManager::class)->move($order->fresh(), $status, $manager);
        }

        $this->assertSame(1, $rice->fresh()->sold_count);
    }

    public function test_local_units_and_low_stock_thresholds(): void
    {
        $heaps = Product::factory()->create(['name' => 'Gombo', 'price' => 500, 'stock' => 12, 'sale_unit' => SaleUnit::Local, 'unit_label' => 'tas']);
        $this->assertSame(['3 tas', ' / tas'], [$heaps->saleQuantity()->format(3), $heaps->saleQuantity()->priceSuffix()]);

        // The general threshold (5) counts displayed units: 5 kg for a product sold by weight.
        config(['storefront.product_card.limited_stock_threshold' => 5]);
        $low = $this->kilo(['name' => 'Oignons', 'stock' => 4000]);
        $plenty = $this->kilo(['name' => 'Carottes', 'stock' => 9000]);
        $this->assertTrue($low->hasLimitedStock());
        $this->assertFalse($plenty->hasLimitedStock());
        $this->assertSame(['Oignons'], Product::lowStock(5)->whereKey([$low->id, $plenty->id])->pluck('name')->all());
        $this->assertSame('5 kg', $low->defaultVariant->lowStockThresholdLabel());
    }

    public function test_the_storefront_shows_the_price_per_kilo_and_the_weights_to_choose(): void
    {
        $tomatoes = $this->kilo(['name' => 'Tomates fraîches', 'slug' => 'tomates-fraiches', 'price' => 1000, 'stock' => 3000]);

        $this->get('/produit/tomates-fraiches')->assertOk()
            ->assertSee('<span class="kova-price-unit"> / kg</span>', false)
            ->assertSeeText('Vendu au poids, par 0,25 kg à partir de 0,25 kg.')
            ->assertSee('<option value="1750"', false)
            ->assertSeeText("1,75 kg — 1\u{00A0}750\u{00A0}FCFA")
            // Not more than the 3 kg in stock.
            ->assertDontSee('<option value="3250"', false)
            ->assertSee('"unitCode":"KGM"', false);

        $this->post('/panier/articles', ['product_id' => $tomatoes->id, 'quantity' => 1500]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);

        $this->get('/panier')->assertOk()
            ->assertSeeText("1\u{00A0}000\u{00A0}FCFA / kg")
            ->assertSee('<option value="1500" selected', false)
            ->assertSeeText("1\u{00A0}500\u{00A0}FCFA");

        $this->post('/commande', $this->details());
        $order = Order::sole();

        $this->get("/commande/{$order->number}/merci")->assertOk()->assertSeeText('1,5 kg × Tomates fraîches');
    }

    public function test_api_clients_get_the_unit_and_readable_quantities(): void
    {
        $tomatoes = $this->kilo(['name' => 'Tomates', 'slug' => 'tomates', 'price' => 1000, 'stock' => 10_000]);

        $this->getJson('/api/v1/products/tomates')->assertOk()
            ->assertJsonPath('data.sale_unit.code', 'kg')
            ->assertJsonPath('data.sale_unit.factor', 1000)
            ->assertJsonPath('data.sale_unit.step', 250)
            ->assertJsonPath('data.sale_unit.price_suffix', ' / kg');

        $token = $this->postJson('/api/v1/cart/items', ['variant_id' => $tomatoes->defaultVariant->id, 'quantity' => 1500])->json('data.token');
        $this->getJson('/api/v1/cart', [CartManager::HEADER => $token])->assertOk()
            ->assertJsonPath('data.lines.0.quantity_label', '1,5 kg')
            ->assertJsonPath('data.lines.0.total', 1500);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function kilo(array $attributes): Product
    {
        return Product::factory()->create(['sale_unit' => SaleUnit::Kilogram, ...$attributes]);
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
