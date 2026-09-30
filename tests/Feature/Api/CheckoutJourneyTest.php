<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-160 acceptance: a whole purchase (catalog → cart → order → tracking) goes through the API only.
 */
class CheckoutJourneyTest extends TestCase
{
    use RefreshDatabase;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'delay_label' => 'J+1', 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
    }

    public function test_a_guest_buys_from_the_catalog_to_the_tracking_through_the_api_only(): void
    {
        $category = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);
        Product::factory()->for($category)->create(['name' => 'Enceinte JBL', 'slug' => 'enceinte-jbl', 'price' => 45000, 'stock' => 10]);

        $this->assertContains('audio', $this->getJson('/api/v1/categories')->assertOk()->json('data.*.slug'));
        $this->getJson('/api/v1/categories/audio/products')->assertOk()->assertJsonPath('data.0.slug', 'enceinte-jbl');

        $variantId = $this->getJson('/api/v1/products/enceinte-jbl')
            ->assertOk()
            ->assertJsonPath('data.price', 45000)
            ->json('data.variants.0.id');

        $token = $this->postJson('/api/v1/cart/items', ['variant_id' => $variantId, 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.subtotal', 90000)
            ->json('data.token');

        $this->assertNotNull($token);
        $cart = ['X-Cart-Token' => $token];

        $communeId = $this->getJson('/api/v1/communes')->assertOk()->assertJsonPath('data.0.name', 'Cocody')->json('data.0.id');

        $this->putJson('/api/v1/cart/commune', ['commune_id' => $communeId], $cart)
            ->assertOk()
            ->assertJsonPath('data.shipping_fee', 1500)
            ->assertJsonPath('data.total', 91500);

        $number = $this->postJson('/api/v1/orders', [
            'customer_name' => 'Awa Koné',
            'phone' => '07 01 02 03 04',
            'commune_id' => $communeId,
            'district' => 'Riviera 2',
            'landmark' => 'Près de la pharmacie',
            'payment_method' => 'paiement_livraison',
            'terms' => true,
        ], $cart)
            ->assertCreated()
            ->assertJsonPath('data.status.code', OrderStatus::Received->value)
            ->assertJsonPath('data.source', 'mobile')
            ->assertJsonPath('data.total', 91500)
            ->assertJsonPath('data.items.0.product_name', 'Enceinte JBL')
            ->json('data.number');

        $this->getJson('/api/v1/cart', $cart)->assertOk()->assertJsonPath('data.item_count', 0);

        $tracking = $this->postJson('/api/v1/orders/track', ['number' => strtolower($number), 'phone' => '+225 07 01 02 03 04'])
            ->assertOk()
            ->assertJsonPath('data.number', $number)
            ->assertJsonPath('data.status.label', 'Reçue')
            ->assertJsonPath('data.commune_name', 'Cocody')
            ->assertJsonPath('data.history.0.status', OrderStatus::Received->value);

        // Public tracking never shows the address details, the phone number or the e-mail.
        $this->assertArrayNotHasKey('district', $tracking->json('data'));
        $this->assertArrayNotHasKey('phone', $tracking->json('data'));

        $order = Order::sole();
        $this->assertSame(['mobile', 8], [$order->source, Product::sole()->defaultVariant->stock]);
    }

    public function test_tracking_gives_the_same_answer_for_a_wrong_number_or_phone(): void
    {
        $this->postJson('/api/v1/orders/track', ['number' => 'KM-000000-0000', 'phone' => '0701020304'])
            ->assertNotFound()
            ->assertJsonPath('message', 'Commande introuvable. Vérifiez le numéro et le téléphone indiqués lors de la commande.');
    }

    public function test_order_refusals_come_back_as_json_with_their_message(): void
    {
        $this->postJson('/api/v1/orders', [])->assertUnprocessable()->assertJsonValidationErrors(['customer_name', 'phone', 'commune_id', 'terms']);

        $details = [
            'customer_name' => 'Awa Koné', 'phone' => '0701020304', 'commune_id' => $this->cocody->id,
            'district' => 'Riviera 2', 'payment_method' => 'paiement_livraison', 'terms' => true,
        ];

        $this->postJson('/api/v1/orders', $details)->assertUnprocessable()->assertJsonPath('message', 'Votre panier est vide.');

        $product = Product::factory()->create(['stock' => 3]);
        $token = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->json('data.token');
        $product->defaultVariant->forceFill(['stock' => 0])->save();

        $this->postJson('/api/v1/orders', $details, [CartManager::HEADER => $token])
            ->assertUnprocessable()
            ->assertJsonPath('message', "« {$product->name} » vient d’être épuisé : retirez-le du panier pour commander.");

        $this->assertSame(0, Order::count());
    }
}
