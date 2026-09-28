<?php

namespace Tests\Feature\Api;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantities_are_capped_to_the_stock_and_a_zero_quantity_removes_the_line(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 3]);
        $response = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 5])
            ->assertCreated()
            ->assertJsonPath('data.lines.0.quantity', 3);
        $headers = [CartManager::HEADER => $response->json('data.token')];
        $line = $response->json('data.lines.0.id');

        $this->patchJson("/api/v1/cart/items/{$line}", ['quantity' => 2], $headers)->assertOk()->assertJsonPath('data.subtotal', 20000);
        $this->patchJson("/api/v1/cart/items/{$line}", ['quantity' => 0], $headers)->assertOk()->assertJsonCount(0, 'data.lines');
    }

    public function test_the_api_cart_sets_no_cookie(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->assertCreated()->assertCookieMissing(CartManager::COOKIE);
    }

    public function test_a_line_of_another_cart_is_not_reachable(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $line = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->json('data.lines.0.id');
        $otherToken = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->json('data.token');

        $this->deleteJson("/api/v1/cart/items/{$line}", [], [CartManager::HEADER => $otherToken])->assertNotFound();
        $this->deleteJson("/api/v1/cart/items/{$line}")->assertNotFound();
    }

    public function test_business_refusals_are_422_with_their_message(): void
    {
        $soldOut = Product::factory()->soldOut()->create();
        $this->postJson('/api/v1/cart/items', ['product_id' => $soldOut->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ce produit est épuisé.');

        $zone = DeliveryZone::create(['name' => 'Fermée', 'fee' => 2000, 'delay_label' => 'J+2', 'is_active' => false]);
        $closed = $zone->communes()->create(['name' => 'Bingerville']);
        $this->putJson('/api/v1/cart/commune', ['commune_id' => $closed->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La livraison n’est pas disponible pour cette commune.');
    }

    public function test_promo_codes_are_checked_like_on_the_storefront(): void
    {
        $product = Product::factory()->create(['price' => 20000, 'stock' => 5]);
        $headers = [CartManager::HEADER => $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->json('data.token')];
        Coupon::factory()->create(['code' => 'BIENVENUE', 'type' => CouponType::Percentage, 'value' => 10]);

        $this->postJson('/api/v1/cart/coupon', ['code' => 'INCONNU'], $headers)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ce code promo n’existe pas.');

        $this->postJson('/api/v1/cart/coupon', ['code' => 'bienvenue'], $headers)
            ->assertOk()
            ->assertJsonPath('data.coupon.code', 'BIENVENUE')
            ->assertJsonPath('data.discount', 2000)
            ->assertJsonPath('data.total', 18000);

        $this->deleteJson('/api/v1/cart/coupon', [], $headers)->assertOk()->assertJsonPath('data.coupon', null);
    }
}
