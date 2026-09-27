<?php

namespace App\View\Composers;

use App\Services\Storefront\CatalogService;
use App\Services\Storefront\NavigationService;
use Illuminate\View\View;

/**
 * Provides the data needed by the storefront layout: header, menus, side panels, modals and footer.
 */
class StorefrontLayoutComposer
{
    public const TRENDING = 'trending-searches';

    public const RECENTLY_VIEWED = 'weekly-highlights';

    public function __construct(
        private CatalogService $catalog,
        private NavigationService $navigation,
    ) {}

    public function compose(View $view): void
    {
        $collections = $this->catalog->collections([self::TRENDING, self::RECENTLY_VIEWED]);

        $contact = config('storefront.contact');

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
            // Networks without a real profile url yet ("#") are not displayed.
            'socialLinks' => collect(config('storefront.social'))->reject(fn (array $network) => $network['url'] === '#')->values(),
            'categoryTree' => $this->catalog->categoryTree(),
            'navBrands' => $this->catalog->brands(),
            'promotions' => $this->catalog->currentPromotions(),
            'trendingProducts' => $collections->get(self::TRENDING)?->products ?? collect(),
            // Per-visitor history is not tracked yet: the modal showcases the weekly highlights meanwhile.
            'recentlyViewedProducts' => $collections->get(self::RECENTLY_VIEWED)?->products ?? collect(),
        ]);
    }
}
