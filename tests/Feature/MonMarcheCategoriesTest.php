<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Mon Marché", the market department created by migration: first in the menus, with its parent categories and
 * sub-categories, in every environment.
 */
class MonMarcheCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_mon_marche_holds_its_parent_categories_and_sub_categories(): void
    {
        $market = Category::where('slug', 'mon-marche')->firstOrFail();

        $this->assertNull($market->parent_id);
        $this->assertSame('Mon Marché', Category::roots()->first()->name);
        $this->assertSame(
            ['Fruits et légumes', 'Viandes, volailles et poissons', 'Céréales et légumineuses', 'Épicerie', 'Produits frais et laitiers', 'Boulangerie et pâtisserie', 'Boissons', 'Surgelés'],
            $market->children->pluck('name')->all(),
        );
        $this->assertContains('Riz', $market->children->firstWhere('name', 'Céréales et légumineuses')->children->pluck('name'));
        // Three levels, like the rest of the catalog: the sub-categories are the last one.
        $this->assertSame(0, Category::whereIn('parent_id', $market->children->flatMap->children->pluck('id'))->count());
    }

    public function test_the_storefront_shows_it_in_the_menus_and_on_its_page(): void
    {
        // The menus show the categories holding products.
        Product::factory()->for(Category::where('slug', 'legumes-frais')->firstOrFail())->create();

        $this->get('/')->assertOk()->assertSeeText('Mon Marché')->assertSeeText('Fruits et légumes')->assertSee('fa-regular fa-basket-shopping', false);

        $this->get('/categorie/mon-marche')->assertOk()->assertSeeText('Mon Marché')->assertSeeText('Boissons');
        $this->get('/categorie/fruits-et-legumes')->assertOk()->assertSeeText('Légumes frais');
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $count = Category::count();

        (require database_path('migrations/2026_09_30_120000_create_mon_marche_categories.php'))->up();

        $this->assertSame($count, Category::count());
    }
}
