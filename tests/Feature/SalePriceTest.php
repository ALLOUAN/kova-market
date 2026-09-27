<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SalePriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('storefront');
    }

    public function test_a_sale_planned_for_tomorrow_does_not_apply_today(): void
    {
        $product = $this->productOnSale(startsAt: now()->addDay(), endsAt: now()->addDays(3));

        $this->assertSame([50000, null], [$product->price, $product->compare_at_price]);
        $this->get('/produit/casque')->assertOk()->assertSeeText("50\u{00A0}000\u{00A0}FCFA")->assertDontSeeText("40\u{00A0}000\u{00A0}FCFA");

        // The cart and the order charge the normal price too.
        $this->post('/panier/articles', ['product_id' => $product->id]);
        $this->withCookie('kova_cart', Cart::sole()->token);
        $this->get('/panier')->assertSeeText("50\u{00A0}000\u{00A0}FCFA l’unité");

        // Tomorrow the reduced price applies, crossed-out normal price and countdown included.
        $this->travel(1)->days();
        $this->artisan('catalog:refresh-sale-prices')->assertSuccessful();

        $product->refresh();
        $this->assertSame([40000, 50000, 20], [$product->price, $product->compare_at_price, $product->discountPercentage()]);
        $this->assertTrue($product->hasCountdown());
        $this->get('/produit/casque')->assertSeeTextInOrder(["50\u{00A0}000\u{00A0}FCFA", "40\u{00A0}000\u{00A0}FCFA"]);
    }

    public function test_the_normal_price_comes_back_when_the_sale_ends(): void
    {
        $product = $this->productOnSale(startsAt: null, endsAt: now()->addHour());
        $this->assertSame(40000, $product->price);

        $this->post('/panier/articles', ['product_id' => $product->id]);
        $this->withCookie('kova_cart', Cart::sole()->token);

        $this->travel(2)->hours();

        // The order is placed at the normal price even before the scheduler refreshed the product.
        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1000, 'is_active' => true]);
        $this->placeOrder($zone->communes()->create(['name' => 'Cocody']));
        $this->assertSame([50000, 51000], [Order::sole()->items->sole()->unit_price, Order::sole()->total]);

        $this->artisan('catalog:refresh-sale-prices');
        $product->refresh();
        $this->assertSame([50000, null], [$product->price, $product->compare_at_price]);
        $this->assertFalse($product->isOnSale());
        $this->assertFalse($product->hasCountdown());
    }

    public function test_managers_date_a_sale_on_creation_and_on_each_variant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());
        Storage::fake('storefront');

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Montre GT',
                'slug' => 'montre-gt',
                'category_id' => Category::factory()->create()->id,
                'price' => 90000,
                'compare_at_price' => 120000,
                'sale_starts_at' => now()->addDays(9)->format('Y-m-d 08:00'),
                'sale_ends_at' => now()->addDays(11)->format('Y-m-d 20:00'),
                'stock' => 5,
                'image' => UploadedFile::fake()->image('montre.jpg'),
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'montre-gt')->sole();
        $variant = $product->defaultVariant;
        $this->assertSame([now()->addDays(9)->format('Y-m-d 08:00'), now()->addDays(11)->format('Y-m-d 20:00')], [$variant->sale_starts_at->format('Y-m-d H:i'), $variant->sale_ends_at->format('Y-m-d H:i')]);
        $this->assertSame(120000, $variant->currentPrice());

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make('edit')->table($variant), ['sale_starts_at' => null, 'sale_ends_at' => now()->addDay()->format('Y-m-d H:i')])
            ->assertHasNoFormErrors();

        $this->assertSame(90000, $variant->fresh()->currentPrice());
        $this->assertSame([90000, 120000], [$product->fresh()->price, $product->fresh()->compare_at_price]);
    }

    private function productOnSale(?\DateTimeInterface $startsAt, ?\DateTimeInterface $endsAt): Product
    {
        $product = Product::factory()->create(['name' => 'Casque', 'slug' => 'casque', 'price' => 40000, 'compare_at_price' => 50000, 'stock' => 10]);
        $product->defaultVariant->update(['sale_starts_at' => $startsAt, 'sale_ends_at' => $endsAt]);

        return $product->refresh();
    }

    private function placeOrder(Commune $commune): void
    {
        $this->post('/commande', [
            'customer_name' => 'Koffi Yao',
            'phone' => '0701020304',
            'commune_id' => $commune->id,
            'district' => 'Riviera',
            'payment_method' => 'paiement_livraison',
            'terms' => '1',
        ])->assertRedirect();
    }
}
