<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Auth\AppAuthentication;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The QR code of the authenticator-app set-up must be an image the browser can show: one "data:" URI holding the SVG,
 * not a URI wrapped in another one (blank image without the imagick extension).
 */
class TwoFactorQrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_qr_code_is_a_single_svg_data_uri(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $uri = AppAuthentication::make()->generateQrCodeDataUri('JBSWY3DPEHPK3PXP');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        $svg = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')), true);
        $this->assertStringContainsString('<svg', (string) $svg);
    }
}
