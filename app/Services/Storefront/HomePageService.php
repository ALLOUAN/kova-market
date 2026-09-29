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

    /** Sections of the home page in their default order (key => name in the back-office). */
    public const SECTIONS = [
        'hero' => 'Carrousel principal',
        'guarantees' => 'Bandeau de garanties',
        'categories' => 'Catégories populaires',
        'deals_of_the_day' => 'Offres du jour',
        'best_deals' => 'Les meilleures offres du jour',
        'new_arrivals' => 'Nouveautés',
        'highlights' => 'Les incontournables de la semaine',
        'popular' => 'Populaires',
        'featured' => 'Produit vedette',
        'brands' => 'Nos marques',
        'closing' => 'Bannière de fin de page',
    ];

    /**
     * The sections in the order set in Paramètres de la boutique › Page d'accueil, with whether each is shown;
     * sections the setting does not know yet (added later) come last, shown.
     *
     * @return list<array{key: string, visible: bool}>
     */
    public static function sectionSettings(): array
    {
        $saved = collect(config('storefront.home_sections') ?? [])
            ->filter(fn ($section) => is_array($section) && array_key_exists($section['key'] ?? null, self::SECTIONS))
            ->unique('key')
            ->map(fn (array $section) => ['key' => $section['key'], 'visible' => (bool) ($section['visible'] ?? true)]);

        return $saved
            ->concat(collect(array_keys(self::SECTIONS))->diff($saved->pluck('key'))->map(fn (string $key) => ['key' => $key, 'visible' => true]))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function visibleSections(): array
    {
        return collect(self::sectionSettings())->where('visible', true)->pluck('key')->values()->all();
    }

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
            'sections' => self::visibleSections(),
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
        $isCurated = $curated && $curated->products->isNotEmpty();

        $products = $isCurated
            ? $curated->products->take(self::ROW_SIZE)
            : $order(Product::query()->active()->with('category'))->limit(self::ROW_SIZE)->get();

        return [
            'title' => $curated?->name ?? $title,
            // A curated row opens its own selection page; a computed one, the shop sorted the same way.
            'url' => $isCurated ? route('collections.show', $curated) : route('shop.index', ['tri' => $sort]),
            'products' => $products->values(),
        ];
    }

    /**
     * Hero slides published from the back-office; none live, no slider (never the template's sample slides).
     *
     * @return list<array<string, mixed>>
     */
    private function hero(): array
    {
        return $this->liveBanners()
            ->where('placement', BannerPlacement::Hero)
            ->map(fn (Banner $banner) => $this->withLink($banner->toStorefront()))
            ->values()
            ->all();
    }

    /**
     * A banner without a link leads to the shop rather than nowhere.
     *
     * @param  array<string, mixed>  $banner
     * @return array<string, mixed>
     */
    private function withLink(array $banner): array
    {
        return [...$banner, 'url' => filled($banner['url'] ?? null) ? $banner['url'] : route('shop.index')];
    }

    /**
     * One banner per single slot, or null: an empty slot hides its banner (the section around it adapts).
     *
     * @return array<string, array<string, mixed>|null>
     */
    private function banners(): array
    {
        return collect(BannerPlacement::cases())
            ->reject(fn (BannerPlacement $placement) => $placement->isSlider())
            ->mapWithKeys(function (BannerPlacement $placement): array {
                $banner = $this->liveBanners()->firstWhere('placement', $placement);

                return [$placement->value => $banner ? $this->withLink($banner->toStorefront()) : null];
            })
            ->all();
    }

    /**
     * @return EloquentCollection<int, Banner>
     */
    private function liveBanners(): EloquentCollection
    {
        return once(fn () => Banner::live()->with('product')->get());
    }
}
