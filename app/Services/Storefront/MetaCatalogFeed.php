<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\SeoText;

/**
 * Product feed for Meta's catalogue (Commerce Manager › Catalogue › Sources de données › Flux de données, URL
 * /flux/meta.csv, fetched daily): one line per online variant, its id being the SKU the pixel and the Conversions API
 * report, so that Meta ties the products seen or bought to its catalogue (catalogue ads, retargeting).
 */
class MetaCatalogFeed
{
    public const COLUMNS = ['id', 'item_group_id', 'title', 'description', 'availability', 'condition', 'price', 'sale_price', 'link', 'image_link', 'brand', 'product_type'];

    /**
     * @return iterable<list<string>>
     */
    public function rows(): iterable
    {
        $currency = config('storefront.currency');

        foreach (Product::query()->active()->with(['brand', 'category', 'variants.attributeValues'])->lazyById(200) as $product) {
            foreach ($product->variants as $variant) {
                /** @var ProductVariant $variant */
                $regular = $variant->currentComparePrice() ?? $variant->currentPrice();
                $label = $variant->attributeValues->isNotEmpty() ? $variant->label() : null;

                yield [
                    $variant->sku ?: (string) $product->id,
                    (string) $product->id,
                    mb_substr($product->name.($label ? ' — '.$label : ''), 0, 150),
                    mb_substr(SeoText::product($product), 0, 5000),
                    $variant->stock > 0 ? 'in stock' : 'out of stock',
                    'new',
                    $regular.' '.$currency,
                    $variant->currentComparePrice() ? $variant->currentPrice().' '.$currency : '',
                    $product->url(),
                    $product->image ? asset($product->image) : '',
                    $product->brand?->name ?? config('storefront.name'),
                    $product->category?->name ?? '',
                ];
            }
        }
    }

    public function csv(): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, self::COLUMNS);

        foreach ($this->rows() as $row) {
            fputcsv($out, $row);
        }

        rewind($out);

        return (string) stream_get_contents($out);
    }
}
