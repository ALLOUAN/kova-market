<?php

namespace App\Filament\Auth;

use Filament\Auth\MultiFactor\App\AppAuthentication as BaseAppAuthentication;

/**
 * Authenticator-app two-factor provider. On a local machine with ADMIN_LOCAL_TWO_FACTOR_SECRET set, every account
 * gets that test secret, so the current 6-digit code can be displayed next to the code fields (see
 * AdminPanelProvider). Anywhere else, or without it, each account gets its own random secret.
 */
class AppAuthentication extends BaseAppAuthentication
{
    public function generateSecret(): string
    {
        return self::localTestSecret() ?? parent::generateSecret();
    }

    public function getCurrentTestCode(): ?string
    {
        $secret = self::localTestSecret();

        return $secret === null ? null : $this->google2FA->getCurrentOtp($secret);
    }

    public static function localTestSecret(): ?string
    {
        $secret = config('admin.local_two_factor_secret');

        return app()->isLocal() && filled($secret) ? (string) $secret : null;
    }
}
