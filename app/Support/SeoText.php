<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Meta descriptions written from the catalog when the back-office leaves the SEO field empty (F-152): each page gets
 * its own text — what it sells, a price, where it is delivered — instead of the store's generic description, which
 * search engines treat as duplicate and replace with a random extract.
 */
final class SeoText
{
    public const MAX = 160;

    private const PROMISE = 'Livraison à Abidjan, paiement Mobile Money ou à la livraison.';

    public static function product(Product $product): string
    {
        $text = self::plain($product->meta_description) ?: self::plain($product->description);

        if ($text !== '') {
            return Str::limit($text, self::MAX, '…');
        }

        $price = ($product->price_max ? 'dès ' : 'à ').Money::format($product->price).$product->saleQuantity()->priceSuffix();

        return self::fit(self::withBrand($product)." {$price} chez ".config('storefront.name').'. '.self::PROMISE);
    }

    /** A category or a brand list: its own text, else what it holds and how many products. */
    public static function listing(Category|Brand $subject, int $count): string
    {
        $own = self::plain($subject->meta_description) ?: self::plain($subject instanceof Category ? $subject->tagline : null);

        if ($own !== '') {
            return Str::limit($own, self::MAX, '…');
        }

        $what = $subject instanceof Brand ? "Produits {$subject->name}" : $subject->name;
        $children = $subject instanceof Category ? $subject->children->take(3)->pluck('name')->join(', ') : '';
        $count = $count > 0 ? " : {$count} produit".($count > 1 ? 's' : '') : '';

        return self::fit("{$what}{$count} sur ".config('storefront.name').($children !== '' ? " ({$children}…)" : '').'. '.self::PROMISE);
    }

    public static function shop(int $count): string
    {
        return self::fit('Tous les produits de '.config('storefront.name').($count > 0 ? " : {$count} références" : '').', à Abidjan. '.self::PROMISE);
    }

    private static function withBrand(Product $product): string
    {
        $brand = $product->brand?->name;

        return $brand && ! Str::contains(Str::lower($product->name), Str::lower($brand)) ? "{$product->name} de {$brand}" : $product->name;
    }

    private static function plain(?string $html): string
    {
        // Sanitised first: a script typed in a description never reaches the meta tags, not even its text.
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(Str::sanitizeHtml((string) $html)), ENT_QUOTES | ENT_HTML5)) ?? '');
    }

    private static function fit(string $text): string
    {
        return Str::limit($text, self::MAX, '…');
    }
}
