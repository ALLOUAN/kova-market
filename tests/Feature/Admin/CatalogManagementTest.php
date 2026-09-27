<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\StockManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
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
        $this->assertSame([250000, 8], [$product->defaultVariant->price, $product->defaultVariant->stock]);
        $this->assertTrue($product->stockMovements()->sole()->user->is($this->manager));
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

    public function test_saving_a_product_leaves_its_prices_and_stock_to_the_variants(): void
    {
        $product = Product::factory()->create(['price' => 108000, 'stock' => 7]);
        Storage::disk('storefront')->put($product->image, 'image');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['price' => 1, 'stock' => 999])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame(108000, $product->price);
        $this->assertSame(7, $product->stock);
    }

    public function test_changes_are_recorded_in_the_audit_log_with_their_author(): void
    {
        $product = Product::factory()->create(['price' => 108000]);
        Storage::disk('storefront')->put($product->image, 'image');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['name' => 'Téléviseur 65 pouces'])
            ->call('save')
            ->assertHasNoFormErrors();

        $activity = Activity::where('subject_type', $product->getMorphClass())->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertTrue($activity->causer->is($this->manager));
        $this->assertSame('Téléviseur 65 pouces', $activity->attribute_changes['attributes']['name']);
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

    public function test_a_manager_adds_a_variant_with_its_characteristics_and_opening_stock(): void
    {
        $product = Product::factory()->create(['price' => 400000, 'stock' => 2]);
        [$black, $capacity] = $this->attributeValues();

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make(CreateAction::getDefaultName())->table(), [
                'attributeValues' => [$black->id, $capacity->id],
                'sku' => 'IPHONE-NOIR-256',
                'price' => 520000,
                'opening_stock' => 5,
            ])
            ->assertHasNoActionErrors();

        $variant = ProductVariant::where('sku', 'IPHONE-NOIR-256')->firstOrFail();
        $this->assertSame('Couleur : Noir, Capacité : 256 Go', $variant->label());
        $this->assertSame(5, $variant->stock);
        $this->assertSame([7, 520000], [$product->fresh()->stock, $product->fresh()->price_max]);
    }

    public function test_the_same_combination_or_two_values_of_one_attribute_are_refused(): void
    {
        $product = Product::factory()->create();
        [$black, $capacity, $white] = $this->attributeValues();
        app(StockManager::class)->createVariant($product, ['sku' => 'EXISTANTE', 'price' => 1000])->attributeValues()->attach([$black->id, $capacity->id]);
        foreach ([[$capacity->id, $black->id], [$black->id, $white->id]] as $values) {
            Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
                ->callAction(TestAction::make(CreateAction::getDefaultName())->table(), ['attributeValues' => $values, 'sku' => 'DOUBLON', 'price' => 1000, 'opening_stock' => 0])
                ->assertHasActionErrors(['attributeValues']);
        }

        $this->assertDatabaseMissing('product_variants', ['sku' => 'DOUBLON']);
    }

    public function test_adjusting_the_stock_records_a_movement_and_the_default_variant_cannot_be_deleted(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $variant = $product->defaultVariant;

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make('adjustStock')->table($variant), ['counted' => 6, 'reason' => 'ajustement', 'note' => 'Inventaire'])
            ->assertActionHidden(TestAction::make(DeleteAction::getDefaultName())->table($variant));

        $movement = $variant->stockMovements()->first();
        $this->assertSame([-4, 6, 'Inventaire'], [$movement->quantity, $movement->stock_after, $movement->note]);
        $this->assertTrue($movement->user->is($this->manager));
        $this->assertSame(6, $product->fresh()->stock);
    }

    /**
     * @return array{0: AttributeValue, 1: AttributeValue, 2: AttributeValue}
     */
    private function attributeValues(): array
    {
        $color = ProductAttribute::create(['name' => 'Couleur', 'slug' => 'couleur', 'position' => 0]);
        $storage = ProductAttribute::create(['name' => 'Capacité', 'slug' => 'capacite', 'position' => 1]);

        return [
            $color->values()->create(['value' => 'Noir']),
            $storage->values()->create(['value' => '256 Go']),
            $color->values()->create(['value' => 'Blanc']),
        ];
    }
}
