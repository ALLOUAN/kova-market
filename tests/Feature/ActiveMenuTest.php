<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The page being shown is marked in the menus: header bars, dropdowns, mega menus, footer and mobile toolbar.
 */
class ActiveMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_lights_up_home_only(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<li class="position-relative active">\s*<a href="'.preg_quote(route('home'), '~').'"\s+aria-current="page"\s*>Accueil~', $html);
        $this->assertStringNotContainsString('has-dropdown position-relative active', $html);
    }

    public function test_a_help_page_lights_up_its_dropdown_its_link_and_the_footer_once(): void
    {
        $html = $this->get(route('faq'))->assertOk()->getContent();

        $this->assertStringContainsString('<li class="has-dropdown position-relative active">', $html);
        // "Pages" also lists the FAQ, but only "Aide" lights up.
        $this->assertStringNotContainsString('<li class="with-rbt-megamenu has-menu-child-item position-static active">', $html);
        $this->assertMatchesRegularExpression('~<li class="active">\s*<a href="'.preg_quote(route('faq'), '~').'"\s+aria-current="page"\s*>\s*Questions fréquentes~', $html);
        $this->assertMatchesRegularExpression('~<a href="'.preg_quote(route('faq'), '~').'" class="is-active"~', $html);
        $this->assertDoesNotMatchRegularExpression('~<li class="position-relative active">\s*<a href="'.preg_quote(route('home'), '~').'"~', $html);
    }

    public function test_the_most_precise_link_wins_when_several_lead_to_the_shop(): void
    {
        $html = $this->get(route('shop.index', ['tri' => 'nouveautes']))->assertOk()->getContent();
        $news = preg_quote(route('shop.index', ['tri' => 'nouveautes']), '~');
        $all = preg_quote(route('shop.index'), '~');

        $this->assertMatchesRegularExpression('~<li class="active">\s*<a href="'.$news.'"\s+aria-current="page"\s*>~', $html);
        $this->assertDoesNotMatchRegularExpression('~<li class="active">\s*<a href="'.$all.'"~', $html);
        $this->assertStringContainsString('class="rbt-round-btn has-rbt-md-fsize is-active"', $html);
    }

    public function test_a_market_category_lights_up_mon_marche_and_its_department_not_the_shop(): void
    {
        $market = Category::where('slug', 'mon-marche')->firstOrFail();
        $rayon = $market->children()->orderBy('position')->firstOrFail();
        Product::factory()->create(['category_id' => $rayon->id]);

        $html = $this->get($rayon->url())->assertOk()->getContent();

        $this->assertStringContainsString('kova-menu-highlight active', $html);
        $this->assertStringNotContainsString('<li class="with-rbt-megamenu has-menu-child-item position-static active">', $html);
        $this->assertStringContainsString('nav-link active is-current', $html);
    }

    public function test_a_shop_category_lights_up_the_shop(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $html = $this->get($category->url())->assertOk()->getContent();

        $this->assertStringContainsString('<li class="with-rbt-megamenu has-menu-child-item position-static active">', $html);
        $this->assertStringNotContainsString('kova-menu-highlight active', $html);
    }

    public function test_an_unknown_category_still_renders_the_menu(): void
    {
        $this->get('/categorie/rayon-inexistant')->assertNotFound();
    }
}
