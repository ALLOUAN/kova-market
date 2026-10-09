<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Filament\Catalog\Widgets\ImportCatalogOverview;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Brands\Widgets\BrandsOverview;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Widgets\CategoriesOverview;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Filament\Resources\Collections\Widgets\CollectionsOverview;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Widgets\CustomersOverview;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Widgets\OrdersOverview;
use App\Filament\Resources\Payments\Widgets\PaymentsOverview;
use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use App\Filament\Resources\ProductAttributes\Widgets\ProductAttributesOverview;
use App\Filament\Resources\ProductReviews\Widgets\ProductReviewsOverview;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Widgets\ProductsOverview;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Header bands and tabs of the back-office lists (orders, payments, customers).
 */
class ListHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_orders_list_says_what_to_handle_and_its_tabs_filter_the_steps(): void
    {
        $received = $this->order(OrderStatus::Received);
        $unpaidOnline = $this->order(OrderStatus::Received, ['payment_method' => PaymentMethod::Online]);
        $onTheWay = $this->order(OrderStatus::OutForDelivery);
        $cancelled = $this->order(OrderStatus::Cancelled);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $this->get(ListOrders::getUrl())->assertOk()->assertSeeText('Commandes')->assertSeeText('À traiter');
        Livewire::test(OrdersOverview::class)
            ->assertSeeText('4 commandes aujourd’hui')
            ->assertSeeText('1 à traiter')
            ->assertSeeText('Ventes du jour');

        Livewire::test(ListOrders::class, ['activeTab' => 'to_handle'])
            ->assertCanSeeTableRecords([$received])
            ->assertCanNotSeeTableRecords([$unpaidOnline, $onTheWay, $cancelled]);
        Livewire::test(ListOrders::class, ['activeTab' => 'on_the_way'])->assertCanSeeTableRecords([$onTheWay])->assertCanNotSeeTableRecords([$received]);
        Livewire::test(ListOrders::class, ['activeTab' => 'cancelled'])->assertCanSeeTableRecords([$cancelled])->assertCanNotSeeTableRecords([$received]);
    }

    public function test_a_picker_sees_the_orders_band_without_the_sales(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        Livewire::test(OrdersOverview::class)->assertSeeText('À traiter')->assertDontSeeText('Ventes du jour');
    }

    public function test_the_payments_and_customers_bands(): void
    {
        $this->order(OrderStatus::Delivered);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(PaymentsOverview::class)->assertSeeText('Paiements en ligne')->assertSeeText('À rembourser')->assertSeeText('Aucun remboursement en attente');
        Livewire::test(CustomersOverview::class)->assertSeeText('Clients')->assertSeeText('Nouveaux ce mois')->assertSeeText('Clients fidèles');
        Livewire::test(ListCustomers::class, ['activeTab' => 'guests'])->assertOk();
        Livewire::test(ListCustomers::class, ['activeTab' => 'loyal'])->assertOk();
    }

    public function test_the_products_list_says_what_needs_restocking_and_its_tabs_filter_the_catalogue(): void
    {
        $inStock = Product::factory()->create(['name' => 'Enceinte', 'stock' => 40, 'sold_count' => 3]);
        $soldOut = Product::factory()->create(['name' => 'Casque', 'stock' => 0, 'sold_count' => 12]);
        $offline = Product::factory()->create(['name' => 'Radio', 'stock' => 40, 'is_active' => false]);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $this->get(ListProducts::getUrl())->assertOk()->assertSeeText('Ajouter un produit');
        Livewire::test(ProductsOverview::class)
            ->assertSeeText('2 produits en ligne sur 3')
            ->assertSeeText('1 en rupture')
            ->assertSeeText('Meilleure vente')
            ->assertSeeText('Casque');

        Livewire::test(ListProducts::class, ['activeTab' => 'sold_out'])->assertCanSeeTableRecords([$soldOut])->assertCanNotSeeTableRecords([$inStock, $offline]);
        Livewire::test(ListProducts::class, ['activeTab' => 'offline'])->assertCanSeeTableRecords([$offline])->assertCanNotSeeTableRecords([$inStock, $soldOut]);
        Livewire::test(ListProducts::class, ['activeTab' => 'best_sellers'])->assertCanSeeTableRecords([$soldOut, $inStock], inOrder: true)->assertCanNotSeeTableRecords([$offline]);
    }

    public function test_the_catalogue_lists_have_their_header_band_and_tabs(): void
    {
        $audio = Category::factory()->create(['name' => 'Audio', 'parent_id' => null]);
        $empty = Category::factory()->create(['name' => 'Vide', 'parent_id' => null]);
        Product::factory()->create(['category_id' => $audio->id]);
        $brand = Brand::factory()->create(['name' => 'JBL']);
        $running = Collection::create(['name' => 'Offres du jour', 'slug' => 'offres-du-jour']);
        $scheduled = Collection::create(['name' => 'Noël', 'slug' => 'noel', 'starts_at' => now()->addWeek()]);
        $over = Collection::create(['name' => 'Rentrée', 'slug' => 'rentree', 'ends_at' => now()->subDay()]);
        ProductAttribute::create(['name' => 'Couleur', 'slug' => 'couleur'])->values()->create(['value' => 'Noir']);
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());

        Livewire::test(CategoriesOverview::class, ['scope' => 'all'])->assertSeeText('Catégories')->assertSeeText('encore vide');
        Livewire::test(CategoriesOverview::class, ['scope' => 'parents'])->assertSeeText('Catégories parentes')->assertSeeText('Glissez les lignes');
        Livewire::test(ListCategories::class, ['activeTab' => 'empty'])->assertCanSeeTableRecords([$empty])->assertCanNotSeeTableRecords([$audio]);
        $this->get(CategoryResource::getUrl('parents'))->assertOk();
        $this->get(CategoryResource::getUrl('sub'))->assertOk();

        Livewire::test(BrandsOverview::class)->assertSeeText('Marques')->assertSeeText('Produits sans marque');
        Livewire::test(ListBrands::class, ['activeTab' => 'empty'])->assertCanSeeTableRecords([$brand]);

        Livewire::test(CollectionsOverview::class)->assertSeeText('Sélections')->assertSeeText('1 programmée');
        Livewire::test(ListCollections::class, ['activeTab' => 'over'])->assertCanSeeTableRecords([$over])->assertCanNotSeeTableRecords([$running, $scheduled]);
        Livewire::test(ListCollections::class, ['activeTab' => 'scheduled'])->assertCanSeeTableRecords([$scheduled])->assertCanNotSeeTableRecords([$running, $over]);

        Livewire::test(ProductAttributesOverview::class)->assertSeeText('Attributs')->assertSeeText('Inutilisés');
        $this->get(ProductAttributeResource::getUrl())->assertOk()->assertSeeText('Couleur');
        Livewire::test(ProductReviewsOverview::class)->assertSeeText('Avis clients')->assertSeeText('Aucun avis en attente');
        Livewire::test(ImportCatalogOverview::class)->assertSeeText('Importer le catalogue')->assertSeeText('Aucun import pour le moment');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(OrderStatus $status, array $attributes = []): Order
    {
        return Order::forceCreate([
            'number' => 'KM-261007-'.str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT), 'status' => $status,
            'payment_method' => PaymentMethod::CashOnDelivery, 'payment_status' => PaymentStatus::Pending, 'source' => 'web',
            'customer_name' => 'Client', 'phone' => '+22507010203'.str_pad((string) (Order::count() + 1), 2, '0', STR_PAD_LEFT),
            'commune_name' => 'Cocody', 'zone_name' => 'Zone 1', 'district' => 'Riviera', 'subtotal' => 10000, 'shipping_fee' => 0,
            'discount' => 0, 'total' => 10000, 'marketing_opt_in' => false, 'terms_accepted_at' => now(), ...$attributes,
        ]);
    }
}
