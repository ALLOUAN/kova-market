<?php

namespace App\Filament\Auth;

use Filament\Auth\MultiFactor\App\AppAuthentication as BaseAppAuthentication;
use SensitiveParameter;

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

    /**
     * The QR code to scan. The QR library already returns a "data:" URI, and Filament wraps it in another one when
     * the imagick extension is missing (shared hosting, most local machines): the image then stays blank. The inner
     * URI is kept in that case.
     */
    public function generateQrCodeDataUri(#[SensitiveParameter] string $secret): string
    {
        $uri = parent::generateQrCodeDataUri($secret);
        $prefix = 'data:image/svg+xml;base64,';

        if (str_starts_with($uri, $prefix)) {
            $inner = base64_decode(substr($uri, strlen($prefix)), true);

            if (is_string($inner) && str_starts_with($inner, 'data:')) {
                return $inner;
            }
        }

        return $uri;
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
