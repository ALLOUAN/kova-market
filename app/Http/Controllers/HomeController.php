<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Services\Storefront\Analytics;
use App\Services\Storefront\HomePageService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomePageService $homePage, Analytics $analytics): View
    {
        $data = $homePage->data();

        if ($analytics->enabled()) {
            $this->measure($data, $analytics);
        }

        return view('pages.home', $data);
    }

    /**
     * Marketing measurement of the home page: the product selections and banners shown (clicks are reported by
     * public/assets/js/analytics.js). Sent only once the visitor accepts the trackers.
     *
     * @param  array<string, mixed>  $data
     */
    private function measure(array $data, Analytics $analytics): void
    {
        $shown = $data['sections'];

        foreach ([
            'deals_of_the_day' => $data['dealsOfTheDay'], 'best_deals' => $data['bestDeals'], 'highlights' => $data['highlights'],
        ] as $section => $collection) {
            if ($collection instanceof Collection && in_array($section, $shown, true)) {
                $collection->products->loadMissing(['defaultVariant', 'brand']);
                $analytics->viewItemList($collection->slug, $collection->name, $collection->products);
            }
        }

        foreach (['new_arrivals' => $data['newArrivals'], 'popular' => $data['popular']] as $section => $row) {
            if (in_array($section, $shown, true)) {
                $row['products']->loadMissing(['defaultVariant', 'brand']);
                $analytics->viewItemList($section, $row['title'], $row['products']);
            }
        }

        if (in_array('featured', $shown, true) && $data['featuredProduct']) {
            $analytics->viewItemList('featured-products', (string) $data['featuredTitle'], collect([$data['featuredProduct']])->concat($data['featuredProducts']));
        }

        // A single-slot banner shows inside its section, and only when that section shows.
        $bannerShown = [
            'categories' => in_array('categories', $shown, true),
            'best_deals' => in_array('best_deals', $shown, true) && $data['bestDeals'],
            'highlights' => in_array('highlights', $shown, true) && $data['highlights'],
            'closing' => in_array('closing', $shown, true),
        ];

        $analytics->viewPromotions([
            ...(in_array('hero', $shown, true) ? $data['hero'] : []),
            ...collect($data['banners'])->filter(fn ($banner, string $slot) => $bannerShown[$slot] ?? false)->all(),
        ]);
    }
}
