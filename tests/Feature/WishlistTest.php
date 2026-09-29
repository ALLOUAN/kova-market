<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\Storefront\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Mes favoris": the heart adds or removes a product, for visitors (cookie) and customers (account); a visitor's
 * favourites join the account at sign-in.
 */
class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_adds_and_removes_a_favourite(): void
    {
        $product = Product::factory()->create(['name' => 'Casque Sony']);

        $this->get('/')->assertSee('action="'.route('wishlist.toggle', $product).'"', false);

        $response = $this->postJson(route('wishlist.toggle', $product))
            ->assertOk()
            ->assertJson(['added' => true, 'count' => 1, 'message' => '« Casque Sony » est dans vos favoris.'])
            ->assertCookie(Wishlist::COOKIE);
        $visitor = $response->getCookie(Wishlist::COOKIE, false)->getValue();

        $this->withUnencryptedCookie(Wishlist::COOKIE, $visitor);
        $this->get(route('wishlist.index'))->assertOk()->assertSeeText('Casque Sony')->assertSee('aria-pressed="true"', false);

        $this->postJson(route('wishlist.toggle', $product))->assertJson(['added' => false, 'count' => 0]);
        $this->get(route('wishlist.index'))->assertSeeText('Vous n’avez pas encore de favori');
    }

    public function test_the_favourites_of_a_visitor_join_the_account_at_sign_in(): void
    {
        $user = User::factory()->customer()->create(['phone' => '0701020304']);
        [$a, $b] = Product::factory()->count(2)->create();
        WishlistItem::create(['user_id' => $user->id, 'product_id' => $a->id]);
        $visitor = (string) str()->uuid();
        WishlistItem::create(['visitor' => $visitor, 'product_id' => $a->id]);
        WishlistItem::create(['visitor' => $visitor, 'product_id' => $b->id]);

        $this->withCookie(Wishlist::COOKIE, $visitor)
            ->post('/login', ['login' => '0701020304', 'password' => 'password']);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], WishlistItem::where('user_id', $user->id)->pluck('product_id')->all());
        $this->assertSame(0, WishlistItem::where('visitor', $visitor)->count());
    }

    public function test_a_customer_keeps_favourites_on_the_account_and_sees_only_products_on_sale(): void
    {
        $user = User::factory()->customer()->create();
        $onSale = Product::factory()->create(['name' => 'Montre connectée']);
        $retired = Product::factory()->create(['name' => 'Ancien modèle']);
        WishlistItem::create(['user_id' => $user->id, 'product_id' => $retired->id]);
        $retired->update(['is_active' => false]);

        $this->actingAs($user)->post(route('wishlist.toggle', $onSale))->assertRedirect();
        $this->assertTrue(WishlistItem::where('user_id', $user->id)->where('product_id', $onSale->id)->exists());

        $this->get(route('wishlist.index'))->assertSeeText('Montre connectée')->assertDontSeeText('Ancien modèle');
        $this->get(route('account.show'))->assertSee('href="'.route('wishlist.index').'"', false);

        // A product taken off the site cannot be added.
        $this->post(route('wishlist.toggle', $retired))->assertNotFound();
    }
}
