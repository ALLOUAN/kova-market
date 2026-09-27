<?php

namespace App\Filament\Auth;

use Filament\Auth\MultiFactor\App\AppAuthentication as BaseAppAuthentication;

/**
 * Authenticator-app two-factor provider. On a local machine every account gets the same known test secret,
 * so the current 6-digit code can be displayed next to the code fields (see AdminPanelProvider).
 */
class AppAuthentication extends BaseAppAuthentication
{
    public function generateSecret(): string
    {
        return app()->isLocal() ? config('admin.local_two_factor_secret') : parent::generateSecret();
    }

    public function getCurrentTestCode(): string
    {
        return $this->google2FA->getCurrentOtp(config('admin.local_two_factor_secret'));
    }
}
