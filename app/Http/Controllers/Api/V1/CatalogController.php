<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\CommuneResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Services\Storefront\ProductListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catalog and search through the API (F-020 to F-038): the storefront lists with the same query parameters
 * (q, tri, prix_min, prix_max, en_stock, marques[], valeurs[], categories[], page) and the filter choices in "facets".
 */
class CatalogController extends Controller
{
    public function __construct(private ProductListing $listing) {}

    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::roots()->with('children.children')->get());
    }

    public function brands(): AnonymousResourceCollection
    {
        return BrandResource::collection(Brand::ordered()->get());
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        return $this->list($this->listing->list($request));
    }

    public function category(Request $request, Category $category): AnonymousResourceCollection
    {
        return $this->list($this->listing->list($request, category: $category->load('children')));
    }

    public function brand(Request $request, Brand $brand): AnonymousResourceCollection
    {
        return $this->list($this->listing->list($request, brand: $brand));
    }

    public function product(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        return ProductResource::make($product->load([
            'category', 'brand', 'variants.attributeValues.attribute',
            'bundleItems.variant.product', 'bundleItems.variant.attributeValues.attribute',
        ]));
    }

    /**
     * Communes served and their delivery fee, to choose where the order goes.
     */
    public function communes(): AnonymousResourceCollection
    {
        return CommuneResource::collection(Commune::deliverable()->with('zone')->get());
    }

    /**
     * @param  array{products: LengthAwarePaginator, filters: array<string, mixed>, facets: array<string, mixed>}  $listing
     */
    private function list(array $listing): AnonymousResourceCollection
    {
        $facets = $listing['facets'];

        return ProductResource::collection($listing['products'])->additional([
            'facets' => [
                'categories' => CategoryResource::collection($facets['categories']),
                'brands' => BrandResource::collection($facets['brands']),
                'attributes' => $facets['attributes']->map(fn (ProductAttribute $attribute) => [
                    'name' => $attribute->name,
                    'values' => $attribute->values->map(fn (AttributeValue $value) => ['id' => $value->id, 'value' => $value->value])->values(),
                ])->values(),
                'price' => $facets['price'],
            ],
        ]);
    }
}
