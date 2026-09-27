<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_signs_up_with_a_phone_number_and_no_email(): void
    {
        $this->post('/register', [
            'name' => 'Aya Kouassi',
            'phone' => '07 01 02 03 04',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
        ])->assertRedirect('/');

        $user = User::firstOrFail();
        $this->assertSame('+2250701020304', $user->phone);
        $this->assertNull($user->email);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_phone_number_can_only_be_used_once_however_it_is_typed(): void
    {
        User::factory()->create(['phone' => '+2250701020304']);

        $this->post('/register', [
            'name' => 'Autre client',
            'phone' => '07.01.02.03.04',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
        ])->assertSessionHasErrors(['phone' => 'Un compte existe déjà avec ce numéro de téléphone.']);

        $this->assertSame(1, User::count());
    }

    public function test_sign_up_requires_a_valid_ivorian_number_and_an_eight_character_password(): void
    {
        $this->post('/register', [
            'name' => 'Client',
            'phone' => '01020304',
            'password' => 'court',
            'password_confirmation' => 'court',
        ])->assertSessionHasErrors(['phone', 'password']);

        $this->assertGuest();
    }

    public function test_a_customer_logs_in_with_the_phone_number_typed_in_another_format(): void
    {
        $user = User::factory()->customer()->create(['phone' => '0501020304']);

        $this->post('/login', ['login' => '+225 05 01 02 03 04', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_accounts_with_an_email_still_log_in_with_it(): void
    {
        $user = User::factory()->create(['email' => 'client@kovamarket.ci']);

        $this->post('/login', ['login' => 'Client@KovaMarket.ci', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_or_unknown_login_is_refused(): void
    {
        User::factory()->customer()->create(['phone' => '0501020304']);

        $this->post('/login', ['login' => '0501020304', 'password' => 'mauvais'])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => '0799999999', 'password' => 'password'])->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
