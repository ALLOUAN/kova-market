<?php

namespace App\View\Composers;

use App\Models\Testimonial;
use App\Services\Cart\CartManager;
use App\Services\Storefront\CatalogService;
use App\Services\Storefront\Comparison;
use App\Services\Storefront\NavigationService;
use App\Services\Storefront\RecentlyViewed;
use App\Services\Storefront\StoreSettings;
use App\Services\Storefront\Wishlist;
use Illuminate\View\View;

/**
 * Provides the data needed by the storefront layout: header, menus, side panels, modals and footer.
 */
class StorefrontLayoutComposer
{
    public const TRENDING = 'trending-searches';

    public function __construct(
        private CatalogService $catalog,
        private NavigationService $navigation,
        private StoreSettings $settings,
        private CartManager $cart,
        private RecentlyViewed $recentlyViewed,
    ) {}

    public function compose(View $view): void
    {
        $collections = $this->catalog->collections([self::TRENDING]);

        $contact = $this->settings->contact();

        $view->with([
            'contact' => [
                ...$contact,
                'phone_href' => 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone']),
                'toll_free_href' => 'tel:'.preg_replace('/[^\d+]/', '', $contact['toll_free']),
            ],
            'mainMenu' => $this->navigation->mainMenu(),
            'sidebarLinks' => $this->navigation->groups('sidebar'),
            'footerLinks' => $this->navigation->groups('footer'),
            'legalLinks' => $this->navigation->links(config('navigation.legal')),
            'socialLinks' => $this->settings->socialLinks(),
            'cartSummary' => $this->cart->summary(),
            'categoryTree' => $this->catalog->categoryTree(),
            'navBrands' => $this->catalog->brands(),
            'promotions' => $this->catalog->currentPromotions(),
            'trendingProducts' => $collections->get(self::TRENDING)?->products ?? collect(),
            'recentlyViewedProducts' => $this->recentlyViewed->products(),
            'wishlistCount' => config('storefront.features.wishlist') ? app(Wishlist::class)->count() : 0,
            'compared' => config('storefront.features.compare') ? app(Comparison::class)->products() : collect(),
            // Sign-in and sign-up windows (guests only).
            'testimonials' => auth()->check() ? collect() : Testimonial::query()->published()->limit(8)->get(),
        ]);
    }
}
