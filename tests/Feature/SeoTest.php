<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Storefront\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_renamed_product_redirects_its_old_address_permanently(): void
    {
        $product = Product::factory()->create(['slug' => 'casque-bleu']);
        $product->update(['slug' => 'casque-bluetooth']);
        $product->update(['slug' => 'casque-sans-fil']);

        $this->get('/produit/casque-bleu?ref=whatsapp')->assertStatus(301)->assertRedirect('/produit/casque-sans-fil?ref=whatsapp');
        $this->get('/produit/casque-bluetooth')->assertStatus(301)->assertRedirect('/produit/casque-sans-fil');
        $this->get('/produit/casque-bluetooth/apercu')->assertStatus(301)->assertRedirect('/produit/casque-sans-fil/apercu');
        $this->get('/produit/casque-sans-fil')->assertOk();
        $this->get('/produit/jamais-existe')->assertNotFound();
    }

    public function test_a_slug_taken_again_serves_its_new_owner(): void
    {
        $first = Product::factory()->create(['slug' => 'enceinte']);
        $first->update(['slug' => 'enceinte-jbl']);

        Product::factory()->create(['name' => 'Nouvelle enceinte', 'slug' => 'enceinte']);

        $this->get('/produit/enceinte')->assertOk()->assertSeeText('Nouvelle enceinte');
    }

    public function test_renamed_categories_and_brands_redirect_too(): void
    {
        Category::factory()->create(['slug' => 'telephones'])->update(['slug' => 'smartphones']);
        Brand::factory()->create(['slug' => 'samsng'])->update(['slug' => 'samsung']);

        $this->get('/categorie/telephones?tri=prix-croissant')->assertStatus(301)->assertRedirect('/categorie/smartphones?tri=prix-croissant');
        $this->get('/marque/samsng')->assertStatus(301)->assertRedirect('/marque/samsung');
    }

    public function test_the_product_page_describes_the_product_to_search_engines(): void
    {
        $category = Category::factory()->create(['name' => 'Audio']);
        $brand = Brand::factory()->create(['name' => 'JBL']);
        $product = Product::factory()->for($category)->for($brand)->create([
            'name' => 'Enceinte </script><script>alert(1)</script>', 'slug' => 'enceinte', 'price' => 45000, 'stock' => 4,
            'description' => '<p>Son puissant &amp; basses profondes.</p>',
        ]);

        $response = $this->get('/produit/enceinte')->assertOk();
        [$data, $breadcrumbs] = $this->jsonLd($response)[0];

        $this->assertSame('Product', $data['@type']);
        $this->assertSame($product->name, $data['name']);
        $this->assertSame('Son puissant & basses profondes.', $data['description']);
        $this->assertSame(['@type' => 'Brand', 'name' => 'JBL'], $data['brand']);
        $this->assertSame($product->defaultVariant->sku, $data['sku']);
        $this->assertSame(['Offer', 45000, 'XOF', 'https://schema.org/InStock'], [$data['offers']['@type'], $data['offers']['price'], $data['offers']['priceCurrency'], $data['offers']['availability']]);
        $this->assertSame(['Accueil', 'Boutique', 'Audio', $product->name], array_column($breadcrumbs['itemListElement'], 'name'));
        // A product text cannot close the data block and inject a script.
        $this->assertStringNotContainsString('<script>alert(1)', $response->getContent());

        $response->assertSee('<link rel="canonical" href="'.$product->url().'">', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:image" content="'.asset($product->image).'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    }

    public function test_variants_at_different_prices_make_an_aggregate_offer_and_sold_out_products_say_so(): void
    {
        $product = Product::factory()->soldOut()->create(['slug' => 'telephone']);
        $product->variants()->create(['sku' => 'KM-TEL-256', 'price' => 350000, 'is_default' => false, 'position' => 2]);

        $offers = $this->jsonLd($this->get('/produit/telephone'))[0][0]['offers'];

        $this->assertSame('AggregateOffer', $offers['@type']);
        $this->assertSame(350000, $offers['highPrice']);
        $this->assertSame('https://schema.org/OutOfStock', $offers['availability']);
    }

    public function test_every_page_has_a_canonical_address_and_a_link_preview(): void
    {
        $response = $this->get('/?utm_source=facebook')->assertOk();

        $response->assertSee('<link rel="canonical" href="'.url('/').'">', false)
            ->assertSee('<meta property="og:site_name" content="'.config('storefront.name').'">', false)
            ->assertSee('<meta property="og:type" content="website">', false);

        [$organization, $website] = $this->jsonLd($response)[0];
        $this->assertSame(['Organization', 'WebSite'], [$organization['@type'], $website['@type']]);
        $this->assertStringContainsString('{search_term_string}', $website['potentialAction']['target']['urlTemplate']);
    }

    public function test_searches_filters_and_private_pages_are_kept_out_of_the_index(): void
    {
        Product::factory()->count(30)->create();

        $this->get('/boutique')->assertSee('content="index, follow"', false);
        $this->get('/boutique?page=2')->assertSee('<link rel="canonical" href="'.url('/boutique').'?page=2">', false);
        $this->get('/boutique?q=casque')->assertSee('content="noindex, follow"', false);
        $this->get('/boutique?en_stock=1')->assertSee('content="noindex, follow"', false);
        $this->get('/panier')->assertSee('content="noindex, nofollow"', false);
        $this->get('/suivi')->assertSee('content="noindex, nofollow"', false);
    }

    public function test_the_sitemap_lists_the_public_pages_and_is_regenerated_by_the_command(): void
    {
        Storage::fake('local');
        $active = Product::factory()->create(['slug' => 'en-ligne']);
        Product::factory()->create(['slug' => 'retire', 'is_active' => false]);
        $category = Category::factory()->create(['slug' => 'audio']);

        $this->artisan('seo:sitemap')->assertSuccessful();
        Storage::disk('local')->assertExists(SitemapGenerator::PATH);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.$active->url().'</loc>', false)
            ->assertSee('<loc>'.$category->url().'</loc>', false)
            ->assertSee('<loc>'.route('home').'</loc>', false)
            ->assertDontSee('/produit/retire', false);
    }

    public function test_robots_open_the_store_in_production_only(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSeeText('Disallow: /');

        $this->app['env'] = 'production';

        $content = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Disallow: /panier', $content);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $content);
        $this->assertStringNotContainsString("Disallow: /\n", $content);
        $this->assertStringNotContainsString(config('admin.path'), $content);
    }

    /**
     * Decoded JSON-LD blocks of the page.
     *
     * @return list<mixed>
     */
    private function jsonLd(TestResponse $response): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);

        return array_map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
    }
}
