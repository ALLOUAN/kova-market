<?php

namespace Tests\Feature;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Setting;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_the_footer_links_to_the_legal_pages_installed_by_the_content_seeder(): void
    {
        $this->seed(ContentSeeder::class);

        $this->get('/')->assertOk()->assertSee(route('pages.show', 'conditions-generales-de-vente'), false);
        $this->get(route('pages.show', 'politique-de-confidentialite'))->assertOk();
    }
}
