<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Resources\Products\ProductResource;
use App\Models\User;
use App\Notifications\AdminLoginCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Back-office two-factor by e-mail: the 6-digit code goes to the account's address at once, and an account using it
 * meets the two-factor requirement.
 */
class EmailTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sign_in_code_is_e_mailed_at_once_and_valid_ten_minutes(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['email' => 'admin-kovamarket@kova-market.com']);
        $admin->forceFill(['has_email_authentication' => true])->save();
        $provider = Filament::getPanel('admin')->getMultiFactorAuthenticationProviders()['email_code'];

        $this->assertTrue($provider->isEnabled($admin));
        $this->assertTrue($provider->sendCode($admin));

        Notification::assertSentTo($admin, AdminLoginCode::class, function (AdminLoginCode $notification) use ($admin): bool {
            $mail = $notification->toMail($admin);

            return preg_match('/^\d{6}$/', $notification->code) === 1
                && $notification->codeExpiryMinutes === 10
                && str_contains($mail->subject, $notification->code);
        });
        // Not queued: on shared hosting the queue only runs every minute.
        $this->assertNotInstanceOf(ShouldQueue::class, new AdminLoginCode('123456', 10));
    }

    public function test_an_account_using_e_mail_codes_meets_the_two_factor_requirement(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->staff(Role::SuperAdmin)->create();
        $admin->forceFill(['has_email_authentication' => true])->save();

        $this->actingAs($admin)->get(ProductResource::getUrl('index'))->assertOk();
    }
}
