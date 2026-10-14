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

    /**
     * Meta keywords of a product page: what people type to find it in Abidjan ("<produit> prix abidjan", "acheter
     * <produit> abidjan"…), its brand and category, then the store's own words.
     */
    public static function productKeywords(Product $product): string
    {
        $name = Str::lower($product->name);

        return self::keywords([
            $name, "{$name} prix abidjan", "{$name} pas cher abidjan", "acheter {$name} abidjan", "{$name} côte d’ivoire",
            $product->brand?->name, $product->category?->name,
        ]);
    }

    /** Meta keywords of a category or brand list. */
    public static function listingKeywords(Category|Brand $subject): string
    {
        $name = Str::lower($subject->name);

        return self::keywords([$name, "{$name} abidjan", "{$name} côte d’ivoire", "acheter {$name} en ligne", "{$name} pas cher"]);
    }

    /**
     * The page's own words first, then the store's (Paramètres de la boutique), without repeats.
     *
     * @param  array<int, string|null>  $own
     */
    private static function keywords(array $own): string
    {
        $store = array_map('trim', explode(',', (string) config('storefront.keywords')));

        return collect([...$own, ...$store])
            ->map(fn (?string $word) => Str::lower(trim((string) $word)))
            ->filter()
            ->unique()
            ->implode(', ');
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
