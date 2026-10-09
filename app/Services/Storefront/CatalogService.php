<?php

namespace App\Services\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
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
        return once(fn () => $this->withProducts(Category::roots()->with('children.children')->get()));
    }

    /**
     * Keeps the categories holding an online product, themselves or below: menus never lead to an empty page, and
     * the header stays light. An empty category still opens by its address.
     *
     * @param  EloquentCollection<int, Category>  $categories
     * @return EloquentCollection<int, Category>
     */
    public function withProducts(EloquentCollection $categories): EloquentCollection
    {
        $stocked = once(fn () => Product::query()->active()->distinct()->pluck('category_id')->flip());

        return $categories->filter(function (Category $category) use ($stocked): bool {
            if ($category->relationLoaded('children')) {
                $category->setRelation('children', $this->withProducts($category->children));
            }

            return $stocked->has($category->getKey()) || ($category->relationLoaded('children') && $category->children->isNotEmpty());
        })->values();
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
     * Merchandising collections with their active products, keyed by slug; a collection whose start date is still
     * to come is left out (prepared in advance in the back-office).
     *
     * @param  list<string>  $slugs
     * @return EloquentCollection<string, Collection>
     */
    public function collections(array $slugs): EloquentCollection
    {
        return once(fn () => Collection::query()
            ->whereIn('slug', $slugs)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
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
