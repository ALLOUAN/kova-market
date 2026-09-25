<?php

namespace App\Services\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Read side of the catalog used by storefront pages and the shared layout.
 *
 * Registered as a scoped singleton: results are memoized for the current request,
 * so the several menus rendering the category tree share a single query.
 */
class CatalogService
{
    /**
     * Root categories with two levels of descendants (menus, side panel, home grid).
     *
     * @return EloquentCollection<int, Category>
     */
    public function categoryTree(): EloquentCollection
    {
        return once(fn () => Category::roots()->with('children.children')->get());
    }

    /**
     * @return EloquentCollection<int, Category>
     */
    public function featuredCategories(): EloquentCollection
    {
        return $this->categoryTree()->where('is_featured', true)->values();
    }

    /**
     * @return EloquentCollection<int, Brand>
     */
    public function brands(): EloquentCollection
    {
        return once(fn () => Brand::ordered()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->get());
    }

    /**
     * Merchandising collections with their active products, keyed by slug.
     *
     * @param  list<string>  $slugs
     * @return EloquentCollection<string, Collection>
     */
    public function collections(array $slugs): EloquentCollection
    {
        return once(fn () => Collection::query()
            ->whereIn('slug', $slugs)
            ->with(['products' => fn ($query) => $query->active()->with('category')])
            ->get()
            ->keyBy('slug'));
    }

    /**
     * @return EloquentCollection<int, Promotion>
     */
    public function currentPromotions(): EloquentCollection
    {
        return once(fn () => Promotion::current()->get());
    }
}
