<?php

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\Role;
use App\Filament\Resources\NewsletterCampaigns\Pages\CreateNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\EditNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\ListNewsletterCampaigns;
use App\Filament\Resources\NewsletterCampaigns\Pages\ViewNewsletterCampaign;
use App\Jobs\SendNewsletterCampaignBatch;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\CampaignSender;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Newsletter campaigns by e-mail: written in the back-office, tested, sent at once or at a date to the active
 * subscribers only, each once, in queued batches; stopped while going out; followed with exact counters.
 */
class NewsletterCampaignTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create(['email' => 'gestion@kovamarket.ci']);
    }

    public function test_a_campaign_goes_to_the_active_subscribers_only_once_each(): void
    {
        Mail::fake();
        $awa = NewsletterSubscriber::subscribe('awa@exemple.ci', 'footer');
        $koffi = NewsletterSubscriber::subscribe('koffi@exemple.ci', 'popup');
        NewsletterSubscriber::subscribe('parti@exemple.ci', 'footer')->unsubscribe();
        $campaign = $this->campaign();

        $count = app(CampaignSender::class)->launch($campaign, $this->manager);

        $this->assertSame(2, $count);
        Mail::assertSent(NewsletterCampaignMail::class, 2);
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('awa@exemple.ci') && $mail->subscriber->is($awa));
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('koffi@exemple.ci') && $mail->subscriber->is($koffi));
        Mail::assertNotSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('parti@exemple.ci'));

        $campaign->refresh();
        $this->assertSame(CampaignStatus::Sent, $campaign->status);
        $this->assertSame([2, 2, 0, 0, 100], [$campaign->recipients_count, $campaign->sent_count, $campaign->failed_count, $campaign->skipped_count, $campaign->progress()]);
        $this->assertNotNull($campaign->sent_at);

        // A batch run again (retried job) sends nothing more.
        app(CampaignSender::class)->sendBatch($campaign, $campaign->recipients()->pluck('id')->all());
        Mail::assertSent(NewsletterCampaignMail::class, 2);
    }

    public function test_the_e_mail_carries_the_content_the_button_and_the_unsubscribe_link(): void
    {
        $subscriber = NewsletterSubscriber::subscribe('awa@exemple.ci', 'footer');
        $mail = new NewsletterCampaignMail($this->campaign(), $subscriber);

        $mail->assertHasSubject('-20 % sur l’électroménager');
        $mail->assertSeeInHtml('Jusqu’à dimanche minuit', false);
        $mail->assertSeeInHtml('Les offres du week-end</h2>', false);
        $mail->assertDontSeeInHtml('&lt;h2&gt;', false);
        $mail->assertSeeInHtml('Voir les offres');
        $mail->assertSeeInHtml(route('newsletter.unsubscribe', $subscriber), false);
        $mail->assertDontSeeInHtml('E-mail de test');
        $this->assertSame('<'.route('newsletter.unsubscribe', $subscriber).'>', $mail->headers()->text['List-Unsubscribe']);

        // A test send says so and goes to the address given only.
        Mail::fake();
        app(CampaignSender::class)->sendTest($this->campaign(), 'moi@kovamarket.ci');
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('moi@kovamarket.ci') && $mail->envelope()->subject === '[TEST] -20 % sur l’électroménager');
    }

    public function test_the_e_mails_leave_through_the_queue_in_batches(): void
    {
        Queue::fake();
        foreach (range(1, CampaignSender::BATCH_SIZE + 3) as $i) {
            NewsletterSubscriber::subscribe("abonne{$i}@exemple.ci", 'footer');
        }

        app(CampaignSender::class)->launch($campaign = $this->campaign());

        Queue::assertPushed(SendNewsletterCampaignBatch::class, 2);
        $this->assertSame(CampaignStatus::Sending, $campaign->fresh()->status);
        $this->assertSame(CampaignSender::BATCH_SIZE + 3, $campaign->fresh()->recipients_count);
    }

    public function test_someone_who_unsubscribes_before_their_batch_is_skipped_and_a_stopped_campaign_sends_no_more(): void
    {
        Mail::fake();
        Queue::fake();
        $awa = NewsletterSubscriber::subscribe('awa@exemple.ci', 'footer');
        NewsletterSubscriber::subscribe('koffi@exemple.ci', 'footer');
        NewsletterSubscriber::subscribe('yao@exemple.ci', 'footer');
        $sender = app(CampaignSender::class);
        $sender->launch($campaign = $this->campaign());
        $awa->unsubscribe();

        $awaRecipient = $campaign->recipients()->where('newsletter_subscriber_id', $awa->id)->sole();
        $koffiRecipient = $campaign->recipients()->where('newsletter_subscriber_id', '!=', $awa->id)->orderBy('id')->first();
        $sender->sendBatch($campaign, [$awaRecipient->id, $koffiRecipient->id]);

        $this->assertSame(NewsletterCampaignRecipient::SKIPPED, $awaRecipient->fresh()->status);
        $this->assertSame(NewsletterCampaignRecipient::SENT, $koffiRecipient->fresh()->status);
        Mail::assertSent(NewsletterCampaignMail::class, 1);

        $sender->stop($campaign->fresh(), $this->manager);
        $campaign->refresh();
        $this->assertSame(CampaignStatus::Cancelled, $campaign->status);
        $this->assertSame([1, 2], [$campaign->sent_count, $campaign->skipped_count]);
        $sender->sendBatch($campaign, $campaign->recipients()->pluck('id')->all());
        Mail::assertSent(NewsletterCampaignMail::class, 1);
    }

    public function test_a_scheduled_campaign_leaves_at_its_date(): void
    {
        Mail::fake();
        NewsletterSubscriber::subscribe('awa@exemple.ci', 'footer');
        $campaign = $this->campaign();
        app(CampaignSender::class)->schedule($campaign, now()->addHour());

        $this->artisan('newsletter:send-scheduled')->assertSuccessful();
        Mail::assertNothingSent();
        $this->assertSame(CampaignStatus::Scheduled, $campaign->fresh()->status);

        $this->travel(61)->minutes();
        $this->artisan('newsletter:send-scheduled')->expectsOutput('1 campagne(s) lancée(s).')->assertSuccessful();
        Mail::assertSent(NewsletterCampaignMail::class, 1);
        $this->assertSame(CampaignStatus::Sent, $campaign->fresh()->status);
    }

    public function test_the_manager_writes_tests_and_sends_a_campaign_from_the_back_office(): void
    {
        Mail::fake();
        NewsletterSubscriber::subscribe('awa@exemple.ci', 'footer');
        $this->actingAs($this->manager);

        Livewire::test(CreateNewsletterCampaign::class)
            ->fillForm([
                'subject' => 'Nouveautés de la semaine',
                'preheader' => 'Trois produits à découvrir',
                'content' => '<p>Bonjour, voici nos nouveautés.</p>',
                'button_label' => 'Découvrir',
                'button_url' => 'https://kovamarket.ci/boutique',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $campaign = NewsletterCampaign::sole();
        $this->assertSame([CampaignStatus::Draft, $this->manager->id], [$campaign->status, $campaign->created_by]);

        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->getRouteKey()])
            ->assertActionVisible('preview')
            ->callAction('sendTest', ['email' => 'moi@kovamarket.ci'])
            ->assertHasNoActionErrors()
            ->callAction('sendNow')
            ->assertHasNoActionErrors();

        Mail::assertSent(NewsletterCampaignMail::class, 2);
        $this->assertSame(CampaignStatus::Sent, $campaign->fresh()->status);

        // Once sent: no more editing, the follow-up page instead.
        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->getRouteKey()])->assertRedirect(ViewNewsletterCampaign::getUrl(['record' => $campaign]));
        $this->get(ViewNewsletterCampaign::getUrl(['record' => $campaign]))->assertOk()->assertSeeText('Destinataires')->assertSeeText('100 %');
        Livewire::test(ListNewsletterCampaigns::class)->assertCanSeeTableRecords([$campaign])->assertSeeText('1 / 1 envoyés');
    }

    public function test_the_manager_schedules_then_unschedules(): void
    {
        $campaign = $this->campaign();
        $this->actingAs($this->manager);

        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->getRouteKey()])
            ->callAction('schedule', ['scheduled_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s')])
            ->assertHasNoActionErrors();
        $this->assertSame(CampaignStatus::Scheduled, $campaign->fresh()->status);

        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->getRouteKey()])
            ->callAction('unschedule')
            ->assertHasNoActionErrors();
        $this->assertSame([CampaignStatus::Draft, null], [$campaign->fresh()->status, $campaign->fresh()->scheduled_at]);
    }

    public function test_the_follow_up_page_says_when_the_queue_does_not_run(): void
    {
        Queue::fake();
        NewsletterSubscriber::subscribe('awa@exemple.ci', 'footer');
        app(CampaignSender::class)->launch($campaign = $this->campaign());
        $this->actingAs($this->manager);
        $page = ViewNewsletterCampaign::getUrl(['record' => $campaign]);

        $this->get($page)->assertOk()->assertSeeText('Envoi en cours')->assertDontSeeText('Aucun e-mail n’est encore parti.');

        $this->travel(3)->minutes();
        $this->get($page)->assertSeeText('Aucun e-mail n’est encore parti.')->assertSee('demarrer-local.bat', false);
    }

    public function test_a_picker_cannot_open_the_campaigns(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        $this->get(ListNewsletterCampaigns::getUrl())->assertForbidden();
    }

    private function campaign(): NewsletterCampaign
    {
        return NewsletterCampaign::create([
            'subject' => '-20 % sur l’électroménager',
            'preheader' => 'Jusqu’à dimanche minuit',
            'content' => '<h2>Les offres du week-end</h2><p>Climatiseurs, réfrigérateurs et micro-ondes à prix doux.</p>',
            'button_label' => 'Voir les offres',
            'button_url' => 'https://kovamarket.ci/boutique',
            'created_by' => $this->manager->id,
        ]);
    }
}
