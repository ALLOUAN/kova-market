<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewsletterWelcome;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Newsletter: sign-up from the footer or the invitation window, welcome e-mail, one-click unsubscribe, list and
 * export in the back-office.
 */
class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_signs_up_and_gets_the_welcome_email_once(): void
    {
        Notification::fake();

        $this->get('/')->assertSee('action="'.route('newsletter.store').'"', false);

        $this->postJson(route('newsletter.store'), ['email' => ' Awa@Exemple.ci ', 'source' => 'footer'])
            ->assertOk()
            ->assertJson(['message' => 'Merci ! Vous êtes inscrit à notre newsletter. Un e-mail de bienvenue vient de vous être envoyé.']);

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('awa@exemple.ci', $subscriber->email);
        $this->assertTrue($subscriber->isActive());
        Notification::assertSentOnDemand(NewsletterWelcome::class, fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'awa@exemple.ci');

        // Twice: no second row, no second e-mail.
        $this->postJson(route('newsletter.store'), ['email' => 'awa@exemple.ci'])->assertJson(['message' => 'Vous êtes déjà inscrit à notre newsletter, merci !']);
        $this->assertSame(1, NewsletterSubscriber::count());
        Notification::assertSentOnDemandTimes(NewsletterWelcome::class, 1);
    }

    public function test_a_wrong_address_and_robots_are_turned_away(): void
    {
        $this->postJson(route('newsletter.store'), ['email' => 'pas-une-adresse'])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson(route('newsletter.store'), ['email' => 'robot@exemple.ci', 'website' => 'https://spam.example'])->assertOk();

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_the_unsubscribe_link_asks_first_then_unsubscribes(): void
    {
        $subscriber = NewsletterSubscriber::subscribe('awa@exemple.ci', 'popup');

        // Opening the link (or a mail scanner opening it) changes nothing.
        $this->get(route('newsletter.unsubscribe', $subscriber))->assertOk()->assertSeeText('Se désinscrire de la newsletter ?');
        $this->assertTrue($subscriber->fresh()->isActive());

        $this->post(route('newsletter.unsubscribe.confirm', $subscriber))->assertOk()->assertSeeText('Vous êtes désinscrit');
        $this->assertFalse($subscriber->fresh()->isActive());

        // Signing up again is a new, dated consent.
        NewsletterSubscriber::subscribe('awa@exemple.ci', 'footer');
        $this->assertTrue($subscriber->fresh()->isActive());
    }

    public function test_the_back_office_lists_exports_and_unsubscribes(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $active = NewsletterSubscriber::subscribe('awa@exemple.ci', 'footer');
        $gone = NewsletterSubscriber::subscribe('koffi@exemple.ci', 'popup');
        $gone->unsubscribe();

        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(ListNewsletterSubscribers::class)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$gone]);

        $export = Livewire::test(ListNewsletterSubscribers::class)->callAction('export');
        $csv = base64_decode($export->effects['download']['content']);
        $this->assertStringContainsString('awa@exemple.ci', $csv);
        $this->assertStringNotContainsString('koffi@exemple.ci', $csv);
        $this->assertStringContainsString(route('newsletter.unsubscribe', $active), $csv);

        Livewire::test(ListNewsletterSubscribers::class)->callAction(TestAction::make('unsubscribe')->table($active));
        $this->assertFalse($active->fresh()->isActive());
    }

    public function test_the_settings_switch_the_footer_form_and_the_invitation_window(): void
    {
        $this->get('/')->assertSee('id="newsletter"', false)->assertDontSee('id="newsletterModal"', false);

        Setting::store(['newsletter.footer' => '0', 'newsletter.popup' => '1', 'newsletter.popup_title' => 'Offres réservées aux abonnés']);

        $this->get('/')
            ->assertDontSee('id="newsletter"', false)
            ->assertSee('id="newsletterModal"', false)
            ->assertSeeText('Offres réservées aux abonnés');

        // Never during a purchase.
        $this->get('/panier')->assertDontSee('id="newsletterModal"', false);
    }
}
