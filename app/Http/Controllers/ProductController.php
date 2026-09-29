<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Storefront\Analytics;
use App\Services\Storefront\RecentlyViewed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;

/**
 * Product page (F-030 to F-038) and its quick view from the product cards (EX-18).
 */
class ProductController extends Controller
{
    public const RECOMMENDED = 8;

    public function show(Product $product, Analytics $analytics, RecentlyViewed $recentlyViewed): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['category.parent.parent', 'brand', 'variants.attributeValues.attribute', 'bundleItems.variant.product', 'bundleItems.variant.attributeValues.attribute']);
        $analytics->viewItem($product);
        $recentlyViewed->remember($product);

        return view('pages.product', [
            'product' => $product,
            ...$this->purchase($product),
            'breadcrumb' => $product->category ? $product->category->ancestry() : [],
            'recommended' => $this->recommended($product),
        ]);
    }

    /**
     * Quick view fragment, loaded into the modal (or side panel) opened from a product card.
     */
    public function quickView(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'variants.attributeValues.attribute', 'bundleItems.variant.product', 'bundleItems.variant.attributeValues.attribute']);

        return response()
            ->view('partials.product-quick-view', ['product' => $product, ...$this->purchase($product)])
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * What the variant selector needs: every variant with its price and stock, and the attribute values
     * offered, grouped by attribute.
     *
     * @return array{variants: SupportCollection<int, array<string, mixed>>, options: SupportCollection<string, SupportCollection<int, AttributeValue>>}
     */
    private function purchase(Product $product): array
    {
        return [
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'label' => $variant->label(),
                'price' => $variant->currentPrice(),
                'compare_at_price' => $variant->currentComparePrice(),
                'stock' => $variant->stock,
                'values' => $variant->attributeValues->modelKeys(),
            ])->values(),
            'options' => $product->variants->flatMap->attributeValues->unique('id')
                ->sortBy(['attribute.position', 'position'])
                ->groupBy(fn ($value) => $value->attribute->name),
        ];
    }

    /**
     * Up to 8 other active products, same category first then same brand, those in stock first (F-038).
     *
     * @return Collection<int, Product>
     */
    private function recommended(Product $product): Collection
    {
        $candidates = fn () => Product::query()->active()->whereKeyNot($product->getKey())
            ->with('category')->orderByRaw('stock = 0')->orderByDesc('sold_count');

        $sameCategory = $candidates()->where('category_id', $product->category_id)->limit(self::RECOMMENDED)->get();

        if ($sameCategory->count() >= self::RECOMMENDED || ! $product->brand_id) {
            return $sameCategory;
        }

        return $sameCategory->merge(
            $candidates()->where('brand_id', $product->brand_id)
                ->whereKeyNot($sameCategory->modelKeys())
                ->limit(self::RECOMMENDED - $sameCategory->count())
                ->get(),
        );
    }
}
