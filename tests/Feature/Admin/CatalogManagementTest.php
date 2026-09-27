<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\AttachAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create();
        $this->actingAs($this->manager);
        Storage::fake('storefront');
    }

    public function test_a_manager_creates_a_product_priced_in_whole_fcfa(): void
    {
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Téléviseur 55 pouces',
                'slug' => 'televiseur-55-pouces',
                'category_id' => $category->id,
                'price' => 250000,
                'compare_at_price' => 300000,
                'stock' => 8,
                'image' => UploadedFile::fake()->image('produit.jpg'),
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'televiseur-55-pouces')->firstOrFail();
        $this->assertSame(250000, (int) $product->price);
        $this->assertSame(17, $product->discountPercentage());
    }

    public function test_prices_must_be_whole_amounts_and_the_compare_price_above_the_price(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Casque',
                'slug' => 'casque',
                'category_id' => Category::factory()->create()->id,
                'price' => 15000.5,
                'compare_at_price' => 10000,
                'stock' => 1,
                'image' => UploadedFile::fake()->image('produit.jpg'),
            ])
            ->call('create')
            ->assertHasFormErrors(['price', 'compare_at_price']);
    }

    public function test_an_existing_product_is_saved_unchanged_with_its_whole_fcfa_price(): void
    {
        $product = Product::factory()->create(['price' => 108000]);
        Storage::disk('storefront')->put($product->image, 'image');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSchemaStateSet(['price' => 108000])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_changes_are_recorded_in_the_audit_log_with_their_author(): void
    {
        $product = Product::factory()->create(['price' => 108000]);
        Storage::disk('storefront')->put($product->image, 'image');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['price' => 99000])
            ->call('save')
            ->assertHasNoFormErrors();

        $activity = Activity::where('subject_type', $product->getMorphClass())->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertTrue($activity->causer->is($this->manager));
        $this->assertEquals(99000, $activity->attribute_changes['attributes']['price']);
    }

    public function test_an_empty_menu_promo_is_saved_as_no_promo(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Audio', 'slug' => 'audio', 'position' => 0])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(Category::where('slug', 'audio')->value('promo'));
    }

    public function test_a_category_holding_products_cannot_be_deleted(): void
    {
        $withProducts = Category::factory()->create();
        Product::factory()->for($withProducts)->create();
        $empty = Category::factory()->create();

        Livewire::test(ListCategories::class)
            ->assertActionHidden(TestAction::make(DeleteAction::getDefaultName())->table($withProducts))
            ->assertActionVisible(TestAction::make(DeleteAction::getDefaultName())->table($empty));
    }

    public function test_products_added_to_a_collection_go_to_the_end_of_the_list(): void
    {
        $collection = Collection::create(['name' => 'Offres du jour', 'slug' => 'deals-of-the-day']);
        $collection->products()->attach(Product::factory()->create(), ['position' => 1]);
        $added = Product::factory()->create();

        Livewire::test(ProductsRelationManager::class, ['ownerRecord' => $collection, 'pageClass' => EditCollection::class])
            ->callAction(TestAction::make(AttachAction::getDefaultName())->table(), ['recordId' => $added->getKey()]);

        $this->assertSame(2, $collection->products()->whereKey($added->getKey())->first()->pivot->position);
    }
}
