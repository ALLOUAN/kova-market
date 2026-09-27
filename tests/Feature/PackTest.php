<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\StockMovementReason;
use App\Filament\Resources\Bundles\Pages\CreateBundle;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Catalog\Import\CatalogImporter;
use App\Services\Catalog\StockManager;
use App\Services\Orders\OrderStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

class PackTest extends TestCase
{
    use RefreshDatabase;

    private Product $console;

    private Product $manette;

    private Commune $cocody;

    protected function setUp(): void
    {
        parent::setUp();

        $this->console = Product::factory()->create(['name' => 'Console', 'price' => 300000, 'stock' => 3]);
        $this->manette = Product::factory()->create(['name' => 'Manette', 'price' => 30000, 'stock' => 10]);
        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1000, 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
    }

    public function test_a_pack_stock_is_what_its_components_allow(): void
    {
        $pack = $this->pack();

        // 3 consoles → 3 packs; 10 controllers at 2 per pack → 5 packs.
        $this->assertSame(3, $pack->fresh()->stock);

        app(StockManager::class)->adjust($this->manette->defaultVariant, -6, StockMovementReason::Adjustment);
        $this->assertSame(2, $pack->fresh()->stock);

        $this->console->update(['is_active' => false]);
        $this->assertSame(0, $pack->fresh()->stock);
        $this->get('/produit/pack-gaming')->assertOk()->assertSeeText('Épuisé : soyez prévenu de son retour');
    }

    public function test_ordering_a_pack_takes_each_component_and_keeps_the_contents_on_the_line(): void
    {
        $pack = $this->pack();

        $this->get('/produit/pack-gaming')->assertOk()->assertSeeText('Ce pack contient')->assertSeeTextInOrder(['1 ×', 'Console', '2 ×', 'Manette']);

        $this->addToCart($pack, 2);
        $this->placeOrder()->assertRedirect();

        $order = Order::sole();
        $line = $order->items->sole();
        $this->assertSame(['Pack Gaming', 320000, 640000], [$line->product_name, $line->unit_price, $line->line_total]);
        $this->assertSame('Contient : 1 × Console, 2 × Manette', $line->contentsSummary());
        $this->assertSame([1, 6, 1], [$this->console->defaultVariant->fresh()->stock, $this->manette->defaultVariant->fresh()->stock, $pack->fresh()->stock]);
        $this->assertSame('Commande '.$order->number.' — pack « Pack Gaming »', $this->manette->defaultVariant->stockMovements()->first()->note);

        // A cancellation gives the components back.
        $this->seed(RolesAndPermissionsSeeder::class);
        app(OrderStatusManager::class)->move($order, OrderStatus::Cancelled, User::factory()->staff(Role::Manager)->create(), 'Test');
        $this->assertSame([3, 10, 3], [$this->console->defaultVariant->fresh()->stock, $this->manette->defaultVariant->fresh()->stock, $pack->fresh()->stock]);

        // The frozen contents survive a change of the pack.
        $pack->bundleItems()->delete();
        $this->assertSame('Contient : 1 × Console, 2 × Manette', $line->fresh()->contentsSummary());
    }

    public function test_a_pack_sold_out_meanwhile_is_refused_at_checkout(): void
    {
        $pack = $this->pack();
        $this->addToCart($pack, 2);

        app(StockManager::class)->adjust($this->console->defaultVariant, -2, StockMovementReason::Adjustment);

        $this->placeOrder()->assertRedirect('/panier')->assertSessionHas('cart_error', 'Il ne reste que 1 « Pack Gaming » : ajustez la quantité pour commander.');
        $this->assertSame(0, Order::count());
        $this->assertSame(10, $this->manette->defaultVariant->fresh()->stock);
    }

    public function test_managers_build_a_pack_from_variants(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());
        Storage::fake('storefront');

        Livewire::test(CreateBundle::class)
            ->fillForm([
                'name' => 'Pack Gaming',
                'slug' => 'pack-gaming',
                'category_id' => Category::factory()->create()->id,
                'price' => 320000,
                'compare_at_price' => 360000,
                'image' => UploadedFile::fake()->image('pack.jpg'),
                'is_active' => true,
                'bundleItems' => [
                    ['product_variant_id' => $this->console->defaultVariant->id, 'quantity' => 1],
                    ['product_variant_id' => $this->manette->defaultVariant->id, 'quantity' => 2],
                ],
            ])
            ->assertSeeText("360\u{00A0}000\u{00A0}FCFA")
            ->call('create')
            ->assertHasNoFormErrors();

        $pack = Product::where('slug', 'pack-gaming')->sole();
        $this->assertTrue($pack->is_bundle);
        $this->assertSame('PACK-'.str_pad((string) $pack->id, 6, '0', STR_PAD_LEFT), $pack->defaultVariant->sku);
        $this->assertSame([320000, 360000, 3], [$pack->price, $pack->compare_at_price, $pack->stock]);

        // Packs stay out of the product list, where stock is adjusted by hand.
        Livewire::test(ListProducts::class)->assertCanNotSeeTableRecords([$pack])->assertCanSeeTableRecords([$this->console]);
    }

    public function test_the_csv_import_leaves_packs_alone(): void
    {
        $pack = $this->pack();
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, "sku;produit;prix;stock\n{$pack->defaultVariant->sku};Pack Gaming;1000;50");

        $report = app(CatalogImporter::class)->import($path, null);

        $this->assertSame([2 => 'Les packs ne s’importent pas : gérez-les dans Promotions › Packs.'], $report->errors);
        $this->assertSame(3, $this->console->defaultVariant->fresh()->stock);
    }

    private function pack(): Product
    {
        $pack = Product::factory()->create(['name' => 'Pack Gaming', 'slug' => 'pack-gaming', 'price' => 320000, 'stock' => 0, 'is_bundle' => true]);
        $pack->bundleItems()->createMany([
            ['product_variant_id' => $this->console->defaultVariant->id, 'quantity' => 1, 'position' => 1],
            ['product_variant_id' => $this->manette->defaultVariant->id, 'quantity' => 2, 'position' => 2],
        ]);
        app(StockManager::class)->refreshPack($pack);

        return $pack->refresh();
    }

    private function addToCart(Product $product, int $quantity): void
    {
        $this->post('/panier/articles', ['product_id' => $product->id, 'quantity' => $quantity]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);
    }

    private function placeOrder(): TestResponse
    {
        return $this->post('/commande', [
            'customer_name' => 'Koffi Yao',
            'phone' => '0701020304',
            'commune_id' => $this->cocody->id,
            'district' => 'Riviera',
            'payment_method' => 'paiement_livraison',
            'terms' => '1',
        ]);
    }
}
