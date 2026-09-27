<?php

namespace Tests\Feature;

use App\Enums\StockMovementReason;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Services\Catalog\InsufficientStock;
use App\Services\Catalog\StockManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    private StockManager $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stock = app(StockManager::class);
    }

    public function test_every_product_sells_through_a_default_variant_carrying_its_price_and_stock(): void
    {
        $product = Product::factory()->onSale(price: 108000, compareAt: 177000)->create(['stock' => 12]);

        $variant = $product->defaultVariant;
        $this->assertSame('KM-'.str_pad((string) $product->id, 6, '0', STR_PAD_LEFT), $variant->sku);
        $this->assertSame([108000, 177000, 12], [$variant->price, $variant->compare_at_price, $variant->stock]);
        $this->assertSame(StockMovementReason::Initial, $variant->stockMovements()->first()->reason);
        $this->assertSame('Modèle unique', $variant->label());
    }

    public function test_a_stock_change_is_always_recorded_as_a_movement_with_its_author(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $manager = User::factory()->create();

        $movement = $this->stock->adjust($product->defaultVariant, -3, StockMovementReason::Adjustment, $manager, 'Casse');

        $this->assertSame([-3, 7, 'Casse'], [$movement->quantity, $movement->stock_after, $movement->note]);
        $this->assertTrue($movement->user->is($manager));
        $this->assertSame(7, $product->defaultVariant->fresh()->stock);
        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_the_stock_can_never_go_below_zero(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        try {
            $this->stock->adjust($product->defaultVariant, -3, StockMovementReason::Sale);
            $this->fail('Selling more than the stock must be refused.');
        } catch (InsufficientStock) {
            $this->assertSame(2, $product->defaultVariant->fresh()->stock);
            $this->assertSame(1, $product->defaultVariant->stockMovements()->count());
        }
    }

    public function test_an_inventory_count_records_only_the_difference(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->assertSame(4, $this->stock->setTo($product->defaultVariant, 14, StockMovementReason::Adjustment)->quantity);
        $this->assertNull($this->stock->setTo($product->defaultVariant, 14, StockMovementReason::Adjustment));
    }

    public function test_the_product_summary_follows_its_variants(): void
    {
        $product = Product::factory()->create(['price' => 100000, 'stock' => 5, 'variants_count' => 0]);

        $this->stock->createVariant($product, ['sku' => 'TV-65', 'price' => 150000, 'compare_at_price' => 180000], 3);
        $this->stock->createVariant($product, ['sku' => 'TV-43', 'price' => 80000, 'compare_at_price' => 95000], 0);

        $product->refresh();
        $this->assertSame(8, $product->stock);
        $this->assertSame(80000, $product->price);
        $this->assertSame(95000, $product->compare_at_price);
        $this->assertSame(150000, $product->price_max);
        $this->assertSame(3, $product->variants_count);
    }

    public function test_an_attribute_value_worn_by_a_variant_cannot_be_deleted(): void
    {
        $color = ProductAttribute::create(['name' => 'Couleur', 'slug' => 'couleur']);
        $black = $color->values()->create(['value' => 'Noir']);
        Product::factory()->create()->defaultVariant->attributeValues()->attach($black);

        $this->expectException(ValidationException::class);

        $black->delete();
    }

    public function test_existing_products_get_their_default_variant_without_changing_the_storefront(): void
    {
        $migration = require database_path('migrations/2026_09_28_100300_create_default_variants_for_existing_products.php');
        $migration->down();
        DB::table('products')->insert([
            'category_id' => Category::factory()->create()->id, 'name' => 'Enceinte', 'slug' => 'enceinte',
            'price' => 30000, 'price_max' => 45000, 'compare_at_price' => 40000, 'stock' => 6, 'variants_count' => 4,
            'image' => 'assets/images/enceinte.webp', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'enceinte')->firstOrFail();
        $this->assertSame(1, $product->variants()->count());
        $this->assertSame([30000, 40000, 6], [$product->defaultVariant->price, $product->defaultVariant->compare_at_price, $product->defaultVariant->stock]);
        $this->assertSame([45000, 4], [$product->price_max, $product->variants_count]);
        $this->assertSame(6, $product->stockMovements()->sole()->stock_after);
    }
}
