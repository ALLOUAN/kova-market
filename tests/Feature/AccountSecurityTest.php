<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\AccountSecurityAlert;
use App\Services\Account\AccountEraser;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Account takeover protections (F-147): the password guards the phone and e-mail, and every change of password,
 * e-mail or phone is reported to the former contact details.
 */
class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_changing_the_phone_or_the_email_asks_for_the_password(): void
    {
        $user = User::factory()->customer()->create(['name' => 'Awa Koné', 'phone' => '0701020304', 'email' => 'awa@example.com']);
        $this->actingAs($user);

        $this->put(route('user-profile-information.update'), ['name' => 'Awa Koné', 'phone' => '0505050505', 'email' => 'awa@example.com'])
            ->assertSessionHasErrorsIn('updateProfileInformation', ['current_password']);
        $this->put(route('user-profile-information.update'), ['name' => 'Awa Koné', 'phone' => '0701020304', 'email' => 'pirate@example.com', 'current_password' => 'mauvais'])
            ->assertSessionHasErrorsIn('updateProfileInformation', ['current_password']);
        $this->assertSame(['+2250701020304', 'awa@example.com'], [$user->fresh()->phone, $user->fresh()->email]);
        Notification::assertNothingSent();

        // The name alone, or the phone typed another way, needs no password.
        $this->put(route('user-profile-information.update'), ['name' => 'Awa K.', 'phone' => '07 01 02 03 04', 'email' => 'awa@example.com'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Awa K.', $user->fresh()->name);
        Notification::assertNothingSent();

        $this->put(route('user-profile-information.update'), ['name' => 'Awa K.', 'phone' => '0505050505', 'email' => 'awa@example.com', 'current_password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertSame('+2250505050505', $user->fresh()->phone);

        // The alert goes to the FORMER number.
        Notification::assertSentOnDemand(AccountSecurityAlert::class, fn (AccountSecurityAlert $alert, array $channels, AnonymousNotifiable $notifiable) => $alert->changes === ['phone']
            && $notifiable->routes['whatsapp'] === '+2250701020304'
            && $notifiable->routes['mail'] === 'awa@example.com');
    }

    public function test_a_new_password_is_reported_by_whatsapp_and_email(): void
    {
        $user = User::factory()->customer()->create(['name' => 'Awa Koné', 'phone' => '0701020304', 'email' => 'awa@example.com']);

        $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'password', 'password' => 'NouveauMotDePasse1!', 'password_confirmation' => 'NouveauMotDePasse1!',
        ])->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(AccountSecurityAlert::class, function (AccountSecurityAlert $alert, array $channels, AnonymousNotifiable $notifiable) {
            $message = $alert->toWhatsApp($notifiable);

            return $alert->summary() === 'nouveau mot de passe'
                && $message->name() === 'kova_securite_compte'
                && $message->parameters[0] === 'Awa Koné'
                && str_contains($alert->toMail($notifiable)->render(), 'nouveau mot de passe')
                && in_array('mail', $channels, true);
        });
    }

    public function test_a_courier_gives_the_current_password_except_for_the_first_change(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $courier = User::factory()->create(['phone' => '0707070707']);
        $courier->assignRole(Role::Courier->value);
        $courier->courier()->create(['transport' => 'moto']);

        // A temporary password set by the store is sent with the credentials, not as an alert.
        $courier->forceFill(['password' => 'Provisoire1', 'must_change_password' => true])->save();
        Notification::assertNothingSent();

        $this->actingAs($courier)->put(route('courier.password.update'), ['password' => 'MonMotDePasse1', 'password_confirmation' => 'MonMotDePasse1'])
            ->assertRedirect(route('courier.home'));
        Notification::assertSentOnDemandTimes(AccountSecurityAlert::class, 1);

        $this->put(route('courier.password.update'), ['password' => 'Autre1234', 'password_confirmation' => 'Autre1234'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('courier.password.update'), ['current_password' => 'MonMotDePasse1', 'password' => 'Autre1234', 'password_confirmation' => 'Autre1234'])
            ->assertRedirect(route('courier.home'));
        Notification::assertSentOnDemandTimes(AccountSecurityAlert::class, 2);
    }

    public function test_an_erased_account_gets_no_alert(): void
    {
        $user = User::factory()->customer()->create(['email' => 'awa@example.com']);

        app(AccountEraser::class)->erase($user);

        Notification::assertNothingSent();
    }
}
