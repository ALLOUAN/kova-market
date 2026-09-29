<?php

namespace Tests\Feature;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\Money;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_banner_slots_hide_their_banner_and_never_show_the_template_samples(): void
    {
        // Nothing published: no slider, no sample banner with its made-up prices.
        $this->get('/')->assertOk()->assertDontSeeText('GOPRO')->assertDontSeeText('Faites-vous plaisir')
            ->assertDontSee('rbt-hero-banner-activation-1', false);
    }

    public function test_a_published_banner_shows_with_its_own_button_text(): void
    {
        Banner::create(['placement' => BannerPlacement::Hero, 'image' => 'assets/images/x.webp', 'highlight' => 'SAMSUNG', 'title' => 'GALAXY S25', 'button_label' => 'Je découvre']);
        Banner::create(['placement' => BannerPlacement::Closing, 'image' => 'c.webp', 'title' => 'Rentrée']);

        $this->get('/')
            ->assertOk()
            ->assertSeeText('SAMSUNG')
            ->assertSee('Je<br>découvre', false)
            // No button text: the default one.
            ->assertSee('Acheter<br>maintenant', false);
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
            ->assertDontSeeText('Faites-vous plaisir');
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
        Banner::create(['placement' => BannerPlacement::Hero, 'image' => 'h.webp', 'title' => 'Sans lien']);
        $content = $this->get('/')->assertOk()->getContent();

        // A banner without a link of its own leads to the shop.
        $this->assertStringNotContainsString('rbt-magnetic-button text-uppercase" href="#"', $content);
        $this->assertStringContainsString('rbt-magnetic-button text-uppercase" href="'.route('shop.index').'"', $content);
        $this->assertStringNotContainsString('Ajouter au comparateur', $content);
    }

    public function test_a_home_selection_has_its_own_page_and_can_be_prepared_in_advance(): void
    {
        $selection = ProductCollection::create(['name' => 'Offres du jour', 'slug' => 'deals-of-the-day']);
        $first = Product::factory()->create(['name' => 'Casque premier']);
        $second = Product::factory()->create(['name' => 'Enceinte deuxième']);
        Product::factory()->create(['name' => 'Produit hors sélection']);
        $selection->products()->attach([$second->id => ['position' => 2], $first->id => ['position' => 1]]);

        $this->get('/')->assertSee('href="'.route('collections.show', $selection).'"', false);
        $this->get(route('collections.show', $selection))
            ->assertOk()
            ->assertSeeText('Offres du jour')
            ->assertSeeInOrder(['Casque premier', 'Enceinte deuxième'])
            ->assertDontSeeText('Produit hors sélection');
    }

    public function test_a_selection_prepared_for_later_is_not_shown_yet(): void
    {
        $selection = ProductCollection::create(['name' => 'Black Friday', 'slug' => 'deals-of-the-day', 'starts_at' => now()->addWeek()]);
        $selection->products()->attach(Product::factory()->create()->id, ['position' => 1]);

        $this->get('/')->assertDontSeeText('Black Friday');
        $this->get(route('collections.show', $selection))->assertNotFound();
    }

    public function test_a_banner_promoting_a_product_follows_its_price_and_leads_to_it(): void
    {
        $product = Product::factory()->create(['name' => 'Enceinte JBL']);
        $product->defaultVariant->update(['price' => 45000, 'compare_at_price' => 60000]);
        $product->syncFromVariants();
        Banner::create(['placement' => BannerPlacement::Hero, 'product_id' => $product->id, 'image' => 'c.webp', 'title' => 'Son puissant', 'price' => 1, 'badge' => 'Faux']);

        $this->get('/')
            ->assertSee('<span class="rbt-price-text offer-price">'.Money::format(45000), false)
            ->assertSee(Money::format(60000), false)
            ->assertSeeText('-25 %')
            ->assertDontSeeText('Faux')
            ->assertSee('href="'.$product->url().'"', false);
    }

    public function test_a_banner_whose_product_is_taken_off_shows_no_price(): void
    {
        $product = Product::factory()->create(['is_active' => false]);
        Banner::create(['placement' => BannerPlacement::Closing, 'product_id' => $product->id, 'image' => 'c.webp', 'title' => 'Produit retiré', 'price' => 99000]);

        $this->get('/')->assertSeeText('Produit retiré')->assertDontSee('rbt-price-text offer-price', false)
            ->assertSee('text-uppercase" href="'.route('shop.index').'"', false);
    }

    public function test_home_sections_follow_the_order_and_visibility_set_in_the_back_office(): void
    {
        Setting::store(['home.sections' => json_encode([
            ['key' => 'brands', 'visible' => true],
            ['key' => 'guarantees', 'visible' => true],
            ['key' => 'new_arrivals', 'visible' => false],
        ])]);
        Brand::create(['name' => 'Sony', 'slug' => 'sony', 'logo' => 'assets/images/sony.webp']);
        Product::factory()->create();

        $this->get('/')
            ->assertSeeInOrder(['Nos marques', 'kova-home-guarantees'], false)
            ->assertDontSee('data-analytics-list="{&quot;item_list_id&quot;:&quot;new_arrivals&quot;', false)
            // Sections the setting does not list stay shown, after the others.
            ->assertSee('data-analytics-list="{&quot;item_list_id&quot;:&quot;popular&quot;', false);
    }

    public function test_the_home_page_shows_the_guarantees_and_its_own_title(): void
    {
        $this->get('/')
            ->assertSee('<title>'.config('storefront.name').' - Boutique en ligne à Abidjan</title>', false)
            ->assertSeeText('Livraison rapide')
            ->assertSeeText('Paiement à la livraison');

        Setting::store([
            'identity.home_title' => 'KOVA MARKET, high-tech livré à Abidjan',
            'home.guarantees' => json_encode([['icon' => 'award', 'title' => 'Produits authentiques', 'text' => 'Garantie constructeur']]),
        ]);

        $this->get('/')
            ->assertSee('<title>KOVA MARKET, high-tech livré à Abidjan</title>', false)
            ->assertSeeText('Produits authentiques')
            ->assertDontSeeText('Payez en recevant votre colis');
    }

    public function test_a_campaign_links_to_its_offer_only_when_it_has_a_link(): void
    {
        Promotion::factory()->create(['title' => 'Semaine audio', 'url' => '/categorie/audio']);
        Promotion::factory()->create(['title' => 'Fête des mères']);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<a href="/categorie/audio">Semaine audio</a>', $content);
        $this->assertSame(1, substr_count($content, 'Voir le détail'));
    }

    public function test_hidden_campaigns_are_left_out_and_the_others_follow_the_chosen_order(): void
    {
        Promotion::factory()->create(['title' => 'Deuxième choisie', 'position' => 2, 'starts_at' => now()->subDays(3)]);
        Promotion::factory()->create(['title' => 'Première choisie', 'position' => 1, 'starts_at' => now()->subDay()]);
        Promotion::factory()->create(['title' => 'Campagne masquée', 'is_visible' => false]);

        $this->get('/')->assertSeeInOrder(['Première choisie', 'Deuxième choisie'])->assertDontSeeText('Campagne masquée');
    }

    public function test_without_visible_campaign_the_special_offers_panel_says_so(): void
    {
        Promotion::factory()->create(['title' => 'Campagne masquée', 'is_visible' => false]);

        $this->get('/')->assertSeeText('Aucune offre spéciale en ce moment.')->assertDontSeeText('Campagne masquée');
    }

    public function test_the_shop_mega_menu_promo_uses_the_back_office_image_and_button(): void
    {
        $promo = ['label' => 'À partir du', 'highlight' => '11 décembre', 'title' => 'Jusqu’à -40 %', 'subtitle' => 'Sur toutes les marques'];
        Category::factory()->create(['name' => 'Montres', 'position' => 1, 'promo' => [...$promo, 'menu_image' => 'uploads/categories/fond-montres.webp', 'button' => 'Découvrir les montres']]);
        // Nothing chosen: the theme's background and "Voir la collection".
        Category::factory()->create(['name' => 'Audio', 'position' => 2, 'promo' => $promo]);

        $response = $this->get('/')
            ->assertSee("background-image: url('".asset('uploads/categories/fond-montres.webp')."')", false)
            ->assertSeeText('Découvrir les montres')
            ->assertSeeText('Voir la collection')
            ->assertSeeText('Jusqu’à -40 %');

        // Only the category with an image of its own (the menu is in the header twice: normal and sticky).
        $this->assertSame(substr_count($response->getContent(), 'Découvrir les montres'), substr_count($response->getContent(), 'background-image: url('));
    }

    public function test_a_missing_page_keeps_the_visitor_in_the_store(): void
    {
        $this->get('/cette-page-n-existe-pas')
            ->assertNotFound()
            ->assertSeeText('Cette page est introuvable')
            ->assertSee(route('shop.index'), false)
            ->assertSee('assets/css/kova.css', false);
    }

    public function test_the_sign_in_windows_show_the_published_testimonials_or_the_guarantees(): void
    {
        // Nothing published: the store's guarantees, no made-up review.
        $this->get('/')
            ->assertDontSee('rbt-client-review', false)
            ->assertSee('kova-guarantees', false)
            ->assertSee('kova-network-logos', false)
            ->assertSee('assets/images/payment/wave.webp', false);

        Testimonial::create(['author_name' => 'Awa K.', 'city' => 'Cocody', 'content' => 'Commande reçue le lendemain, très bien emballée.', 'rating' => 4, 'is_verified' => true]);
        Testimonial::create(['author_name' => 'Brouillon', 'content' => 'Pas encore relu.', 'is_published' => false]);

        $this->get('/')
            ->assertSeeText('Commande reçue le lendemain, très bien emballée.')
            ->assertSeeText('Awa K.')
            ->assertSeeText('Client vérifié')
            ->assertSee('Note : 4 sur 5', false)
            ->assertDontSeeText('Pas encore relu.')
            ->assertDontSee('kova-guarantees', false);
    }

    public function test_error_and_maintenance_pages_carry_the_charter_without_the_database(): void
    {
        Route::get('/_test-maintenance', fn () => abort(503));

        $this->get('/_test-maintenance')
            ->assertStatus(503)
            ->assertSee('kova-logo-400.webp', false)
            ->assertSeeText('La boutique est en cours de mise à jour')
            ->assertSeeText('Retour à la boutique')
            ->assertSee('#021732', false);
    }

    public function test_emails_use_the_kova_logo_and_colours(): void
    {
        $html = (string) (new MailMessage)->line('Votre commande est confirmée.')->action('Suivre ma commande', url('/'))->render();

        $this->assertStringContainsString('assets/images/logo/kova-logo.png', $html);
        $this->assertStringContainsString('#0e7d42', $html);
        $this->assertStringNotContainsString('laravel.com/img', $html);
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
