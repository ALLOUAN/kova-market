<?php

namespace Tests\Feature;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_falls_back_to_the_default_banners_until_one_is_published(): void
    {
        $this->get('/')->assertOk()->assertSeeText('GOPRO');

        Banner::create(['placement' => BannerPlacement::Hero, 'image' => 'assets/images/x.webp', 'highlight' => 'SAMSUNG', 'title' => 'GALAXY S25']);

        $this->get('/')->assertOk()->assertSeeText('SAMSUNG')->assertDontSeeText('GOPRO');
    }

    public function test_hidden_scheduled_and_expired_banners_are_not_shown(): void
    {
        Banner::create(['placement' => BannerPlacement::Closing, 'image' => 'a.webp', 'title' => 'Bannière masquée', 'is_visible' => false]);
        Banner::create(['placement' => BannerPlacement::Closing, 'image' => 'b.webp', 'title' => 'Bannière à venir', 'starts_at' => now()->addDay()]);
        Banner::create(['placement' => BannerPlacement::Closing, 'image' => 'c.webp', 'title' => 'Bannière expirée', 'ends_at' => now()->subDay()]);

        $this->get('/')
            ->assertOk()
            ->assertDontSeeText('Bannière masquée')
            ->assertDontSeeText('Bannière à venir')
            ->assertDontSeeText('Bannière expirée')
            ->assertSeeText('Faites-vous plaisir');
    }

    public function test_a_banner_without_price_shows_no_price_and_links_where_asked(): void
    {
        Banner::create(['placement' => BannerPlacement::Closing, 'image' => 'c.webp', 'title' => 'Rentrée', 'url' => 'https://kovamarket.ci/rentree']);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="https://kovamarket.ci/rentree"', false)
            ->assertDontSee('<span class="rbt-price-text offer-price">0', false);
    }

    public function test_contact_details_and_social_links_come_from_the_settings(): void
    {
        Setting::store(['contact.phone' => '+225 01 02 03 04 05', 'social.instagram' => 'https://instagram.com/kovamarket']);

        $this->get('/')
            ->assertOk()
            ->assertSeeText('+225 01 02 03 04 05')
            ->assertSee('https://instagram.com/kovamarket', false);
    }

    public function test_published_pages_are_displayed_sanitized_and_unpublished_ones_are_not_found(): void
    {
        Page::create(['title' => 'Livraison', 'slug' => 'livraison', 'content' => '<p>Livré sous 48 h.</p><script>alert(1)</script>']);
        Page::create(['title' => 'Brouillon', 'slug' => 'brouillon', 'content' => '<p>…</p>', 'is_published' => false]);

        $this->get('/page/livraison')->assertOk()->assertSeeText('Livré sous 48 h.')->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/page/brouillon')->assertNotFound();
    }

    public function test_the_faq_lists_published_questions_by_topic(): void
    {
        Faq::create(['topic' => 'Paiement', 'question' => 'Acceptez-vous Wave ?', 'answer' => 'Oui.']);
        Faq::create(['topic' => 'Paiement', 'question' => 'Question masquée ?', 'answer' => 'Non.', 'is_published' => false]);

        $this->get('/faq')->assertOk()->assertSeeText('Paiement')->assertSeeText('Acceptez-vous Wave ?')->assertDontSeeText('Question masquée ?');
    }

    public function test_home_buttons_never_lead_nowhere(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        // Default banners have no link of their own: they lead to the shop.
        $this->assertStringNotContainsString('rbt-magnetic-button" href="#"', $content);
        $this->assertStringContainsString('rbt-magnetic-button" href="'.route('shop.index').'"', $content);
        $this->assertStringNotContainsString('Ajouter au comparateur', $content);
    }

    public function test_a_campaign_links_to_its_offer_only_when_it_has_a_link(): void
    {
        Promotion::factory()->create(['title' => 'Semaine audio', 'url' => '/categorie/audio']);
        Promotion::factory()->create(['title' => 'Fête des mères']);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<a href="/categorie/audio">Semaine audio</a>', $content);
        $this->assertSame(1, substr_count($content, 'Voir le détail'));
    }

    public function test_recently_viewed_lists_the_visitors_own_product_pages_most_recent_first(): void
    {
        $first = Product::factory()->create(['name' => 'Casque vu en premier', 'slug' => 'casque']);
        $second = Product::factory()->create(['name' => 'Enceinte vue ensuite', 'slug' => 'enceinte']);
        Product::factory()->create(['name' => 'Jamais consulté']);

        $this->get('/')->assertSeeText('Les produits que vous consultez apparaîtront ici.');

        $this->get('/produit/casque');
        $this->get('/produit/enceinte');

        // The modal sits in the layout just before the page's <main>.
        $modal = Str::between($this->get('/')->getContent(), 'id="recent-viewModal"', '<main');
        $this->assertStringNotContainsString('Jamais consulté', $modal);
        $this->assertTrue(strpos($modal, $second->name) < strpos($modal, $first->name));
    }

    public function test_popular_searches_lead_to_their_results(): void
    {
        $this->get('/')->assertSee('href="'.e(route('shop.index', ['q' => 'Smartphones'])).'"', false);
    }

    public function test_the_footer_links_to_the_legal_pages_installed_by_the_content_seeder(): void
    {
        $this->seed(ContentSeeder::class);

        $this->get('/')->assertOk()->assertSee(route('pages.show', 'conditions-generales-de-vente'), false);
        $this->get(route('pages.show', 'politique-de-confidentialite'))->assertOk();
    }
}
