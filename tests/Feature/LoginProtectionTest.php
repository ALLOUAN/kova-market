<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sign-in protections (F-147): the storefront and the mobile app are for customers only (the team goes through the
 * back-office and its two-factor code), tries are capped per account, per address and at sign-up, and personal
 * pages are never cached.
 */
class LoginProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_member_cannot_sign_in_on_the_storefront_and_skip_the_two_factor_code(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->staff(Role::SuperAdmin)->create(['email' => 'admin@kovamarket.ci'])
            ->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

        $this->post('/login', ['login' => 'admin@kovamarket.ci', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
        $staff = session('errors')->first('login');
        $this->get('/'.config('admin.path'))->assertRedirect();

        $this->postJson('/api/v1/auth/login', ['login' => 'admin@kovamarket.ci', 'password' => 'password', 'device_name' => 'iPhone'])
            ->assertUnprocessable()->assertJsonValidationErrors('login');

        // Same message as a wrong password or an unknown account: nothing tells a team account apart.
        $this->post('/login', ['login' => 'inconnu@kovamarket.ci', 'password' => 'password'])->assertSessionHasErrors(['login' => $staff]);
    }

    public function test_tries_are_capped_per_account_whatever_the_address_and_per_address_whatever_the_account(): void
    {
        User::factory()->customer()->create(['phone' => '0701020304']);

        foreach (range(1, 20) as $try) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$try}"])
                ->post('/login', ['login' => '07 01 02 03 04', 'password' => "essai-{$try}"])->assertSessionHasErrors('login');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->post('/login', ['login' => '+2250701020304', 'password' => 'password'])->assertTooManyRequests();

        foreach (range(1, 30) as $try) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])
                ->post('/login', ['login' => "client{$try}@example.com", 'password' => 'password'])->assertSessionHasErrors('login');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])
            ->post('/login', ['login' => 'client31@example.com', 'password' => 'password'])->assertTooManyRequests();
    }

    public function test_sign_up_is_limited_per_address(): void
    {
        foreach (range(1, 10) as $try) {
            $this->post('/register', ['name' => 'Test', 'phone' => '0701020304', 'password' => 'x', 'password_confirmation' => 'x'])->assertSessionHasErrors();
        }

        $this->post('/register', ['name' => 'Test', 'phone' => '0701020304', 'password' => 'x', 'password_confirmation' => 'x'])->assertTooManyRequests();
    }

    public function test_pages_made_for_a_signed_in_person_are_never_cached(): void
    {
        $this->assertStringNotContainsString('no-store', (string) $this->get('/')->headers->get('Cache-Control'));

        $this->actingAs(User::factory()->customer()->create());

        $this->assertStringContainsString('no-store', (string) $this->get('/compte')->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $this->get('/')->headers->get('Cache-Control'));
    }

    public function test_an_unknown_account_never_matches_a_password(): void
    {
        $this->assertFalse(User::passwordMatches(null, 'password'));
        $this->assertTrue(User::passwordMatches(User::factory()->create(), 'password'));
    }
}
