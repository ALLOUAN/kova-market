<?php

namespace App\View\Composers;

use App\Services\Cart\CartManager;
use App\Services\Storefront\CatalogService;
use App\Services\Storefront\NavigationService;
use App\Services\Storefront\RecentlyViewed;
use App\Services\Storefront\StoreSettings;
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
        ]);
    }
}
