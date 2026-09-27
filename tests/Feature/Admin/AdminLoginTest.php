<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_an_account_is_locked_after_five_failed_attempts_even_with_the_right_password(): void
    {
        $picker = User::factory()->staff(Role::Picker)->create(['email' => 'preparateur@kovamarket.ci']);

        for ($attempt = 1; $attempt <= Login::MAX_FAILURES; $attempt++) {
            // Filament's own per-visitor throttle is not what is under test here.
            RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));

            Livewire::test(Login::class)
                ->fillForm(['email' => $picker->email, 'password' => 'mauvais-mot-de-passe'])
                ->call('authenticate')
                ->assertHasFormErrors(['email']);
        }

        RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));

        Livewire::test(Login::class)
            ->fillForm(['email' => $picker->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertNotified('Compte temporairement verrouillé');

        $this->assertGuest();
    }

    public function test_the_create_super_admin_command_installs_roles_and_promotes_the_account(): void
    {
        $this->artisan('app:create-super-admin', ['--name' => 'Direction KOVA', '--email' => 'direction@kovamarket.ci'])
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'un-mot-de-passe-solide')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'direction@kovamarket.ci')->firstOrFail()->hasRole(Role::SuperAdmin->value));
    }

    public function test_the_create_super_admin_command_refuses_a_short_password(): void
    {
        $this->artisan('app:create-super-admin', ['--name' => 'Direction KOVA', '--email' => 'direction@kovamarket.ci'])
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'court')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'direction@kovamarket.ci']);
    }
}
