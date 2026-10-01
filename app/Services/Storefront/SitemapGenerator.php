<?php

namespace App\Services\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use XMLWriter;

/**
 * sitemap.xml (F-154): home, shop, contact and FAQ, every category and brand, active products and published pages,
 * with their last change. Written every night by `seo:sitemap` and served at /sitemap.xml.
 */
class SitemapGenerator
{
    public const PATH = 'seo/sitemap.xml';

    public function generate(): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach (['home', 'shop.index', 'contact.show', 'faq'] as $route) {
            $this->url($xml, route($route));
        }

        // The market showcase stands on its own, products or not.
        $market = app(Market::class);
        if ($market->enabled()) {
            $this->url($xml, route('market.show'));
        }

        // Only lists that show products: an empty category or brand page is thin content for search engines.
        Category::query()->orderBy('id')->each(function (Category $category) use ($xml, $market): void {
            if (! $market->isMarket($category) && Product::query()->active()->whereIn('category_id', $category->descendantIds())->exists()) {
                $this->url($xml, $category->url(), $category->updated_at);
            }
        });
        Brand::query()->whereHas('products', fn ($query) => $query->active())->orderBy('id')
            ->each(fn (Brand $brand) => $this->url($xml, $brand->url(), $brand->updated_at));
        Product::query()->active()->orderBy('id')->each(fn (Product $product) => $this->url($xml, $product->url(), $product->updated_at));
        Page::query()->published()->orderBy('id')->each(fn (Page $page) => $this->url($xml, route('pages.show', $page), $page->updated_at));

        $xml->endElement();
        $xml->endDocument();

        $content = $xml->outputMemory();
        Storage::disk('local')->put(self::PATH, $content);

        return $content;
    }

    /**
     * The last generated sitemap, generated now when there is none yet.
     */
    public function current(): string
    {
        return Storage::disk('local')->get(self::PATH) ?? $this->generate();
    }

    private function url(XMLWriter $xml, string $location, ?\DateTimeInterface $lastModified = null): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $location);

        if ($lastModified) {
            $xml->writeElement('lastmod', $lastModified->format('Y-m-d'));
        }

        $xml->endElement();
    }
}
