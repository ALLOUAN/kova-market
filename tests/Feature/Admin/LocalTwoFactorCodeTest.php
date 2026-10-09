<?php

namespace Tests\Feature\Admin;

use App\Filament\Auth\AppAuthentication;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class LocalTwoFactorCodeTest extends TestCase
{
    private const TEST_SECRET = 'JBSWY3DPEHPK3PXP';

    public function test_local_machines_use_the_test_secret_of_their_env_file_and_its_current_code(): void
    {
        $this->app['env'] = 'local';
        config(['admin.local_two_factor_secret' => self::TEST_SECRET]);
        $provider = app(AppAuthentication::class);

        $this->assertSame(self::TEST_SECRET, $provider->generateSecret());
        $this->assertSame((new Google2FA)->getCurrentOtp(self::TEST_SECRET), $provider->getCurrentTestCode());
    }

    public function test_without_a_test_secret_local_machines_keep_random_secrets(): void
    {
        $this->app['env'] = 'local';
        config(['admin.local_two_factor_secret' => null]);
        $provider = app(AppAuthentication::class);

        $this->assertNotSame($provider->generateSecret(), $provider->generateSecret());
        $this->assertNull($provider->getCurrentTestCode());
    }

    public function test_other_environments_keep_random_secrets_even_with_a_test_secret(): void
    {
        $this->app['env'] = 'production';
        config(['admin.local_two_factor_secret' => self::TEST_SECRET]);
        $provider = app(AppAuthentication::class);

        $this->assertNotSame(self::TEST_SECRET, $provider->generateSecret());
        $this->assertNotSame($provider->generateSecret(), $provider->generateSecret());
        $this->assertNull($provider->getCurrentTestCode());
    }
}
