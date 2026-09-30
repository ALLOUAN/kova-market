<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Enums\SaleUnit;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ListParentCategories;
use App\Filament\Resources\Categories\Pages\ListSubCategories;
use App\Filament\Resources\Categories\RelationManagers\ChildrenRelationManager;
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

    public function test_a_product_sold_by_the_kilo_is_set_in_kilos_and_stored_in_grams(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Tomates fraîches',
                'slug' => 'tomates-fraiches',
                'category_id' => Category::factory()->create()->id,
                'sale_unit' => SaleUnit::Kilogram->value,
                'min_quantity' => 0.5,
                'quantity_step' => 0.25,
                'price' => 1000,
                'stock' => 50,
                'image' => UploadedFile::fake()->image('tomates.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tomatoes = Product::where('slug', 'tomates-fraiches')->firstOrFail();
        $this->assertSame([SaleUnit::Kilogram, 500, 250, null], [$tomatoes->sale_unit, $tomatoes->min_quantity, $tomatoes->quantity_step, $tomatoes->max_quantity]);
        $this->assertSame([1000, 50_000], [$tomatoes->defaultVariant->price, $tomatoes->defaultVariant->stock]);

        // The stock is counted in kilos in the back-office too.
        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $tomatoes, 'pageClass' => EditProduct::class])
            ->assertSee('50 kg')
            ->callAction(TestAction::make('adjustStock')->table($tomatoes->defaultVariant), ['counted' => 12.5, 'reason' => 'ajustement']);
        $this->assertSame(12_500, $tomatoes->defaultVariant->fresh()->stock);

        // A local unit needs its name.
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Gombo', 'slug' => 'gombo', 'category_id' => $tomatoes->category_id, 'sale_unit' => SaleUnit::Local->value, 'price' => 500, 'stock' => 10])
            ->call('create')
            ->assertHasFormErrors(['unit_label' => 'required']);
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

    public function test_only_real_images_are_accepted_as_product_pictures(): void
    {
        $category = Category::factory()->create();
        $form = ['name' => 'Casque', 'slug' => 'casque', 'category_id' => $category->id, 'price' => 20000, 'stock' => 3];

        // Uploads are typed from their content, not their name: a script renamed "photo.jpg" is seen as PHP...
        Storage::disk('local')->put('probe/photo.jpg', '<?php echo "pwned";');
        $this->assertSame('text/x-php', Storage::disk('local')->mimeType('probe/photo.jpg'));
        Storage::disk('local')->deleteDirectory('probe');

        // ...and refused like a PDF (Livewire tests take the type given here instead of reading the content).
        $disguisedScript = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "pwned";')->mimeType('text/x-php');

        foreach ([$disguisedScript, UploadedFile::fake()->create('notice.pdf', 100, 'application/pdf')] as $file) {
            Livewire::test(CreateProduct::class)
                ->fillForm([...$form, 'image' => $file])
                ->call('create')
                ->assertHasFormErrors(['image']);
        }

        $this->assertSame(0, Product::count());
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

    public function test_sub_categories_are_managed_from_their_category(): void
    {
        $audio = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio', 'parent_id' => null]);
        $headphones = Category::factory()->create(['name' => 'Casques', 'slug' => 'casques', 'parent_id' => $audio->id, 'position' => 1]);

        // Added from the category page: attached to it, at the end of the list.
        Livewire::test(ChildrenRelationManager::class, ['ownerRecord' => $audio, 'pageClass' => EditCategory::class])
            ->assertCanSeeTableRecords([$headphones])
            ->callAction(TestAction::make(CreateAction::getDefaultName())->table(), ['name' => 'Enceintes', 'slug' => 'enceintes'])
            ->assertHasNoFormErrors();

        $speakers = Category::where('slug', 'enceintes')->firstOrFail();
        $this->assertSame([$audio->id, 2], [$speakers->parent_id, $speakers->position]);

        // Dragged first: the storefront follows.
        Livewire::test(ChildrenRelationManager::class, ['ownerRecord' => $audio, 'pageClass' => EditCategory::class])
            ->call('reorderTable', [(string) $speakers->id, (string) $headphones->id]);
        $this->assertSame(['Enceintes', 'Casques'], $audio->fresh()->children->pluck('name')->all());
        $this->get('/categorie/audio')->assertSeeInOrder(['Enceintes', 'Casques']);

        // A sub-category holding products stays; an empty one can go.
        Product::factory()->for($headphones)->create();
        Livewire::test(ChildrenRelationManager::class, ['ownerRecord' => $audio, 'pageClass' => EditCategory::class])
            ->assertActionHidden(TestAction::make(DeleteAction::getDefaultName())->table($headphones))
            ->callAction(TestAction::make(DeleteAction::getDefaultName())->table($speakers));
        $this->assertModelMissing($speakers);
    }

    public function test_the_third_level_is_the_last_one(): void
    {
        $root = Category::factory()->create(['parent_id' => null]);
        $second = Category::factory()->create(['parent_id' => $root->id]);
        $third = Category::factory()->create(['parent_id' => $second->id]);

        $this->assertTrue(ChildrenRelationManager::canViewForRecord($root, EditCategory::class));
        $this->assertTrue(ChildrenRelationManager::canViewForRecord($second, EditCategory::class));
        $this->assertFalse(ChildrenRelationManager::canViewForRecord($third, EditCategory::class));

        Livewire::test(ListCategories::class)
            ->assertActionVisible(TestAction::make('addChild')->table($second))
            ->assertActionHidden(TestAction::make('addChild')->table($third));

        // "+ Sous-catégorie" opens the form with the parent chosen.
        $this->get(CategoryResource::getUrl('create', ['parent' => $second->id]))->assertOk();
        Livewire::withQueryParams(['parent' => $second->id])->test(CreateCategory::class)->assertSchemaStateSet(['parent_id' => $second->id]);
    }

    public function test_the_sidebar_leads_to_every_sub_category_and_adds_one(): void
    {
        $audio = Category::factory()->create(['name' => 'Audio', 'parent_id' => null]);
        $headphones = Category::factory()->create(['name' => 'Casques', 'parent_id' => $audio->id]);
        $url = CategoryResource::getUrl('create', ['type' => CreateCategory::SUB_CATEGORY]);

        // Sidebar "Sous-catégories": every sub-category, grouped by parent, and the button to add one.
        $this->get(CategoryResource::getUrl('index'))->assertSee('href="'.e(CategoryResource::getUrl('sub')).'"', false);
        $this->get(CategoryResource::getUrl('sub'))->assertOk()->assertSeeText('Sous-catégories')->assertSee('href="'.e($url).'"', false);
        Livewire::test(ListSubCategories::class)->assertCanSeeTableRecords([$headphones])->assertCanNotSeeTableRecords([$audio]);

        $this->get($url)->assertOk()->assertSeeText('Ajouter une sous-catégorie');

        // The parent is required, then the page goes back to it.
        Livewire::withQueryParams(['type' => CreateCategory::SUB_CATEGORY])->test(CreateCategory::class)
            ->fillForm(['name' => 'Enceintes', 'slug' => 'enceintes', 'position' => 0])
            ->call('create')
            ->assertHasFormErrors(['parent_id' => 'required']);

        Livewire::withQueryParams(['type' => CreateCategory::SUB_CATEGORY])->test(CreateCategory::class)
            ->fillForm(['name' => 'Enceintes', 'slug' => 'enceintes', 'position' => 0, 'parent_id' => $audio->id])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(CategoryResource::getUrl('sub'));

        $this->assertSame($audio->id, Category::where('slug', 'enceintes')->value('parent_id'));

        // Without the right to change the catalogue: the list, but no button to add.
        $this->actingAs(User::factory()->staff(Role::Picker)->create())->get(CategoryResource::getUrl('sub'))->assertOk()->assertDontSeeText('Ajouter une sous-catégorie');
    }

    public function test_parent_categories_have_their_own_page_in_menu_order(): void
    {
        $phones = Category::factory()->create(['name' => 'Téléphones', 'slug' => 'telephones', 'parent_id' => null, 'position' => 1]);
        $audio = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio', 'parent_id' => null, 'position' => 2]);
        $headphones = Category::factory()->create(['name' => 'Casques', 'parent_id' => $audio->id]);
        $url = CategoryResource::getUrl('create', ['type' => CreateCategory::PARENT_CATEGORY]);

        $this->get(CategoryResource::getUrl('index'))->assertSee('href="'.e(CategoryResource::getUrl('parents')).'"', false);
        $this->get(CategoryResource::getUrl('parents'))->assertOk()->assertSeeText('Catégories parentes')->assertSee('href="'.e($url).'"', false);

        // Only the main departments; dragging one first changes the storefront menus.
        Livewire::test(ListParentCategories::class)
            ->assertCanSeeTableRecords([$phones, $audio], inOrder: true)
            ->assertCanNotSeeTableRecords([$headphones])
            ->call('reorderTable', [(string) $audio->id, (string) $phones->id]);
        $this->assertSame(['Audio', 'Téléphones'], Category::roots()->whereKey([$phones->id, $audio->id])->pluck('name')->all());

        // Added without a parent to choose, then back to the list.
        $this->get($url)->assertOk()->assertSeeText('Ajouter une catégorie parente')->assertDontSeeText('Catégorie parente*');
        Livewire::withQueryParams(['type' => CreateCategory::PARENT_CATEGORY])->test(CreateCategory::class)
            ->assertFormFieldHidden('parent_id')
            ->fillForm(['name' => 'Informatique', 'slug' => 'informatique', 'position' => 3])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(CategoryResource::getUrl('parents'));
        $this->assertNull(Category::where('slug', 'informatique')->value('parent_id'));
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

    public function test_reordering_a_collection_changes_the_order_on_the_home_page(): void
    {
        $collection = Collection::create(['name' => 'Offres du jour', 'slug' => 'deals-of-the-day']);
        $first = Product::factory()->create(['name' => 'First Deal']);
        $second = Product::factory()->create(['name' => 'Second Deal']);
        $collection->products()->attach([$first->id => ['position' => 1], $second->id => ['position' => 2]]);

        Livewire::test(ProductsRelationManager::class, ['ownerRecord' => $collection, 'pageClass' => EditCollection::class])
            ->call('reorderTable', [(string) $second->id, (string) $first->id]);

        $this->get('/')->assertOk()->assertSeeTextInOrder(['Second Deal', 'First Deal']);
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
