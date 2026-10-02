<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Catalog\StockManager;
use Database\Seeders\DeliverySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_adds_a_product_and_finds_it_again_through_the_cookie(): void
    {
        $product = Product::factory()->create(['name' => 'Enceinte JBL', 'price' => 45000, 'stock' => 10]);

        $this->addToCart(['variant_id' => $product->defaultVariant->id, 'quantity' => 2])
            ->assertSessionHas('cart_status', '« Enceinte JBL » a été ajouté au panier (2 au total).')
            ->assertCookie(CartManager::COOKIE);

        $this->get('/panier')->assertOk()->assertSeeText('Enceinte JBL')->assertSeeText("90\u{00A0}000\u{00A0}FCFA");
        $this->assertSame(1, Cart::count());
    }

    public function test_quantities_are_capped_to_the_stock(): void
    {
        $variant = Product::factory()->create(['stock' => 3])->defaultVariant;

        $this->addToCart(['variant_id' => $variant->id, 'quantity' => 2]);
        $this->addToCart(['variant_id' => $variant->id, 'quantity' => 5])
            ->assertSessionHas('cart_status', fn (string $message) => str_contains($message, '(3 au total)'));

        $item = Cart::sole()->items()->sole();
        $this->patch("/panier/articles/{$item->id}", ['quantity' => 9])->assertSessionHas('cart_status', 'Quantité ramenée à 3 : c’est le stock disponible.');
        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_sold_out_or_retired_products_cannot_be_added(): void
    {
        $soldOut = Product::factory()->create(['stock' => 0]);
        $retired = Product::factory()->create(['is_active' => false]);

        $this->addToCart(['product_id' => $soldOut->id])->assertSessionHas('cart_error', 'Ce produit est épuisé.');
        $this->addToCart(['product_id' => $retired->id])->assertSessionHas('cart_error', 'Ce produit n’est plus en vente.');
        $this->assertSame(0, Cart::count());
    }

    public function test_a_line_that_became_unavailable_is_flagged_and_left_out_of_the_total(): void
    {
        $kept = Product::factory()->create(['name' => 'Casque', 'price' => 20000, 'stock' => 5]);
        $gone = Product::factory()->create(['name' => 'Montre', 'price' => 80000, 'stock' => 5]);
        $this->addToCart(['product_id' => $kept->id]);
        $this->addToCart(['product_id' => $gone->id]);

        $gone->update(['is_active' => false]);

        $this->get('/panier')
            ->assertSeeText('Ce produit n’est plus en vente : il ne sera pas commandé.')
            ->assertSeeText('Sous-total (1 article)')
            ->assertSeeText("20\u{00A0}000\u{00A0}FCFA");
    }

    public function test_the_price_shown_is_always_the_current_price(): void
    {
        $product = Product::factory()->create(['price' => 57300]);
        $this->addToCart(['product_id' => $product->id]);

        $product->defaultVariant->update(['price' => 42700]);

        $this->get('/panier')->assertSeeText("42\u{00A0}700\u{00A0}FCFA")->assertDontSeeText("57\u{00A0}300\u{00A0}FCFA");
    }

    public function test_a_line_of_another_cart_cannot_be_changed(): void
    {
        $other = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $item = $other->items()->create(['product_variant_id' => Product::factory()->create()->defaultVariant->id, 'quantity' => 1]);

        $this->patch("/panier/articles/{$item->id}", ['quantity' => 3])->assertNotFound();
        $this->delete("/panier/articles/{$item->id}")->assertNotFound();
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_the_commune_gives_the_delivery_fee_and_free_delivery_applies_above_the_threshold(): void
    {
        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true]);
        $cocody = $zone->communes()->create(['name' => 'Cocody']);
        Setting::store(['delivery.free_shipping_threshold' => 100000]);
        $product = Product::factory()->create(['price' => 60000, 'stock' => 5]);
        $this->addToCart(['product_id' => $product->id]);

        $this->get('/panier')->assertSeeText('Choisissez votre commune')->assertSeeText("Plus que 40\u{00A0}000\u{00A0}FCFA");

        $this->post('/panier/commune', ['commune_id' => $cocody->id])->assertRedirect('/panier');
        $this->get('/panier')->assertSeeText('Délai indicatif : J+1')->assertSeeText("61\u{00A0}500\u{00A0}FCFA");

        $this->addToCart(['product_id' => $product->id]);
        $this->get('/panier')->assertSeeText('Offerte')->assertSeeText("120\u{00A0}000\u{00A0}FCFA");
    }

    public function test_communes_of_closed_or_unpriced_zones_are_not_offered(): void
    {
        $this->seed(DeliverySeeder::class);
        $this->addToCart(['product_id' => Product::factory()->create()->id]);

        $this->get('/panier')->assertSeeText('Les zones de livraison seront bientôt disponibles.')->assertDontSee('<option value="'.Commune::first()->id.'"', false);
        $this->post('/panier/commune', ['commune_id' => Commune::first()->id])->assertSessionHas('cart_error', 'La livraison n’est pas disponible pour cette commune.');
    }

    public function test_the_guest_cart_joins_the_account_at_sign_in(): void
    {
        $user = User::factory()->customer()->create(['phone' => '0701020304']);
        $variant = Product::factory()->create(['stock' => 10])->defaultVariant;
        $owned = Cart::create(['token' => fake()->uuid(), 'user_id' => $user->id, 'expires_at' => now()->addDay()]);
        $owned->items()->create(['product_variant_id' => $variant->id, 'quantity' => 2]);
        $other = Product::factory()->create(['name' => 'Chargeur'])->defaultVariant;

        $this->addToCart(['variant_id' => $variant->id, 'quantity' => 3]);
        $this->addToCart(['variant_id' => $other->id]);
        $this->post('/login', ['login' => '0701020304', 'password' => 'password']);

        $this->assertSame(1, Cart::count());
        $this->assertSame([5, 1], [$owned->items()->where('product_variant_id', $variant->id)->value('quantity'), $owned->items()->where('product_variant_id', $other->id)->value('quantity')]);
        $this->get('/panier')->assertSeeText('Chargeur');
    }

    public function test_buy_now_goes_to_the_cart_and_variants_with_options_are_chosen_in_the_quick_view(): void
    {
        $simple = Product::factory()->create();
        $withOptions = Product::factory()->create();
        app(StockManager::class)->createVariant($withOptions, ['sku' => 'OPTION-2', 'price' => 1000], 1);

        $this->addToCart(['variant_id' => $simple->defaultVariant->id, 'buy_now' => 1])->assertRedirect('/panier');

        // Every card has an add-to-cart button; with several options it opens the quick view to choose one.
        $this->get('/boutique')->assertDontSeeText('Choisir une option')
            ->assertSee('data-quick-view-url="'.route('products.quick-view', $withOptions).'" data-product-url="'.$withOptions->url().'" aria-label="Ajouter « '.$withOptions->name.' » au panier (choisir une option)"', false);

        // The "+" adds the default variant in one click, options or not.
        $this->get('/boutique')->assertSee('class="kova-quick-add"', false);
        $this->addToCart(['product_id' => $withOptions->id])->assertSessionHas('cart_status');

        // Sent in the background from a card: the answer refreshes the header and the mini-cart, the page stays.
        $this->postJson('/panier/articles', ['product_id' => $simple->id])->assertOk()
            ->assertJsonStructure(['message', 'count', 'total', 'mini_cart']);
        $soldOut = Product::factory()->create(['stock' => 0]);
        $this->postJson('/panier/articles', ['product_id' => $soldOut->id])->assertUnprocessable()->assertJsonStructure(['message']);
    }

    public function test_expired_guest_carts_are_pruned_but_customer_carts_are_kept(): void
    {
        Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->subDay()]);
        Cart::create(['token' => fake()->uuid(), 'user_id' => User::factory()->create()->id, 'expires_at' => now()->subDay()]);

        $this->artisan('model:prune', ['--model' => [Cart::class]]);

        $this->assertSame(1, Cart::count());
        $this->assertNotNull(Cart::first()->user_id);
    }

    /**
     * Posts to the cart like a browser would: the guest cart cookie is sent back on the next requests.
     *
     * @param  array<string, mixed>  $data
     */
    private function addToCart(array $data): TestResponse
    {
        $response = $this->post('/panier/articles', $data);

        if ($cart = Cart::whereNull('user_id')->latest('id')->first()) {
            $this->withCookie(CartManager::COOKIE, $cart->token);
        }

        return $response;
    }

    public function test_the_header_shows_the_cart_count_and_total(): void
    {
        $product = Product::factory()->create(['price' => 30000, 'stock' => 5]);
        $this->addToCart(['product_id' => $product->id, 'quantity' => 2]);

        $this->get('/')->assertSee('<span class="access-box-count rbt-shiny" data-cart-count>2</span>', false)->assertSeeText("60\u{00A0}000\u{00A0}FCFA");
    }
}
