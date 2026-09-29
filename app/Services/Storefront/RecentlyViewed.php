<?php

namespace App\Services\Storefront;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * "Produits récemment consultés": the last product pages the visitor opened, most recent first, kept in their
 * session (no account needed, nothing stored for anyone else).
 */
class RecentlyViewed
{
    public const LIMIT = 8;

    private const SESSION_KEY = 'recently_viewed';

    public function remember(Product $product): void
    {
        $ids = collect(session()->get(self::SESSION_KEY, []))
            ->reject(fn ($id) => $id === $product->getKey())
            ->prepend($product->getKey())
            ->take(self::LIMIT)
            ->values()
            ->all();

        session()->put(self::SESSION_KEY, $ids);
    }

    /**
     * The products still on sale, in the order they were viewed.
     *
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        $ids = session()->get(self::SESSION_KEY, []);

        if ($ids === []) {
            return new Collection;
        }

        return Product::query()->active()->with('category')->whereKey($ids)->get()
            ->sortBy(fn (Product $product) => array_search($product->getKey(), $ids, true))
            ->values();
    }
}
