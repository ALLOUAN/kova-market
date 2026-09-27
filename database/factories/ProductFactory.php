<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\StockManager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(4, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'price' => fake()->numberBetween(10, 500) * 500,
            'stock' => fake()->numberBetween(10, 100),
            'rating' => 5,
            'reviews_count' => fake()->numberBetween(0, 100),
            'image' => 'assets/images/product-img/electronics/electronics-bg-trans-10-a-1.webp',
        ];
    }

    /**
     * Like every product, factory products sell through their default variant.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Product $product) => app(StockManager::class)->createDefaultVariant($product));
    }

    public function onSale(int $price = 108000, int $compareAt = 177000): static
    {
        return $this->state(['price' => $price, 'compare_at_price' => $compareAt]);
    }

    public function soldOut(): static
    {
        return $this->state(['stock' => 0]);
    }
}
