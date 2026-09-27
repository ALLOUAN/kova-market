<?php

namespace App\Services\Storefront;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Assembles the data displayed by the home page sections.
 */
class HomePageService
{
    public const DEALS_OF_THE_DAY = 'deals-of-the-day';

    public const BEST_DEALS = 'todays-best-deals';

    public const HIGHLIGHTS = 'weekly-highlights';

    public const FEATURED = 'featured-products';

    public function __construct(private CatalogService $catalog) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $collections = $this->catalog->collections([
            self::DEALS_OF_THE_DAY, self::BEST_DEALS, self::HIGHLIGHTS, self::FEATURED,
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
