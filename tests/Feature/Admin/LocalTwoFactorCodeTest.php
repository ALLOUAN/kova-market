<?php

namespace Tests\Feature\Admin;

use App\Filament\Auth\AppAuthentication;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class LocalTwoFactorCodeTest extends TestCase
{
    public function test_local_machines_use_the_known_test_secret_and_its_current_code(): void
    {
        $this->app['env'] = 'local';
        $provider = app(AppAuthentication::class);

        $this->assertSame(config('admin.local_two_factor_secret'), $provider->generateSecret());
        $this->assertSame((new Google2FA)->getCurrentOtp(config('admin.local_two_factor_secret')), $provider->getCurrentTestCode());
    }

    public function test_other_environments_keep_random_secrets(): void
    {
        $this->app['env'] = 'production';
        $provider = app(AppAuthentication::class);

        $this->assertNotSame(config('admin.local_two_factor_secret'), $provider->generateSecret());
        $this->assertNotSame($provider->generateSecret(), $provider->generateSecret());
    }
}
