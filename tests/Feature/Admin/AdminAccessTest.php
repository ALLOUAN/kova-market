<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Collections\CollectionResource;
use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(ProductResource::getUrl('index'))->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_customers_and_couriers_cannot_enter_the_back_office(): void
    {
        $this->actingAs(User::factory()->create())->get(ProductResource::getUrl('index'))->assertForbidden();
        $this->actingAs(User::factory()->staff(Role::Courier)->create())->get(ProductResource::getUrl('index'))->assertForbidden();
    }

    public function test_managers_must_set_up_two_factor_authentication_first(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(Role::Manager->value);

        $this->actingAs($manager)->get(ProductResource::getUrl('index'))->assertRedirectContains('multi-factor-authentication');
    }

    public function test_pickers_must_set_up_two_factor_authentication_too(): void
    {
        $picker = User::factory()->create();
        $picker->assignRole(Role::Picker->value);

        $this->actingAs($picker)->get(ProductResource::getUrl('index'))->assertRedirectContains('multi-factor-authentication');
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function catalogResources(): array
    {
        return [
            'produits' => [ProductResource::class],
            'catégories' => [CategoryResource::class],
            'marques' => [BrandResource::class],
            'collections' => [CollectionResource::class],
            'attributs' => [ProductAttributeResource::class],
        ];
    }

    #[DataProvider('catalogResources')]
    public function test_managers_can_browse_and_edit_the_catalog(string $resource): void
    {
        $this->seed(CatalogSeeder::class);
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager)->get($resource::getUrl('index'))->assertOk();
        $this->actingAs($manager)->get($resource::getUrl('edit', ['record' => $resource::getModel()::first()]))->assertOk();
    }

    public function test_pickers_can_read_the_catalog_but_not_change_it_nor_see_promotions(): void
    {
        $this->seed(CatalogSeeder::class);
        $picker = User::factory()->staff(Role::Picker)->create();

        $this->actingAs($picker)->get(ProductResource::getUrl('index'))->assertOk();
        $this->actingAs($picker)->get(ProductResource::getUrl('create'))->assertForbidden();
        $this->actingAs($picker)->get(ProductResource::getUrl('edit', ['record' => 1]))->assertForbidden();
        $this->actingAs($picker)->get(PromotionResource::getUrl('index'))->assertForbidden();
    }

    public function test_collections_cannot_be_created_from_the_back_office(): void
    {
        $this->assertFalse(CollectionResource::hasPage('create'));
    }
}
