<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The header's "Mon compte" menu: the customer's shortcuts, the back-office for the team only.
 */
class AccountMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_finds_the_shortcuts_of_their_account_in_the_header(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Aya Kouassi']);

        $this->actingAs($customer)->get('/')->assertOk()
            ->assertSeeText('Bonjour, Aya')
            ->assertSee('class="kova-account-menu__panel"', false)
            ->assertSee('href="'.route('account.orders').'"', false)
            ->assertSee('href="'.route('account.tracking').'"', false)
            ->assertSee('href="'.route('account.addresses.index').'"', false)
            ->assertSeeText('Se déconnecter')
            ->assertDontSeeText('Administration');
    }

    public function test_a_team_member_also_finds_the_back_office(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs(User::factory()->staff(Role::Manager)->create())->get('/')->assertOk()->assertSeeText('Administration');
    }

    public function test_visitors_keep_the_sign_in_link(): void
    {
        $this->get('/')->assertOk()->assertSeeText('Connexion / Inscription')->assertDontSee('class="kova-account-menu__panel"', false);
    }
}
