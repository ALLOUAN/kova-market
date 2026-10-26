<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Commande protégée" on the product page: it states only the guarantees the store really gives.
 */
class OrderProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_product_page_states_only_the_guarantees_the_store_gives(): void
    {
        config(['services.cinetpay.api_key' => null, 'storefront.contact.whatsapp' => '']);
        $product = Product::factory()->create(['slug' => 'ipad', 'return_days' => null]);

        $this->get('/produit/ipad')->assertOk()
            ->assertSeeText('Commande protégée')
            ->assertSeeText('Paiement à la livraison')
            ->assertSeeText('Commande suivie')
            // No online payment set up, no return window, no WhatsApp number: none of them is promised.
            ->assertDontSeeText('Paiement en ligne sécurisé')
            ->assertDontSeeText('jours pour changer d’avis')
            ->assertDontSeeText('Discuter avec nous')
            ->assertSee('href="'.route('pages.show', 'retours-et-remboursements').'"', false);

        config(['services.cinetpay.api_key' => 'key', 'services.cinetpay.api_password' => 'password', 'storefront.contact.whatsapp' => '2250506070809']);
        $product->update(['return_days' => 7]);

        $this->get('/produit/ipad')->assertOk()
            ->assertSeeText('Paiement en ligne sécurisé')
            ->assertSeeText('7 jours pour changer d’avis')
            ->assertSeeText('Discuter avec nous')
            ->assertSee('https://wa.me/2250506070809', false);
    }

    public function test_the_cart_states_the_same_guarantees_without_a_product_s_return_window(): void
    {
        config(['storefront.contact.whatsapp' => '2250506070809']);
        $product = Product::factory()->create(['stock' => 5, 'return_days' => 7]);
        $this->post('/panier/articles', ['variant_id' => $product->defaultVariant->id, 'quantity' => 1]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);

        $this->get('/panier')->assertOk()
            ->assertSeeText('Commande protégée')
            ->assertSeeText('Paiement à la livraison')
            ->assertDontSeeText('jours pour changer d’avis')
            ->assertSee('https://wa.me/2250506070809?text='.rawurlencode('Bonjour, j’ai une question sur ma commande.'), false);
    }
}
