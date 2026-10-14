<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Search engines: one address for the site in production (301 to https + the host of APP_URL), the ownership tags
 * of Google Search Console and Bing Webmaster Tools, and robots.txt open only in production.
 */
class SearchEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_sends_other_hosts_and_plain_http_to_the_canonical_address(): void
    {
        config(['app.url' => 'https://www.kova-market.com']);
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('http://kova-market.com/boutique?tri=nouveautes')->assertRedirect('https://www.kova-market.com/boutique?tri=nouveautes')->assertStatus(301);
        $this->get('http://www.kova-market.com/faq')->assertRedirect('https://www.kova-market.com/faq');
        $this->get('https://www.kova-market.com/robots.txt')->assertOk()->assertSee('Sitemap: ', false)->assertDontSee('Disallow: /'."\n", false);

        // A webhook posted to the other host is not redirected.
        $this->post('http://kova-market.com/webhooks/whatsapp')->assertStatus(403);
    }

    public function test_behind_the_hosts_front_server_an_https_visit_is_served_without_redirect_loop(): void
    {
        config(['app.url' => 'https://www.kova-market.com']);
        $this->app->detectEnvironment(fn () => 'production');
        $forwarded = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'www.kova-market.com', 'X-Forwarded-Port' => '443'];

        // The front server handles https and passes the visit on in plain http, saying so in X-Forwarded-*.
        $this->withHeaders($forwarded)->get('http://www.kova-market.com/faq')->assertOk();
        $this->withHeaders([...$forwarded, 'X-Forwarded-Proto' => 'http'])->get('http://www.kova-market.com/faq')->assertRedirect('https://www.kova-market.com/faq');
    }

    public function test_outside_production_the_site_stays_hidden_and_is_not_redirected(): void
    {
        $this->get('http://kova-market.com/robots.txt')->assertOk()->assertSee('Disallow: /', false);
    }

    public function test_the_ownership_tags_are_printed_once_filled_in(): void
    {
        Setting::store(['analytics.search_console_token' => 'abcDEF123_google-token', 'analytics.bing_token' => 'ABCDEF0123456789ABCDEF0123456789']);

        $this->get('/')
            ->assertSee('<meta name="google-site-verification" content="abcDEF123_google-token">', false)
            ->assertSee('<meta name="msvalidate.01" content="ABCDEF0123456789ABCDEF0123456789">', false);
    }
}
