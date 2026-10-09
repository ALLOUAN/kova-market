<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\SaleUnit;
use App\Filament\Pages\Settings;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The "Mon Marché" showcase: the market department's categories and a row of products for each, set in
 * Paramètres › Mon Marché.
 */
class MarketPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_showcase_lists_the_market_categories_and_their_products(): void
    {
        $vegetables = Category::where('slug', 'legumes-frais')->firstOrFail();
        $rice = Category::where('slug', 'riz')->firstOrFail();
        Product::factory()->for($vegetables)->create(['name' => 'Tomates fraîches', 'sale_unit' => SaleUnit::Kilogram, 'price' => 1000]);
        Product::factory()->for($rice)->create(['name' => 'Riz parfumé 5 kg']);
        Product::factory()->for(Category::factory()->create())->create(['name' => 'Casque audio']);

        $this->get('/mon-marche')->assertOk()
            ->assertSeeText('Mon Marché')
            ->assertSeeText('Fruits et légumes')
            ->assertSeeText('Surgelés')
            ->assertSeeText('Tomates fraîches')
            ->assertSeeText('Riz parfumé 5 kg')
            ->assertDontSeeText('Casque audio')
            // Rows of the categories holding products, with their sub-categories and "Tout voir".
            ->assertSeeInOrder(['Fruits et légumes', 'Légumes frais', 'Tout voir (1)', 'Tomates fraîches'])
            ->assertSeeText('Vendus au poids (1)')
            ->assertSee(route('categories.show', 'mon-marche').'?vente=poids', false);

        // "Vendus au poids": the market's products sold by weight or volume.
        $this->get('/categorie/mon-marche?vente=poids')->assertOk()->assertSeeText('Tomates fraîches')->assertDontSeeText('Riz parfumé 5 kg');
    }

    public function test_the_menus_lead_to_the_showcase(): void
    {
        Product::factory()->for(Category::where('slug', 'legumes-frais')->firstOrFail())->create();

        // Like "Boutique": a chevron opening the market's categories (tabs) and their sub-categories.
        $this->get('/')->assertOk()
            ->assertSee('class="with-rbt-megamenu has-menu-child-item position-static kova-menu-highlight"', false)
            ->assertSee('href="'.route('market.show').'"', false)
            ->assertSee('id="rbt-megamenu_tab1-market"', false)
            ->assertSee('href="'.route('categories.show', 'legumes-frais').'"', false)
            ->assertSeeText('Découvrir Mon Marché');

        // The department link opens the showcase; its categories keep their own pages, the showcase in their trail.
        $this->assertSame(route('market.show'), Category::where('slug', 'mon-marche')->first()->url());
        $this->get('/categorie/boissons')->assertOk()->assertSee('href="'.route('market.show').'"', false);
    }

    public function test_an_empty_market_says_the_products_are_coming(): void
    {
        $this->get('/mon-marche')->assertOk()->assertSeeText('Les étals se remplissent')->assertSeeText('Boulangerie et pâtisserie');
    }

    public function test_the_back_office_sets_the_showcase(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());

        Livewire::test(Settings::class)
            ->fillForm(['market.title' => 'Le Marché KOVA', 'market.subtitle' => 'Du frais tous les jours.', 'market.per_row' => 4, 'market.in_menu' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Le Marché KOVA', '0'], [Setting::get('market.title'), Setting::get('market.in_menu')]);
        $this->get('/mon-marche')->assertOk()->assertSeeText('Le Marché KOVA')->assertSeeText('Du frais tous les jours.');
        $this->get('/')->assertDontSee('kova-menu-highlight', false);

        // Offline: no page, and the department goes back to its product list.
        Setting::store(['market.enabled' => '0']);
        $this->get('/mon-marche')->assertNotFound();
        $this->assertSame(route('categories.show', 'mon-marche'), Category::where('slug', 'mon-marche')->first()->url());
    }
}
