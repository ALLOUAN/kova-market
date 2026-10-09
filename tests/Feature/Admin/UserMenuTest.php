<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Resources\Products\ProductResource;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shortcuts of the back-office user menu: each one shown only to those allowed to open it.
 */
class UserMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_super_admin_finds_every_shortcut_in_the_user_menu(): void
    {
        $admin = User::factory()->staff(Role::SuperAdmin)->create();
        $admin->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

        $this->actingAs($admin)->get(ProductResource::getUrl('index'))->assertOk()
            ->assertSeeText('Voir la boutique')
            ->assertSeeText('Commandes à traiter')
            ->assertSeeText('Ajouter un produit')
            ->assertSeeText('Nouvelle campagne e-mail')
            ->assertSeeText('Paramètres de la boutique')
            ->assertSee('href="'.route('home').'"', false);
    }

    public function test_a_picker_only_sees_the_shortcuts_of_their_work(): void
    {
        $picker = User::factory()->staff(Role::Picker)->create();
        $picker->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

        $this->actingAs($picker)->get(ProductResource::getUrl('index'))->assertOk()
            ->assertSeeText('Voir la boutique')
            ->assertDontSeeText('Nouvelle campagne e-mail')
            ->assertDontSeeText('Paramètres de la boutique');
    }
}
