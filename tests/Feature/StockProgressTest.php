<?php

namespace Tests\Feature;

use App\Enums\SaleUnit;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stock left on product cards: a plain statement while the stock is comfortable, a warning when it is low, in the
 * product's own unit.
 */
class StockProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_comfortable_stock_is_simply_stated(): void
    {
        $product = Product::factory()->create(['stock' => 97, 'sold_count' => 97]);

        $this->blade('<x-product.stock-progress :product="$product" />', ['product' => $product])
            ->assertSeeText('97 en stock')
            ->assertDontSeeText('Plus que')
            ->assertDontSee('is-limited', false)
            ->assertSee('width: 50%', false);
    }

    public function test_a_low_stock_reads_as_a_warning(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->blade('<x-product.stock-progress :product="$product" />', ['product' => $product])
            ->assertSeeText('Plus que 2 en stock')
            ->assertSee('is-limited', false);
    }

    public function test_a_product_sold_by_weight_shows_its_unit(): void
    {
        $product = Product::factory()->create(['sale_unit' => SaleUnit::Kilogram, 'stock' => 25000]);

        $this->blade('<x-product.stock-progress :product="$product" />', ['product' => $product])
            ->assertSeeText('25 kg en stock')
            ->assertDontSeeText('25000');
    }
}
