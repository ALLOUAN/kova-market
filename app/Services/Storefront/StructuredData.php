<?php

namespace App\Services\Storefront;

use App\Enums\SaleUnit;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\SeoText;

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
            // Delivery and returns, asked by Google for product results (merchant listings).
            'shippingDetails' => $this->shipping($product),
            'hasMerchantReturnPolicy' => $product->return_days
                ? [
                    '@type' => 'MerchantReturnPolicy',
                    'applicableCountry' => 'CI',
                    'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'merchantReturnDays' => $product->return_days,
                ]
                : ['@type' => 'MerchantReturnPolicy', 'applicableCountry' => 'CI', 'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted'],
            // Sold by weight or volume: the price is for 1 kg (KGM) or 1 litre (LTR).
            'priceSpecification' => $product->saleQuantity()->unit->isMeasured() ? [
                '@type' => 'UnitPriceSpecification',
                'price' => $prices->min() ?? $product->price,
                'priceCurrency' => $currency,
                'referenceQuantity' => [
                    '@type' => 'QuantitativeValue',
                    'value' => 1,
                    'unitCode' => $product->saleQuantity()->unit === SaleUnit::Kilogram ? 'KGM' : 'LTR',
                ],
            ] : null,
        ], fn ($value) => $value !== null);

        return [
            array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'image' => $images,
                'description' => SeoText::product($product),
                'sku' => $variants->firstWhere('is_default', true)?->sku ?? $variants->first()?->sku,
                'category' => $product->category?->name,
                'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
                'offers' => $offer,
                // Only from real, approved customer reviews.
                'aggregateRating' => $product->reviews_count > 0 ? [
                    '@type' => 'AggregateRating',
                    'ratingValue' => $product->rating,
                    'reviewCount' => $product->reviews_count,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ] : null,
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

    /**
     * Delivery in Côte d'Ivoire from the cheapest active zone (free for a product with free shipping); null while no
     * zone is set up.
     *
     * @return array<string, mixed>|null
     */
    private function shipping(Product $product): ?array
    {
        $fee = $product->free_shipping ? 0 : once(fn () => DeliveryZone::query()->where('is_active', true)->min('fee'));

        return $fee === null ? null : [
            '@type' => 'OfferShippingDetails',
            'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => (int) $fee, 'currency' => config('storefront.currency')],
            'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'CI'],
        ];
    }
}
