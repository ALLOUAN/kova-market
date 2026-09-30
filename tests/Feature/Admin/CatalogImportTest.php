<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Enums\SaleUnit;
use App\Enums\StockMovementReason;
use App\Filament\Pages\ImportCatalog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\Import\CatalogImporter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'sku;produit;slug;categorie;marque;prix;prix_barre;promo_debut;promo_fin;stock;seuil_alerte;description;image;actif';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('storefront');
        Storage::disk('storefront')->put('uploads/products/photo.webp', 'image');
        Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);
        Brand::factory()->create(['name' => 'Sony', 'slug' => 'sony']);
    }

    public function test_500_lines_with_3_invalid_create_497_products_and_report_3_errors(): void
    {
        $lines = [self::HEADER];

        for ($i = 1; $i <= 500; $i++) {
            $lines[] = match ($i) {
                10 => "SKU-{$i};Produit {$i};;Inconnue;;1000;;;;5;;;uploads/products/photo.webp;oui",
                20 => "SKU-{$i};Produit {$i};;Audio;;mille;;;;5;;;uploads/products/photo.webp;oui",
                30 => "SKU-1;Produit {$i};;Audio;;1000;;;;5;;;uploads/products/photo.webp;oui",
                default => "SKU-{$i};Produit {$i};;Audio;Sony;1000;;;;5;;;uploads/products/photo.webp;oui",
            };
        }

        $report = app(CatalogImporter::class)->import($this->csv($lines), null);

        $this->assertSame(497, Product::count());
        $this->assertSame([500, 497, 497], [$report->rows, $report->imported(), $report->productsCreated]);
        $this->assertSame([
            11 => 'Catégorie inconnue : « Inconnue ».',
            21 => 'Le champ prix doit être un entier.',
            31 => 'SKU SKU-1 en double dans le fichier (déjà ligne 2).',
        ], $report->errors);
        $this->assertSame('Import CSV du catalogue', Activity::latest('id')->first()->description);
    }

    public function test_the_preview_saves_nothing(): void
    {
        $report = app(CatalogImporter::class)->preview($this->csv([self::HEADER, 'CASQ-1;Casque X1;;Audio;;45000;;;;3;;;uploads/products/photo.webp;oui']));

        $this->assertSame([1, 1], [$report->rows, $report->productsCreated]);
        $this->assertSame(0, Product::count());
        $this->assertSame(0, ProductVariant::count());
    }

    public function test_a_known_sku_updates_its_product_and_its_stock_through_a_movement(): void
    {
        $importer = app(CatalogImporter::class);
        $importer->import($this->csv([self::HEADER, 'CASQ-1;Casque X1;casque-x1;Audio;Sony;45000;;;;3;;Ancien texte;uploads/products/photo.webp;oui']), null);

        $report = $importer->import($this->csv([self::HEADER, 'CASQ-1;Casque X1 Pro;;;;40 000;50 000;01/10/2026;31/10/2026 23:59;8;2;;;non']), null);

        $this->assertSame(1, $report->variantsUpdated);
        $product = Product::sole();
        $variant = $product->defaultVariant;
        $this->assertSame(['Casque X1 Pro', 'casque-x1', 'Ancien texte', false], [$product->name, $product->slug, $product->description, $product->is_active]);
        $this->assertSame([40000, 50000, 8, 2], [$variant->price, $variant->compare_at_price, $variant->stock, $variant->low_stock_threshold]);
        $this->assertSame(['2026-10-01 00:00', '2026-10-31 23:59'], [$variant->sale_starts_at->format('Y-m-d H:i'), $variant->sale_ends_at->format('Y-m-d H:i')]);
        $this->assertSame([StockMovementReason::Adjustment, 5, 'Import CSV'], [$variant->stockMovements()->first()->reason, $variant->stockMovements()->first()->quantity, $variant->stockMovements()->first()->note]);
    }

    public function test_products_sold_by_weight_or_local_unit_are_imported_in_their_unit(): void
    {
        $header = self::HEADER.';mode_vente;quantite_min;pas';
        $report = app(CatalogImporter::class)->import($this->csv([
            $header,
            'TOM-KG;Tomates fraîches;;Audio;;1000;;;;12,5;5;;uploads/products/photo.webp;oui;kg;0,5;0,25',
            'GOMBO;Gombo;;Audio;;500;;;;30;;;uploads/products/photo.webp;oui;tas;;',
            'OEUF;Œufs;;Audio;;100;;;;2,5;;;uploads/products/photo.webp;oui;;;',
        ]), null);

        $this->assertSame(2, $report->productsCreated);
        $this->assertStringContainsString('Quantité entière attendue', $report->errors[4] ?? implode(' ', $report->errors));

        $tomatoes = Product::where('slug', 'tomates-fraiches')->sole();
        $this->assertSame([SaleUnit::Kilogram, 500, 250], [$tomatoes->sale_unit, $tomatoes->min_quantity, $tomatoes->quantity_step]);
        $this->assertSame([12_500, 5000], [$tomatoes->defaultVariant->stock, $tomatoes->defaultVariant->low_stock_threshold]);

        $okra = Product::where('slug', 'gombo')->sole();
        $this->assertSame([SaleUnit::Local, 'tas', 30], [$okra->sale_unit, $okra->unit_label, $okra->defaultVariant->stock]);

        // Stock updated later in kilos too.
        app(CatalogImporter::class)->import($this->csv([$header, 'TOM-KG;Tomates fraîches;;;;1000;;;;8,75;;;;;;;']), null);
        $this->assertSame(8750, $tomatoes->defaultVariant->fresh()->stock);
    }

    public function test_variants_of_one_product_come_from_lines_sharing_its_slug(): void
    {
        ProductAttribute::create(['name' => 'Couleur', 'slug' => 'couleur']);
        $header = self::HEADER.';attribut:Couleur';

        $report = app(CatalogImporter::class)->import($this->csv([
            $header,
            'CASQ-NOIR;Casque X1;casque-x1;Audio;;45000;;;;3;;;uploads/products/photo.webp;oui;Noir',
            'CASQ-BLANC;Casque X1;casque-x1;Audio;;47000;;;;0;;;;oui;Blanc',
            'CASQ-ROSE;Casque X1;casque-x1;Audio;;47000;;;;1;;;;oui;',
            'ENC-1;Enceinte;casque-x1;Audio;;30000;;;;1;;;;oui;Noir',
        ]), null);

        $product = Product::sole();
        $this->assertSame(['CASQ-NOIR', 'CASQ-BLANC'], $product->variants->pluck('sku')->all());
        $this->assertSame(['Couleur : Noir', 'Couleur : Blanc'], $product->variants->map->label()->all());
        $this->assertSame([45000, 47000, 3], [$product->price, $product->price_max, $product->stock]);
        $this->assertSame([
            4 => 'Le produit « Casque X1 » existe déjà : ajoutez les colonnes attribut:… pour distinguer cette variante, ou reprenez son SKU pour le mettre à jour.',
            5 => 'Le slug « casque-x1 » est déjà utilisé par le produit « Casque X1 ».',
        ], $report->errors);
    }

    public function test_excel_files_and_images_from_the_web_are_accepted(): void
    {
        Http::fake(['cdn.example.com/*' => Http::response('binary', 200, ['Content-Type' => 'image/jpeg'])]);

        // Windows-1252, commas and header names as typed in Excel.
        $content = mb_convert_encoding("SKU,Produit,Catégorie,Prix,Prix barré,Image\nENC-1,Enceinte Été,Audio,30000,,https://cdn.example.com/enceinte.jpg\nENC-2,Radio,Audio,20000,,uploads/products/absente.webp", 'Windows-1252', 'UTF-8');
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);

        $report = app(CatalogImporter::class)->import($path, null);

        $product = Product::sole();
        $this->assertSame('Enceinte Été', $product->name);
        $this->assertStringStartsWith('uploads/products/enceinte-ete-', $product->image);
        Storage::disk('storefront')->assertExists($product->image);
        $this->assertSame([3 => 'Image introuvable sur le site : uploads/products/absente.webp.'], $report->errors);
    }

    public function test_managers_analyse_then_import_and_pickers_cannot(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        $file = UploadedFile::fake()->createWithContent('catalogue.csv', self::HEADER."\nCASQ-1;Casque X1;;Audio;;45000;;;;3;;;uploads/products/photo.webp;oui\nCASQ-2;Casque X2;;Nulle part;;45000;;;;3;;;uploads/products/photo.webp;oui");

        Livewire::test(ImportCatalog::class)
            ->set('data.file', $file)
            ->call('analyse')
            ->assertSet('report.imported', 1)
            ->assertSee('Catégorie inconnue : « Nulle part ».')
            ->assertSee('rien n’est encore enregistré');

        $this->assertSame(0, Product::count());

        Livewire::test(ImportCatalog::class)
            ->set('data.file', $file)
            ->call('analyse')
            ->callAction('import')
            ->assertSet('report.preview', false)
            ->assertSet('analysedFile', null);

        $this->assertSame(['Casque X1'], Product::pluck('name')->all());
        $this->assertStringContainsString('attribut:Couleur', CatalogImporter::template());

        $this->actingAs(User::factory()->staff(Role::Picker)->create());
        $this->get(ImportCatalog::getUrl())->assertForbidden();
    }

    /**
     * @param  list<string>  $lines
     */
    private function csv(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, implode("\n", $lines));

        return $path;
    }
}
