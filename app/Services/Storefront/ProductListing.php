<?php

namespace App\Services\Storefront;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Product lists of the storefront (F-020 to F-024): shop, category, brand and search pages share the same
 * search, filters, sorting and pagination. Every filter lives in the query string, so a list can be shared.
 */
class ProductListing
{
    public const PER_PAGE = 24;

    public const SORTS = [
        'pertinence' => 'Pertinence',
        'prix-croissant' => 'Prix croissant',
        'prix-decroissant' => 'Prix décroissant',
        'nouveautes' => 'Nouveautés',
        'popularite' => 'Popularité',
    ];

    /**
     * @return array{products: LengthAwarePaginator, filters: array<string, mixed>, facets: array<string, mixed>}
     */
    public function list(Request $request, ?Category $category = null, ?Brand $brand = null, ?ProductCollection $collection = null): array
    {
        $filters = $this->filters($request);

        // The scope is what the page is about (shop, category, brand, home selection, search); filters narrow it down.
        $scope = Product::query()->active()
            ->when($category, fn (Builder $query) => $query->whereIn('category_id', $category->descendantIds()))
            ->when($brand, fn (Builder $query) => $query->where('brand_id', $brand->getKey()))
            ->when($collection, fn (Builder $query) => $query->whereHas('collections', fn (Builder $query) => $query->whereKey($collection->getKey())))
            ->when($filters['q'] !== '', fn (Builder $query) => $query->whereKey($this->searchIds($filters['q'])));

        $products = $this->applyFilters(clone $scope, $filters);

        // A home selection keeps the order chosen in the back-office unless the visitor sorts it.
        if ($collection && $filters['sort'] === 'pertinence') {
            $products->orderBy(DB::table('collection_product')->select('position')
                ->whereColumn('collection_product.product_id', 'products.id')
                ->where('collection_product.collection_id', $collection->getKey()));
        }

        $this->applySort($products, $filters['sort']);

        return [
            'products' => $products->with('category')->paginate(self::PER_PAGE)->withQueryString(),
            'filters' => $filters,
            'facets' => $this->facets($scope, $category),
        ];
    }

    /**
     * @return array{q: string, sort: string, min: ?int, max: ?int, in_stock: bool, brands: list<int>, values: list<int>, categories: list<int>}
     */
    private function filters(Request $request): array
    {
        $min = $request->integer('prix_min') ?: null;
        $max = $request->integer('prix_max') ?: null;

        // A reversed range is understood rather than refused.
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $sort = (string) $request->query('tri', 'pertinence');

        return [
            'q' => trim((string) $request->query('q', '')),
            'sort' => array_key_exists($sort, self::SORTS) ? $sort : 'pertinence',
            'min' => $min,
            'max' => $max,
            'in_stock' => $request->boolean('en_stock'),
            'brands' => $this->ids($request->query('marques')),
            'values' => $this->ids($request->query('valeurs')),
            'categories' => $this->ids($request->query('categories')),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['min'] !== null, fn (Builder $query) => $query->where('price', '>=', $filters['min']))
            ->when($filters['max'] !== null, fn (Builder $query) => $query->where('price', '<=', $filters['max']))
            ->when($filters['in_stock'], fn (Builder $query) => $query->where('stock', '>', 0))
            ->when($filters['brands'] !== [], fn (Builder $query) => $query->whereIn('brand_id', $filters['brands']))
            ->when($filters['categories'] !== [], fn (Builder $query) => $query->whereIn(
                'category_id',
                Category::query()->whereKey($filters['categories'])->get()->flatMap->descendantIds()->all(),
            ))
            // Values of one attribute are alternatives (Noir OR Blanc); different attributes all apply (Noir AND 128 Go).
            ->when($filters['values'] !== [], function (Builder $query) use ($filters): void {
                AttributeValue::query()->whereKey($filters['values'])->get()->groupBy('attribute_id')
                    ->each(fn (Collection $values) => $query->whereHas(
                        'variants.attributeValues',
                        fn (Builder $query) => $query->whereKey($values->modelKeys()),
                    ));
            });
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'prix-croissant' => $query->orderBy('price'),
            'prix-decroissant' => $query->orderByDesc('price'),
            'nouveautes' => $query->latest(),
            // "Pertinence" favours products in stock, then the best sellers.
            'pertinence' => $query->orderByRaw('stock = 0')->orderByDesc('sold_count'),
            default => $query->orderByDesc('sold_count'),
        };

        $query->orderByDesc('id');
    }

    /**
     * Choices offered by the filter panel, computed on the page scope (before its own filters).
     *
     * @return array{categories: Collection<int, Category>, brands: Collection<int, Brand>, attributes: Collection<int, ProductAttribute>, price: array{min: int, max: int}}
     */
    private function facets(Builder $scope, ?Category $category): array
    {
        $productIds = (clone $scope)->select('products.id');

        return [
            'categories' => $category ? $category->children : Category::roots()->get(),
            'brands' => Brand::ordered()->whereHas('products', fn (Builder $query) => $query->whereIn('products.id', clone $productIds))->get(),
            'attributes' => ProductAttribute::query()
                ->orderBy('position')
                ->with(['values' => fn ($query) => $query->whereHas(
                    'variants',
                    fn (Builder $query) => $query->whereIn('product_id', clone $productIds),
                )])
                ->get()
                ->filter(fn (ProductAttribute $attribute) => $attribute->values->isNotEmpty())
                ->values(),
            'price' => [
                'min' => (int) (clone $scope)->min('price'),
                'max' => (int) (clone $scope)->max('price'),
            ],
        ];
    }

    /**
     * Matching product ids from the search engine (database today, Meilisearch later: decision C-13).
     *
     * @return list<int>
     */
    private function searchIds(string $term): array
    {
        return Product::search($term)->take(1000)->keys()->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return list<int>
     */
    private function ids(mixed $input): array
    {
        return collect((array) $input)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }
}
