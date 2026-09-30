<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\Maintenance as MaintenancePage;
use App\Models\Setting;
use App\Models\User;
use App\Services\Storefront\Maintenance;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Maintenance mode switched from Administration › Maintenance: visitors get the maintenance page, the team, the
 * allowed addresses, the back-office, the courier app and CinetPay keep the site.
 */
class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_visitors_get_the_maintenance_page_while_it_is_on(): void
    {
        $this->get('/')->assertOk()->assertDontSee('kova-maintenance-bar', false);

        Setting::store(['maintenance.message' => 'Nouvelle boutique en préparation.', 'maintenance.duration' => '3', 'maintenance.progress' => '40']);
        app(Maintenance::class)->enable();

        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After')
            ->assertSeeText('Nous revenons très vite')
            ->assertSeeText('Nouvelle boutique en préparation.')
            ->assertSeeText('40 %')
            ->assertSeeText('3 h')
            ->assertSeeText('Retour prévu');
        $this->get('/contact')->assertStatus(503);

        app(Maintenance::class)->disable();
        $this->get('/')->assertOk();
    }

    public function test_the_team_allowed_addresses_and_the_services_keep_the_site(): void
    {
        app(Maintenance::class)->enable();

        // CinetPay, the courier app, the health check and the back-office stay reachable.
        $this->get('/livreur/connexion')->assertOk();
        $this->get('/up')->assertOk();
        $this->get('/'.config('admin.path').'/login')->assertOk();
        $this->post('/paiement/cinetpay/notification')->assertDontSeeText('Nous revenons très vite');

        // An allowed address (single or range).
        Setting::store(['maintenance.allowed_ips' => "10.0.0.0/24\n41.202.10.5"]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.42'])->get('/')->assertOk()->assertSee('kova-maintenance-bar', false);
        $this->withServerVariables(['REMOTE_ADDR' => '41.202.10.6'])->get('/')->assertStatus(503);

        // A signed-in team member; a customer still gets the maintenance page.
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        $this->actingAs(User::factory()->customer()->create())->get('/')->assertStatus(503);
        $this->actingAs(User::factory()->staff(Role::Manager)->create())->get('/')->assertOk()->assertSeeText('Mode maintenance actif');
    }

    public function test_the_back_office_switches_it_and_sets_the_page(): void
    {
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());

        Livewire::test(MaintenancePage::class)
            ->assertSeeText('Le site est en ligne')
            ->fillForm(['message' => 'Mise à jour du catalogue.', 'duration' => 1.5, 'progress' => 60, 'allowed_ips' => "192.168.1.1\npas-une-ip"])
            ->call('save')
            ->assertHasFormErrors(['allowed_ips']);

        Livewire::test(MaintenancePage::class)
            ->fillForm(['message' => 'Mise à jour du catalogue.', 'duration' => 1.5, 'progress' => 60, 'allowed_ips' => "192.168.1.1\n10.0.0.0/24"])
            ->call('save')
            ->assertHasNoFormErrors();

        $maintenance = app(Maintenance::class);
        $this->assertSame(['Mise à jour du catalogue.', 1.5, 60, ['192.168.1.1', '10.0.0.0/24']], [$maintenance->message(), $maintenance->duration(), $maintenance->progress(), $maintenance->allowedIps()]);
        $this->assertSame('1 h 30', $maintenance->durationLabel());

        Livewire::test(MaintenancePage::class)->callAction('toggle');
        $this->assertTrue($maintenance->enabled());
        $this->assertNotNull($maintenance->startedAt());

        Livewire::test(MaintenancePage::class)->assertSeeText('Le site est en maintenance')->callAction('toggle');
        $this->assertFalse($maintenance->enabled());
    }

    public function test_the_preview_is_for_those_who_manage_it(): void
    {
        $this->get(route('maintenance.preview'))->assertRedirect();

        $this->actingAs(User::factory()->staff(Role::Picker)->create())->get(route('maintenance.preview'))->assertForbidden();

        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create())
            ->get(route('maintenance.preview'))
            ->assertOk()
            ->assertSeeText('Nous revenons très vite');
    }

    public function test_only_settings_managers_open_the_page(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create())->get(MaintenancePage::getUrl())->assertForbidden();
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create())->get(MaintenancePage::getUrl())->assertOk()->assertSeeText('Mode maintenance');
    }
}
