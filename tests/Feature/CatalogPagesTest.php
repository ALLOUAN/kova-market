<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Services\Catalog\StockManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shop_lists_active_products_24_per_page(): void
    {
        Product::factory()->count(25)->create();
        Product::factory()->create(['name' => 'Produit retiré', 'is_active' => false]);

        $this->get('/boutique')->assertOk()->assertSeeText('25 produits')->assertDontSeeText('Produit retiré')->assertSee('?page=2', false);
    }

    public function test_menu_and_card_links_lead_to_the_catalog_pages(): void
    {
        $category = Category::factory()->featured()->create(['name' => 'Téléphones', 'slug' => 'telephones']);
        $product = Product::factory()->for($category)->create(['slug' => 'galaxy-s25']);

        $this->assertSame(url('/categorie/telephones'), $category->url());
        $this->assertSame(url('/produit/galaxy-s25'), $product->url());
        $this->get('/')->assertSee('href="'.url('/categorie/telephones').'"', false);
    }

    public function test_a_category_page_lists_its_sub_categories_products(): void
    {
        $phones = Category::factory()->create(['name' => 'Téléphones', 'slug' => 'telephones']);
        $smartphones = Category::factory()->create(['name' => 'Smartphones', 'parent_id' => $phones->id]);
        Product::factory()->for($smartphones)->create(['name' => 'Galaxy S25']);
        Product::factory()->create(['name' => 'Téléviseur']);

        $this->get('/categorie/telephones')
            ->assertOk()
            ->assertSeeText('Galaxy S25')
            ->assertDontSeeText('Téléviseur')
            ->assertSee('href="'.$smartphones->url().'"', false);
    }

    public function test_price_stock_and_brand_filters_narrow_the_list(): void
    {
        $sony = Brand::factory()->create(['name' => 'Sony']);
        Product::factory()->for($sony)->create(['name' => 'Casque Sony', 'price' => 50000, 'stock' => 3]);
        Product::factory()->create(['name' => 'Casque premier prix', 'price' => 10000, 'stock' => 5]);
        Product::factory()->create(['name' => 'Casque épuisé', 'price' => 40000, 'stock' => 0]);

        // A reversed range is understood.
        $this->get('/boutique?prix_min=60000&prix_max=20000')
            ->assertSeeText('Casque Sony')->assertSeeText('Casque épuisé')->assertDontSeeText('Casque premier prix');
        $this->get('/boutique?en_stock=1')->assertDontSeeText('Casque épuisé');
        $this->get('/boutique?marques[]='.$sony->id)->assertSeeText('1 produit')->assertSeeText('Casque Sony');
    }

    public function test_attribute_filters_combine_alternatives_and_attributes(): void
    {
        $color = ProductAttribute::create(['name' => 'Couleur', 'slug' => 'couleur']);
        $storage = ProductAttribute::create(['name' => 'Capacité', 'slug' => 'capacite']);
        [$black, $white] = [$color->values()->create(['value' => 'Noir']), $color->values()->create(['value' => 'Blanc'])];
        $large = $storage->values()->create(['value' => '256 Go']);

        $this->productWithVariant('Téléphone noir 256', [$black->id, $large->id]);
        $this->productWithVariant('Téléphone blanc', [$white->id]);
        $this->productWithVariant('Téléphone noir', [$black->id]);

        $this->get("/boutique?valeurs[]={$black->id}&valeurs[]={$white->id}")->assertSeeText('3 produits');
        $this->get("/boutique?valeurs[]={$black->id}&valeurs[]={$large->id}")->assertSeeText('1 produit')->assertSeeText('Téléphone noir 256');
    }

    public function test_the_list_can_be_sorted_by_price(): void
    {
        Product::factory()->create(['name' => 'Article cher', 'price' => 90000]);
        Product::factory()->create(['name' => 'Article abordable', 'price' => 15000]);

        $this->get('/boutique?tri=prix-croissant')->assertSeeTextInOrder(['Article abordable', 'Article cher']);
        $this->get('/boutique?tri=prix-decroissant')->assertSeeTextInOrder(['Article cher', 'Article abordable']);
    }

    public function test_the_search_finds_products_by_name_and_offers_a_way_out_when_nothing_matches(): void
    {
        Product::factory()->create(['name' => 'Samsung Galaxy S25']);
        Product::factory()->create(['name' => 'Enceinte JBL']);

        $this->get('/boutique?q=galaxy')->assertSeeText('Résultats pour « galaxy »')->assertSeeText('Samsung Galaxy S25')->assertDontSeeText('Enceinte JBL');
        $this->get('/boutique?q=introuvable')->assertSeeText('Aucun produit ne correspond à votre recherche.');
    }

    public function test_a_search_narrowed_to_a_department_continues_on_its_page(): void
    {
        Category::factory()->create(['slug' => 'audio']);

        $this->get('/boutique?q=casque&category=audio')->assertRedirect('/categorie/audio?q=casque');
    }

    public function test_the_product_page_shows_the_product_its_variants_and_recommendations(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create([
            'name' => 'Galaxy S25', 'slug' => 'galaxy-s25', 'price' => 450000,
            'description' => '<p>Écran 6,2 pouces.</p><script>alert(1)</script>',
            'specifications' => [['label' => 'Mémoire', 'value' => '8 Go']],
        ]);
        $color = ProductAttribute::create(['name' => 'Couleur', 'slug' => 'couleur']);
        app(StockManager::class)->createVariant($product, ['sku' => 'S25-BLEU', 'price' => 470000], 2)
            ->attributeValues()->attach($color->values()->create(['value' => 'Bleu']));
        Product::factory()->for($category)->create(['name' => 'Galaxy A56']);

        $this->get('/produit/galaxy-s25')
            ->assertOk()
            ->assertSeeText('Galaxy S25')
            ->assertSeeText('Écran 6,2 pouces.')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSeeText('Mémoire')
            ->assertSeeTextInOrder(['Couleur', 'Bleu'])
            ->assertSee('<meta name="description" content="Écran 6,2 pouces.">', false)
            ->assertSee('data-purchase', false)
            ->assertSee('S25-BLEU')
            ->assertSee('property="og:title" content="Galaxy S25"', false)
            ->assertSeeText('Vous aimerez aussi')
            ->assertSeeText('Galaxy A56');
    }

    public function test_an_inactive_product_page_is_not_found(): void
    {
        Product::factory()->create(['slug' => 'retire', 'is_active' => false]);

        $this->get('/produit/retire')->assertNotFound();
        $this->get('/produit/retire/apercu')->assertNotFound();
    }

    public function test_the_quick_view_shows_the_real_product_and_adds_it_to_the_cart(): void
    {
        $product = Product::factory()->create(['name' => 'Enceinte Flip 6', 'slug' => 'enceinte-flip-6', 'price' => 65000]);
        $color = ProductAttribute::create(['name' => 'Couleur', 'slug' => 'couleur']);
        $product->defaultVariant->attributeValues()->attach($color->values()->create(['value' => 'Noir']));
        $red = app(StockManager::class)->createVariant($product, ['sku' => 'FLIP6-ROUGE', 'price' => 67000], 4);
        $red->attributeValues()->attach($color->values()->create(['value' => 'Rouge']));

        // Product cards point their quick view button at the product's fragment.
        $this->get('/boutique')->assertOk()->assertSee('data-quick-view-url="'.route('products.quick-view', $product).'"', false);

        $this->get('/produit/enceinte-flip-6/apercu')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertDontSee('<html', false)
            ->assertSeeText('Enceinte Flip 6')
            ->assertSeeText("65\u{00A0}000\u{00A0}FCFA")
            ->assertSeeTextInOrder(['Couleur', 'Noir', 'Rouge'])
            ->assertSee('FLIP6-ROUGE')
            ->assertSee('action="'.route('cart.items.store').'"', false)
            ->assertSee('id="quick-view-quantity"', false)
            ->assertSeeText('Voir la fiche complète');

        $this->post('/panier/articles', ['variant_id' => $red->id, 'quantity' => 2, 'open' => 'sidenav'])
            ->assertSessionHas('cart_open', true);
    }

    public function test_the_template_cart_popup_and_its_sample_codes_are_gone(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('popup-cartModal', false)
            ->assertDontSee('WELCOME100')
            ->assertSee('data-quick-view-body', false);
    }

    /**
     * @param  list<int>  $values
     */
    private function productWithVariant(string $name, array $values): void
    {
        Product::factory()->create(['name' => $name])->defaultVariant->attributeValues()->attach($values);
    }
}
