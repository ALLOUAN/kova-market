<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\Testing\TestAction;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesAndPermissionsSeeder::class, ContentSeeder::class]);
        $this->superAdmin = User::factory()->staff(Role::SuperAdmin)->create();
    }

    public function test_every_administration_screen_renders_for_a_super_admin(): void
    {
        $this->actingAs($this->superAdmin);

        foreach ([Dashboard::getUrl(), Settings::getUrl(), UserResource::getUrl('index'), ActivityResource::getUrl('index'), BannerResource::getUrl('index'), BannerResource::getUrl('create'), PageResource::getUrl('index'), FaqResource::getUrl('index')] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_managers_handle_content_but_not_staff_settings_or_the_audit_log(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $this->get(BannerResource::getUrl('index'))->assertOk();
        $this->get(PageResource::getUrl('index'))->assertOk();
        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(Settings::getUrl())->assertForbidden();
        $this->get(ActivityResource::getUrl('index'))->assertForbidden();
    }

    public function test_the_super_admin_creates_a_manager_account(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Awa Koné',
                'email' => 'awa@kovamarket.ci',
                'roles' => [RoleModel::findByName(Role::Manager->value)->getKey()],
                'password' => 'un-mot-de-passe-solide',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(User::where('email', 'awa@kovamarket.ci')->firstOrFail()->hasRole(Role::Manager->value));
    }

    public function test_staff_phone_numbers_are_stored_normalized_and_unique(): void
    {
        $this->actingAs($this->superAdmin);
        User::factory()->customer()->create(['phone' => '0701020304']);
        $manager = RoleModel::findByName(Role::Manager->value)->getKey();

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Doublon', 'email' => 'doublon@kovamarket.ci', 'phone' => '+225 07 01 02 03 04', 'roles' => [$manager], 'password' => 'un-mot-de-passe-solide'])
            ->call('create')
            ->assertHasFormErrors(['phone']);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Koffi', 'email' => 'koffi@kovamarket.ci', 'phone' => '05 06 07 08 09', 'roles' => [$manager], 'password' => 'un-mot-de-passe-solide'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('+2250506070809', User::where('email', 'koffi@kovamarket.ci')->value('phone'));
    }

    public function test_the_super_admin_cannot_delete_their_own_account(): void
    {
        $this->actingAs($this->superAdmin);
        $colleague = User::factory()->staff(Role::Manager)->create();

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make(DeleteAction::getDefaultName())->table($this->superAdmin))
            ->assertActionVisible(TestAction::make(DeleteAction::getDefaultName())->table($colleague));
    }

    public function test_a_removed_account_is_kept_cannot_sign_in_and_can_be_restored(): void
    {
        $this->actingAs($this->superAdmin);
        $colleague = User::factory()->staff(Role::Manager)->create(['email' => 'awa@kova.test']);

        Livewire::test(ListUsers::class)->callAction(TestAction::make(DeleteAction::getDefaultName())->table($colleague));

        $this->assertSoftDeleted($colleague);
        $this->assertNull(User::findByLogin('awa@kova.test'));

        Livewire::test(ListUsers::class)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$colleague])
            ->callAction(TestAction::make(RestoreAction::getDefaultName())->table($colleague));

        $this->assertNotSoftDeleted($colleague);
    }

    public function test_resetting_two_factor_forces_a_new_set_up(): void
    {
        $this->actingAs($this->superAdmin);
        $manager = User::factory()->staff(Role::Manager)->create();

        Livewire::test(ListUsers::class)->callAction(TestAction::make('resetTwoFactor')->table($manager));

        $this->assertNull($manager->refresh()->app_authentication_secret);
    }

    public function test_settings_are_saved_and_shown_on_the_storefront(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(Settings::class)
            ->fillForm(['contact.phone' => '+225 05 06 07 08 09', 'contact.whatsapp' => '2250506070809'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('2250506070809', Setting::get('contact.whatsapp'));
        $this->get('/')->assertSeeText('+225 05 06 07 08 09');
    }

    public function test_an_invalid_whatsapp_number_is_refused(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(Settings::class)
            ->fillForm(['contact.whatsapp' => '+225 07 xx'])
            ->call('save')
            ->assertHasFormErrors(['contact.whatsapp']);
    }
}
