<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BundleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A product as the storefront shows it: summary in lists, with description, variants and pack contents on its page.
 * Amounts in whole FCFA.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => $this->url(),
            'price' => $this->price,
            'compare_at_price' => $this->isOnSale() ? $this->compare_at_price : null,
            'discount_percentage' => $this->discountPercentage(),
            'sale_ends_at' => $this->when($this->hasCountdown(), fn () => $this->sale_ends_at?->toIso8601String()),
            'stock' => $this->stock,
            'in_stock' => ! $this->isSoldOut(),
            'limited_stock' => $this->hasLimitedStock(),
            'image' => $this->image ? asset($this->image) : null,
            'rating' => $this->rating,
            'free_shipping' => $this->free_shipping,
            'is_bundle' => $this->is_bundle,
            'category' => $this->whenLoaded('category', fn () => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name, 'slug' => $this->category->slug] : null),
            'brand' => BrandResource::make($this->whenLoaded('brand')),
            'description' => $this->when($this->relationLoaded('variants'), fn () => $this->description),
            'specifications' => $this->when($this->relationLoaded('variants'), fn () => $this->specifications),
            'variants' => VariantResource::collection($this->whenLoaded('variants')),
            'bundle_items' => $this->whenLoaded('bundleItems', fn () => $this->bundleItems->map(fn (BundleItem $item) => [
                'name' => $item->variant->product->name,
                'variant' => $item->variant->attributeValues->isEmpty() ? null : $item->variant->label(),
                'quantity' => $item->quantity,
            ])->values()),
        ];
    }
}
