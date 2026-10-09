<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Pages\Maintenance;
use App\Filament\Pages\Menus;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Filament\Resources\Bundles\Pages\ListBundles;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Couriers\Pages\ListCouriers;
use App\Filament\Resources\DeliveryZones\Pages\ListDeliveryZones;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Resources\NewsletterCampaigns\Pages\ListNewsletterCampaigns;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Settings\Widgets\MaintenanceOverview;
use App\Filament\Settings\Widgets\MenusOverview;
use App\Filament\Settings\Widgets\SettingsOverview;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Header bands and tabs of the marketing, delivery, content and administration pages of the back-office: every page,
 * band and tab renders.
 */
class AdminHeaderBandsTest extends TestCase
{
    use RefreshDatabase;

    /** List page => text of its header band. */
    private const LISTS = [
        ListBundles::class => 'Packs',
        ListCoupons::class => 'Codes promo',
        ListPromotions::class => 'Offres spéciales',
        ListNewsletterSubscribers::class => 'Newsletter',
        ListNewsletterCampaigns::class => 'Campagnes e-mail',
        ListCouriers::class => 'Livreurs',
        ListDeliveryZones::class => 'Zones de livraison',
        ListBanners::class => 'Bannières de l’accueil',
        ListPages::class => 'Pages',
        ListFaqs::class => 'Questions fréquentes',
        ListTestimonials::class => 'Témoignages clients',
        ListUsers::class => 'Équipe',
        ListActivities::class => 'Journal d’audit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());
    }

    public function test_every_list_shows_its_header_band_and_every_tab_loads(): void
    {
        foreach (self::LISTS as $page => $title) {
            /** @var class-string<ListRecords> $page */
            $this->get($page::getUrl())->assertOk();

            foreach ((fn () => $this->getHeaderWidgets())->call(new $page) as $widget) {
                Livewire::test($widget)->assertSeeText($title);
            }

            foreach (array_keys((new $page)->getTabs()) as $tab) {
                Livewire::test($page, ['activeTab' => $tab])->assertOk();
            }
        }
    }

    public function test_the_settings_pages_show_what_is_set_up(): void
    {
        config(['services.meta.conversions_token' => null]);

        $this->get(Settings::getUrl())->assertOk();
        Livewire::test(SettingsOverview::class)->assertSeeText('Paramètres de la boutique')->assertSeeText('API Conversions Meta')->assertSeeText('Inactive');

        $this->get(Maintenance::getUrl())->assertOk();
        Livewire::test(MaintenanceOverview::class)->assertSeeText('Mode maintenance')->assertSeeText('En ligne');

        $this->get(Menus::getUrl())->assertOk();
        Livewire::test(MenusOverview::class)->assertSeeText('Menus du site')->assertSeeText('menus d’origine');
    }
}
