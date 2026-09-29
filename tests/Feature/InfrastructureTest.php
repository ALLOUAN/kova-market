<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_preproduction_asks_for_its_shared_login_except_on_the_health_check(): void
    {
        $this->app['env'] = 'staging';
        config(['security.preproduction.user' => 'kova', 'security.preproduction.password' => 'secret']);

        $this->get('/')->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Basic realm="Preproduction", charset="UTF-8"');
        $this->get('/', ['Authorization' => 'Basic '.base64_encode('kova:wrong')])->assertUnauthorized();
        $this->get('/', ['Authorization' => 'Basic '.base64_encode('kova:secret')])->assertOk();
        $this->get('/up')->assertOk();
    }

    public function test_other_environments_are_never_locked(): void
    {
        config(['security.preproduction.user' => 'kova', 'security.preproduction.password' => 'secret']);

        $this->get('/')->assertOk();
    }

    public function test_the_preproduction_never_sends_real_e_mails_or_sms(): void
    {
        $this->app['env'] = 'staging';
        config(['mail.default' => 'smtp', 'services.sms.driver' => 'orange']);

        (new AppServiceProvider($this->app))->register();

        $this->assertSame(['log', 'log'], [config('mail.default'), config('services.sms.driver')]);
    }

    public function test_the_scheduler_empties_the_queue_every_minute(): void
    {
        $worker = collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains($event->command, 'queue:work'));

        $this->assertNotNull($worker);
        $this->assertSame('* * * * *', $worker->expression);
        $this->assertStringContainsString('--stop-when-empty', $worker->command);
    }
}
