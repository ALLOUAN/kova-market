<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Schema.org data read by search engines (F-152): Product with its Offer, BreadcrumbList, and the Organization and
 * WebSite of the store on the home page. Prices in whole FCFA (XOF), as on the pages.
 */
class StructuredData
{
    public function __construct(private StoreSettings $settings) {}

    /**
     * @param  array<string, string>  $trail  breadcrumb links above the product, [label => url]
     * @return list<array<string, mixed>>
     */
    public function product(Product $product, array $trail = []): array
    {
        $variants = $product->variants;
        $prices = $variants->map(fn (ProductVariant $variant) => $variant->currentPrice());
        $inStock = $variants->contains(fn (ProductVariant $variant) => $variant->stock > 0);
        $availability = 'https://schema.org/'.($inStock ? 'InStock' : 'OutOfStock');
        $currency = config('storefront.currency');
        $images = collect([$product->image, $product->hover_image])->filter()->unique()->map(fn (string $path) => asset($path))->values()->all();

        $offer = $prices->unique()->count() > 1
            ? [
                '@type' => 'AggregateOffer',
                'lowPrice' => $prices->min(),
                'highPrice' => $prices->max(),
                'offerCount' => $variants->count(),
            ]
            : [
                '@type' => 'Offer',
                'price' => $prices->first() ?? $product->price,
                'url' => $product->url(),
                'itemCondition' => 'https://schema.org/NewCondition',
            ];

        $offer = array_filter([
            ...$offer,
            'priceCurrency' => $currency,
            'availability' => $availability,
            // A running sale price is valid until its end date.
            'priceValidUntil' => $product->hasCountdown() ? $product->sale_ends_at?->toDateString() : null,
            'seller' => ['@type' => 'Organization', 'name' => config('storefront.name')],
        ], fn ($value) => $value !== null);

        return [
            array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'image' => $images,
                'description' => $this->plainText($product->meta_description ?: $product->description) ?: null,
                'sku' => $variants->firstWhere('is_default', true)?->sku ?? $variants->first()?->sku,
                'category' => $product->category?->name,
                'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
                'offers' => $offer,
            ], fn ($value) => $value !== null && $value !== []),
            $this->breadcrumbs([...$trail, $product->name => $product->url()]),
        ];
    }

    /**
     * @param  array<string, string>  $trail  [label => url], home excluded (added first)
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $trail): array
    {
        $links = ['Accueil' => route('home'), ...$trail];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (string $name, string $url, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $name,
                'item' => $url,
            ], array_map('strval', array_keys($links)), array_values($links), array_keys(array_keys($links))),
        ];
    }

    /**
     * The store and its search box, for the home page.
     *
     * @return list<array<string, mixed>>
     */
    public function organization(): array
    {
        $contact = $this->settings->contact();

        return [
            array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => config('storefront.name'),
                'url' => route('home'),
                'logo' => asset(config('storefront.logo')),
                'email' => $contact['email'] ?: null,
                'contactPoint' => filled($contact['phone']) ? [
                    '@type' => 'ContactPoint',
                    'telephone' => $contact['phone'],
                    'contactType' => 'customer service',
                    'areaServed' => 'CI',
                    'availableLanguage' => 'French',
                ] : null,
                'sameAs' => $this->settings->socialLinks()->pluck('url')->all() ?: null,
            ], fn ($value) => $value !== null),
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('storefront.name'),
                'url' => route('home'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('shop.index').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    private function plainText(?string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5)) ?? '');
    }
}
