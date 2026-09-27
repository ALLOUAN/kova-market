<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductPriceMigrationTest extends TestCase
{
    use RefreshDatabase;

    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migration = require database_path('migrations/2026_09_27_190000_convert_product_prices_to_whole_fcfa.php');

        // Back to the decimal schema the production data was entered with.
        $this->migration->down();
    }

    public function test_decimal_prices_become_whole_fcfa_and_the_original_values_are_kept(): void
    {
        $this->insertProduct(['price' => 107999.60, 'price_max' => null, 'compare_at_price' => 177000.40]);

        $this->migration->up();

        $row = DB::table('products')->first();
        $this->assertEquals(108000, $row->price);
        $this->assertNull($row->price_max);
        $this->assertEquals(177000, $row->compare_at_price);
        $this->assertEquals(107999.60, (float) $row->price_legacy);
        $this->assertEquals(177000.40, (float) $row->compare_at_price_legacy);
    }

    public function test_rolling_back_restores_the_decimal_columns_untouched(): void
    {
        $this->insertProduct(['price' => 49999.99, 'price_max' => 60000.50, 'compare_at_price' => null]);

        $this->migration->up();
        $this->migration->down();

        $this->assertFalse(Schema::hasColumn('products', 'price_legacy'));
        $row = DB::table('products')->first();
        $this->assertEquals(49999.99, (float) $row->price);
        $this->assertEquals(60000.50, (float) $row->price_max);
        $this->assertNull($row->compare_at_price);

        $this->migration->up();
    }

    public function test_products_created_after_the_conversion_survive_a_rollback(): void
    {
        $this->migration->up();
        $this->insertProduct(['price' => 25000]);

        $this->migration->down();

        $this->assertEquals(25000, (float) DB::table('products')->value('price'));

        $this->migration->up();
    }

    /**
     * @param  array<string, float|null>  $prices
     */
    private function insertProduct(array $prices): void
    {
        DB::table('products')->insert([
            ...$prices,
            'category_id' => Category::factory()->create()->id,
            'name' => 'Casque audio',
            'slug' => 'casque-audio',
            'image' => 'assets/images/casque.webp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
