<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The site's addresses never show a database number: products by their slug, cart lines and saved addresses by a
 * random public id. The former numeric addresses answer 404.
 */
class PublicAddressesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_lines_are_reached_by_their_public_id_only(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->post('/panier/articles', ['product_id' => $product->id, 'quantity' => 1]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
        $item = Cart::sole()->items()->sole();

        $this->assertMatchesRegularExpression('/^[0-9a-z]{26}$/', $item->public_id);
        $this->get('/panier')->assertOk()
            ->assertSee('/panier/articles/'.$item->public_id, false)
            ->assertDontSee('/panier/articles/'.$item->id.'"', false);

        $this->patch('/panier/articles/'.$item->id, ['quantity' => 2])->assertNotFound();
        $this->patch('/panier/articles/'.$item->public_id, ['quantity' => 2])->assertRedirect('/panier');
        $this->assertSame(2, $item->fresh()->quantity);
    }

    public function test_favourites_and_comparison_use_the_product_slug(): void
    {
        $product = Product::factory()->create(['slug' => 'enceinte-jbl']);

        $this->assertStringEndsWith('/favoris/enceinte-jbl', route('wishlist.toggle', $product));
        $this->assertStringEndsWith('/comparer/enceinte-jbl', route('compare.toggle', $product));
        $this->postJson('/favoris/enceinte-jbl')->assertOk()->assertJson(['added' => true]);
        $this->postJson('/favoris/'.$product->id)->assertNotFound();
    }

    public function test_colour_swatches_carry_the_picture_of_their_colour_only(): void
    {
        Product::factory()->create(['name' => 'Casque', 'variants_count' => 15, 'colors' => [
            ['name' => 'Violet', 'hex' => '#bdb6d6', 'image' => 'uploads/products/casque-violet.webp'],
            ['name' => 'Bleu', 'hex' => '#486788', 'image' => null],
        ]]);

        $html = $this->get('/boutique')->assertOk()->getContent();

        preg_match('/<a[^>]*data-switcher-color="#bdb6d6"[^>]*>/', $html, $violet);
        preg_match('/<a[^>]*data-switcher-color="#486788"[^>]*>/', $html, $blue);
        $this->assertStringContainsString('casque-violet.webp', $violet[0] ?? '');
        // A colour without its own picture keeps the one shown: no data-src.
        $this->assertStringNotContainsString('data-src', $blue[0] ?? 'data-src');
        // The hand-typed variant total is no longer shown.
        $this->assertStringNotContainsString('autres', strip_tags((string) preg_replace('/.*?(<div class="rbt-color-select-area".*?<\/div>).*/s', '$1', $html)));
    }

    public function test_saved_addresses_are_reached_by_their_public_id_only(): void
    {
        $customer = User::factory()->customer()->create();
        $address = Address::create(['user_id' => $customer->id, 'label' => 'Maison', 'recipient_name' => 'Aya', 'phone' => '0701020304', 'district' => 'Riviera']);

        $this->actingAs($customer)->get('/compte/adresses')->assertOk()->assertSee('/compte/adresses/'.$address->public_id.'/modifier', false);
        $this->get('/compte/adresses/'.$address->id.'/modifier')->assertNotFound();
        $this->get('/compte/adresses/'.$address->public_id.'/modifier')->assertOk();
    }
}
