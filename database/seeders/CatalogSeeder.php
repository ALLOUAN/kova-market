<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Seeds the demo storefront catalog (categories, brands, products, collections, promotions).
 */
class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = require database_path('seeders/data/catalog.php');

        $categories = $this->seedCategories($data['categories']);

        $brands = collect($data['brands'])
            ->mapWithKeys(fn (array $brand, int $position) => [
                $brand['name'] => Brand::create([...$brand, 'slug' => Str::slug($brand['name']), 'position' => $position]),
            ]);

        $products = collect($data['products'])->map(fn (array $product, string $name) => Product::create([
            ...Arr::except($product, ['category', 'brand', 'sale_ends_in_days']),
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => $categories[$product['category']]->id,
            'brand_id' => isset($product['brand']) ? $brands[$product['brand']]->id : null,
            'sale_ends_at' => isset($product['sale_ends_in_days']) ? now()->addDays($product['sale_ends_in_days']) : null,
        ]));

        foreach ($data['collections'] as $slug => $collection) {
            Collection::create([
                'name' => $collection['name'],
                'slug' => $slug,
                'ends_at' => $collection['ends_in_days'] ? now()->addDays($collection['ends_in_days']) : null,
            ])->products()->attach(
                collect($collection['products'])->mapWithKeys(fn (string $name, int $position) => [
                    $products[$name]->id => ['position' => $position],
                ]),
            );
        }

        foreach ($data['promotions'] as $promotion) {
            Promotion::create([
                ...Arr::except($promotion, ['starts_in_days', 'ends_in_days']),
                'starts_at' => now()->subDay()->addDays($promotion['starts_in_days'])->startOfDay(),
                'ends_at' => now()->subDay()->addDays($promotion['ends_in_days'])->endOfDay(),
            ]);
        }
    }

    /**
     * Create the category tree and return every category keyed by name.
     *
     * @param  array<int, array<string, mixed>|string>  $nodes
     * @return array<string, Category>
     */
    private function seedCategories(array $nodes, ?Category $parent = null): array
    {
        $created = [];

        foreach ($nodes as $position => $node) {
            $node = is_string($node) ? ['name' => $node] : $node;

            $category = Category::create([
                ...Arr::except($node, 'children'),
                'parent_id' => $parent?->id,
                'slug' => Str::slug(($parent ? $parent->slug.' ' : '').$node['name']),
                'position' => $position,
            ]);

            // Leaf names repeat across branches, so only the first occurrence of a name is addressable.
            $created += [$category->name => $category];
            $created += $this->seedCategories($node['children'] ?? [], $category);
        }

        return $created;
    }
}
