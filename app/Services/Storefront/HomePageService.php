<?php

namespace App\Services\Storefront;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use App\Models\Collection;
use App\Models\Product;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Assembles the data displayed by the home page sections.
 */
class HomePageService
{
    public const DEALS_OF_THE_DAY = 'deals-of-the-day';

    public const BEST_DEALS = 'todays-best-deals';

    public const HIGHLIGHTS = 'weekly-highlights';

    public const FEATURED = 'featured-products';

    public const NEW_ARRIVALS = 'new-arrivals';

    public const POPULAR = 'popular-products';

    /** Products shown by the "Nouveautés" and "Populaires" rows. */
    public const ROW_SIZE = 8;

    public function __construct(private CatalogService $catalog) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $collections = $this->catalog->collections([
            self::DEALS_OF_THE_DAY, self::BEST_DEALS, self::HIGHLIGHTS, self::FEATURED, self::NEW_ARRIVALS, self::POPULAR,
        ]);

        $featured = $collections->get(self::FEATURED)?->products ?? collect();

        return [
            'hero' => $this->hero(),
            'banners' => $this->banners(),
            'categories' => $this->catalog->featuredCategories(),
            'dealsOfTheDay' => $collections->get(self::DEALS_OF_THE_DAY),
            'bestDeals' => $collections->get(self::BEST_DEALS),
            'highlights' => $collections->get(self::HIGHLIGHTS),
            'featuredProduct' => $featured->first(),
            'featuredProducts' => $featured->slice(1)->values(),
            'featuredTitle' => $collections->get(self::FEATURED)?->name,
            'brands' => $this->catalog->brands(),
            'newArrivals' => $this->row($collections->get(self::NEW_ARRIVALS), 'Nouveautés', 'nouveautes', fn (Builder $query) => $query->latest()->orderByDesc('id')),
            'popular' => $this->row($collections->get(self::POPULAR), 'Populaires', 'popularite', fn (Builder $query) => $query->orderByDesc('sold_count')->orderByDesc('id')),
        ];
    }

    /**
     * A product row of the home page (F-011, F-012): computed from the catalog, unless a collection with that slug
     * exists and holds active products, which then takes precedence (curated by the store).
     *
     * @param  Closure(Builder): Builder  $order
     * @return array{title: string, url: string, products: SupportCollection<int, Product>}
     */
    private function row(?Collection $curated, string $title, string $sort, Closure $order): array
    {
        $products = $curated && $curated->products->isNotEmpty()
            ? $curated->products->take(self::ROW_SIZE)
            : $order(Product::query()->active()->with('category'))->limit(self::ROW_SIZE)->get();

        return [
            'title' => $curated?->name ?? $title,
            'url' => route('shop.index', ['tri' => $sort]),
            'products' => $products->values(),
        ];
    }

    /**
     * Hero slides from the back-office, or the default slides of config/homepage.php when none is live.
     *
     * @return list<array<string, mixed>>
     */
    private function hero(): array
    {
        $slides = $this->liveBanners()->where('placement', BannerPlacement::Hero);

        return $slides->isEmpty()
            ? config('homepage.hero')
            : $slides->map(fn (Banner $banner) => $banner->toStorefront())->values()->all();
    }

    /**
     * One banner per single slot, each falling back to its config/homepage.php default.
     *
     * @return array<string, array<string, mixed>>
     */
    private function banners(): array
    {
        return collect(config('homepage.banners'))
            ->map(fn (array $default, string $slot) => $this->liveBanners()
                ->firstWhere('placement', BannerPlacement::from($slot))
                ?->toStorefront() ?? $default)
            ->all();
    }

    /**
     * @return EloquentCollection<int, Banner>
     */
    private function liveBanners(): EloquentCollection
    {
        return once(fn () => Banner::live()->get());
    }
}
