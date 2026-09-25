<?php

namespace App\Services\Storefront;

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
            'hero' => config('homepage.hero'),
            'banners' => config('homepage.banners'),
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
}
