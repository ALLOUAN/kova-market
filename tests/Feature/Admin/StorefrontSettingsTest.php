<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Pages\Menus;
use App\Filament\Pages\Settings;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\MenuLinks;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * F-111: texts, images and menus of the storefront change from the back-office, without a developer.
 */
class StorefrontSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesAndPermissionsSeeder::class, ContentSeeder::class]);
    }

    public function test_texts_saved_in_the_settings_replace_the_defaults_on_the_storefront(): void
    {
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());

        Livewire::test(Settings::class)
            ->fillForm([
                'identity.name' => 'Kova Shop',
                'identity.about' => 'Votre boutique de quartier, en ligne.',
                'announcements.trending' => ['Livraison offerte ce week-end'],
                'search.popular' => ['Casques', 'Enceintes'],
                'product_card.shipping_delay' => 'Livré le jour même à Cocody',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('["Casques","Enceintes"]', Setting::get('search.popular'));

        auth()->logout();
        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Kova Shop - Boutique en ligne à Abidjan</title>', false)
            ->assertSeeText('Votre boutique de quartier, en ligne.')
            ->assertSeeText('Livraison offerte ce week-end')
            ->assertSee('href="'.e(route('shop.index', ['q' => 'Enceintes'])).'"', false)
            ->assertDontSeeText('Paiement à la livraison disponible');
    }

    public function test_an_emptied_setting_goes_back_to_the_default(): void
    {
        Setting::store(['announcements.trending' => null, 'identity.name' => '']);

        $this->get('/')->assertOk()->assertSee('<title>'.config('storefront.name').' - Boutique en ligne à Abidjan</title>', false);
    }

    public function test_the_low_stock_threshold_follows_the_setting(): void
    {
        Setting::store(['product_card.limited_stock_threshold' => '7']);

        $this->get('/');

        $this->assertSame(7, config('storefront.product_card.limited_stock_threshold'));
    }

    public function test_menus_are_edited_in_the_back_office_and_can_be_restored(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create());
        Page::create(['title' => 'Nos magasins', 'slug' => 'nos-magasins', 'content' => '<p>Adresse</p>', 'is_published' => true]);

        $state = Menus::current();
        $state['help'] = [
            ['label' => 'Nos magasins', 'target' => 'p:nos-magasins', 'url' => null, 'badge_label' => 'Nouveau', 'badge_variant' => 'yellow'],
            ['label' => 'Blog', 'target' => MenuLinks::CUSTOM, 'url' => 'https://blog.kova.ci', 'badge_label' => null, 'badge_variant' => null],
        ];
        $state['legal'][] = ['label' => 'Mentions légales', 'target' => 'r:faq', 'url' => null, 'badge_label' => null, 'badge_variant' => null];

        Livewire::test(Menus::class)->assertOk()->set('data', $state)->call('save')->assertHasNoFormErrors();

        auth()->logout();
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('pages.show', 'nos-magasins').'"', false)
            ->assertSee('href="https://blog.kova.ci"', false)
            ->assertSeeText('Mentions légales');

        $this->actingAs(User::factory()->staff(Role::Manager)->create());
        Livewire::test(Menus::class)->callAction('reset');
        $this->assertSame(0, Setting::whereIn('key', ['menu.help', 'menu.legal'])->count());
    }

    public function test_a_custom_address_must_be_a_web_address_or_a_path(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $state = Menus::current();
        $state['help'] = [['label' => 'Piège', 'target' => MenuLinks::CUSTOM, 'url' => 'javascript:alert(1)', 'badge_label' => null, 'badge_variant' => null]];

        Livewire::test(Menus::class)->set('data', $state)->call('save')->assertHasErrors();
        $this->assertNull(Setting::get('menu.help'));
    }

    public function test_menu_links_convert_both_ways_without_loss(): void
    {
        foreach (config('navigation.footer') as $links) {
            foreach ($links as $link) {
                $this->assertSame($link, MenuLinks::toConfig(MenuLinks::toForm($link)));
            }
        }
    }

    public function test_preparers_cannot_edit_the_menus(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        $this->get(Menus::getUrl())->assertForbidden();
    }
}
