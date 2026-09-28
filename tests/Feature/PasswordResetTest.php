<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\Account\PasswordRecovery;
use App\Services\Sms\SmsGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{phone: string, message: string}> */
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();

        $sent = &$this->sms;
        $this->app->instance(SmsGateway::class, new class($sent) implements SmsGateway
        {
            /** @param list<array{phone: string, message: string}> $sent */
            public function __construct(private array &$sent) {}

            public function send(string $phone, string $message): void
            {
                $this->sent[] = ['phone' => $phone, 'message' => $message];
            }
        });
    }

    public function test_the_sign_in_form_offers_the_forgotten_password_page(): void
    {
        $this->get('/')->assertOk()->assertSee(route('password.request'), false);
        $this->get('/mot-de-passe-oublie')->assertOk()->assertSeeText('Téléphone ou e-mail')->assertSee('noindex, nofollow', false);
    }

    public function test_a_customer_changes_the_password_with_the_code_received_by_sms(): void
    {
        $user = User::factory()->customer()->create(['phone' => '0701020304']);
        $token = $user->createToken('iPhone');

        $this->post('/mot-de-passe-oublie', ['login' => '07 01 02 03 04'])->assertRedirect(route('password.code'));
        $this->get(route('password.code'))->assertOk()->assertSeeText('07 01 02 03 04');

        $this->assertCount(1, $this->sms);
        $this->assertSame('+2250701020304', $this->sms[0]['phone']);

        $this->post(route('password.code.update'), ['code' => $this->code(), 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])
            ->assertRedirect(route('home', ['connexion' => 1]));

        $this->assertTrue(Hash::check('nouveau-pass', $user->fresh()->password));
        // Whoever knew the old password is signed out everywhere.
        $this->assertModelMissing($token->accessToken);
    }

    public function test_the_sms_code_is_single_use(): void
    {
        User::factory()->customer()->create(['phone' => '0701020304']);
        $this->post('/mot-de-passe-oublie', ['login' => '0701020304']);
        $code = $this->code();

        $this->post(route('password.code.update'), ['code' => $code, 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])->assertSessionHasNoErrors();

        $this->assertFalse(app(PasswordRecovery::class)->resetWithCode('0701020304', $code, ['password' => 'autre-pass1', 'password_confirmation' => 'autre-pass1']));
    }

    public function test_a_code_stops_working_after_five_wrong_tries_or_fifteen_minutes(): void
    {
        $user = User::factory()->customer()->create(['phone' => '0701020304']);
        $recovery = app(PasswordRecovery::class);
        $passwords = ['password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'];

        $recovery->request('0701020304');
        $code = $this->code();
        $wrong = $code === '111111' ? '222222' : '111111';

        for ($i = 0; $i < PasswordRecovery::MAX_ATTEMPTS; $i++) {
            $this->assertFalse($recovery->resetWithCode('0701020304', $wrong, $passwords));
        }
        $this->assertFalse($recovery->resetWithCode('0701020304', $code, $passwords));

        $this->travel(PasswordRecovery::RESEND_SECONDS + 1)->seconds();
        $recovery->request('0701020304');
        $this->travel(PasswordRecovery::CODE_MINUTES + 1)->minutes();
        $this->assertFalse($recovery->resetWithCode('0701020304', $this->code(), $passwords));

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_a_wrong_code_is_refused_with_a_message(): void
    {
        User::factory()->customer()->create(['phone' => '0701020304']);
        $this->post('/mot-de-passe-oublie', ['login' => '0701020304']);

        $this->post(route('password.code.update'), ['code' => '000000', 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])
            ->assertSessionHasErrors(['code' => 'Ce code n’est pas valide ou a expiré. Vérifiez le SMS reçu, ou demandez un nouveau code.']);
    }

    public function test_codes_are_not_sent_more_than_once_a_minute(): void
    {
        User::factory()->customer()->create(['phone' => '0701020304']);

        app(PasswordRecovery::class)->request('0701020304');
        app(PasswordRecovery::class)->request('0701020304');

        $this->assertCount(1, $this->sms);
    }

    public function test_the_answer_never_reveals_whether_an_account_exists(): void
    {
        $this->post('/mot-de-passe-oublie', ['login' => '0501020304'])->assertRedirect(route('password.code'));
        $this->post('/mot-de-passe-oublie', ['login' => 'personne@exemple.ci'])->assertSessionHas('status');

        $this->assertSame([], $this->sms);
    }

    public function test_staff_and_couriers_cannot_reset_from_the_storefront(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
        $manager = User::factory()->staff(Role::Manager)->create(['phone' => '0701020304']);

        $this->post('/mot-de-passe-oublie', ['login' => '0701020304']);
        $this->post('/mot-de-passe-oublie', ['login' => $manager->email]);

        $this->assertSame([], $this->sms);
        Notification::assertNothingSent();
    }

    public function test_an_e_mail_link_works_once(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'awa@exemple.ci']);

        $this->post('/mot-de-passe-oublie', ['login' => 'awa@exemple.ci'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token, $user) {
            $token = $notification->token;

            return str_contains($notification->toMail($user)->actionUrl, '/reinitialiser-mot-de-passe/');
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => 'awa@exemple.ci']))->assertOk()->assertSee('value="awa@exemple.ci"', false);

        $reset = ['token' => $token, 'email' => 'awa@exemple.ci', 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'];
        $this->post(route('password.reset.update'), $reset)->assertSessionHasNoErrors()->assertRedirect(route('home', ['connexion' => 1]));
        $this->assertTrue(Hash::check('nouveau-pass', $user->fresh()->password));

        $this->post(route('password.reset.update'), [...$reset, 'password' => 'encore-autre', 'password_confirmation' => 'encore-autre'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('nouveau-pass', $user->fresh()->password));
    }

    public function test_the_mobile_app_resets_with_the_sms_code(): void
    {
        $user = User::factory()->customer()->create(['phone' => '0701020304']);

        $this->postJson('/api/v1/auth/password/forgot', ['login' => '0701020304'])->assertStatus(202);
        $this->postJson('/api/v1/auth/password/reset', ['phone' => '0701020304', 'code' => '000000', 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/api/v1/auth/password/reset', ['phone' => '07 01 02 03 04', 'code' => $this->code(), 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])
            ->assertNoContent();

        $this->assertTrue(Hash::check('nouveau-pass', $user->fresh()->password));
    }

    /**
     * The code of the last SMS sent.
     */
    private function code(): string
    {
        preg_match('/\b(\d{6})\b/', end($this->sms)['message'], $matches);

        return $matches[1];
    }
}
