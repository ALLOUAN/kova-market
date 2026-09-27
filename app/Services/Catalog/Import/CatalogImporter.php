<?php

namespace App\Services\Catalog\Import;

use App\Enums\StockMovementReason;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\StockManager;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Catalog import from a CSV file (F-104), one line per variant. A known SKU updates its variant and product,
 * a new SKU adds a variant to the product of the same slug, or creates the product. Every line is saved on its
 * own: an invalid line is skipped and reported, the others are imported. The preview runs the very same import
 * inside a transaction that is rolled back, so it shows exactly what the import would do.
 */
class CatalogImporter
{
    public const MAX_ROWS = 5000;

    /** Columns of the template, in order; "attribut:<name>" columns may be added for the variant values. */
    public const COLUMNS = [
        'sku', 'produit', 'slug', 'categorie', 'marque', 'prix', 'prix_barre', 'promo_debut', 'promo_fin',
        'stock', 'seuil_alerte', 'description', 'image', 'actif',
    ];

    private const REQUIRED = ['sku', 'produit', 'prix'];

    private const ATTRIBUTE_PREFIX = 'attribut:';

    public function __construct(private StockManager $stock) {}

    public function preview(string $path): ImportReport
    {
        $report = new ImportReport(preview: true);
        $rows = $this->read($path);

        DB::beginTransaction();

        try {
            $this->importRows($rows, $report, null);
        } finally {
            DB::rollBack();
        }

        return $report;
    }

    public function import(string $path, ?User $user): ImportReport
    {
        $report = new ImportReport(preview: false);
        $this->importRows($this->read($path), $report, $user);

        activity('catalogue')
            ->causedBy($user)
            ->withProperties($report->toArray())
            ->log('Import CSV du catalogue');

        return $report;
    }

    /**
     * CSV template with the expected columns and two example lines.
     */
    public static function template(): string
    {
        $lines = [
            [...self::COLUMNS, 'attribut:Couleur'],
            ['CASQ-NOIR', 'Casque Bluetooth X1', 'casque-bluetooth-x1', 'Audio', 'Sony', '45000', '55000', '01/10/2026 08:00', '31/10/2026 23:59', '12', '3', 'Casque sans fil, 30 h d’autonomie.', 'uploads/products/casque-x1.webp', 'oui', 'Noir'],
            ['CASQ-BLANC', 'Casque Bluetooth X1', 'casque-bluetooth-x1', 'Audio', 'Sony', '45000', '', '', '', '4', '', '', '', 'oui', 'Blanc'],
        ];

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\u{FEFF}");

        foreach ($lines as $line) {
            fputcsv($out, $line, ';');
        }

        rewind($out);

        return stream_get_contents($out);
    }

    /**
     * @param  array<int, array<string, string>>  $rows  line number => values by column
     */
    private function importRows(array $rows, ImportReport $report, ?User $user): void
    {
        $seen = [];

        foreach ($rows as $line => $row) {
            $report->rows++;

            try {
                $sku = $row['sku'] ?? '';

                if (isset($seen[Str::upper($sku)])) {
                    throw new ImportException("SKU {$sku} en double dans le fichier (déjà ligne {$seen[Str::upper($sku)]}).");
                }

                $seen[Str::upper($sku)] = $line;
                DB::transaction(fn () => $this->importRow($row, $report, $user));
            } catch (ImportException $exception) {
                $report->fail($line, $exception->getMessage());
            } catch (QueryException $exception) {
                $report->fail($line, 'Enregistrement impossible : '.Str::limit($exception->getPrevious()?->getMessage() ?? $exception->getMessage(), 120));
            }
        }
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importRow(array $row, ImportReport $report, ?User $user): void
    {
        $data = $this->validate($row);
        $variant = ProductVariant::with('product')->where('sku', $data['sku'])->first();
        $product = $variant?->product ?? Product::where('slug', $data['slug'])->first();

        // A pack's stock is its components': it is managed in Promotions › Packs only.
        if ($product?->is_bundle) {
            throw new ImportException('Les packs ne s’importent pas : gérez-les dans Promotions › Packs.');
        }

        if ($variant) {
            $this->updateVariant($variant, $data, $user, $report->preview);
            $report->variantsUpdated++;

            return;
        }

        if ($product) {
            $this->addVariant($product, $data, $user);
            $report->variantsCreated++;

            return;
        }

        $this->createProduct($data, $user, $report->preview);
        $report->productsCreated++;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateVariant(ProductVariant $variant, array $data, ?User $user, bool $preview): void
    {
        $product = $variant->product;

        if ($data['slug_given'] && $data['slug'] !== $product->slug) {
            throw new ImportException("Le SKU {$data['sku']} appartient déjà au produit « {$product->name} » ({$product->slug}).");
        }

        $product->fill(array_filter([
            'name' => $data['name'],
            'description' => $data['description'],
            'category_id' => $data['category']?->id,
            'brand_id' => $data['brand']?->id,
            'is_active' => $data['active'],
            'image' => $this->image($data, $product->slug, $preview),
        ], fn ($value) => $value !== null))->save();

        $variant->update($this->variantAttributes($data));
        $this->assignAttributes($variant, $data['attributes']);

        if ($data['stock'] !== null) {
            $this->stock->setTo($variant, $data['stock'], StockMovementReason::Adjustment, $user, 'Import CSV');
        }

        $product->syncFromVariants();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addVariant(Product $product, array $data, ?User $user): void
    {
        if (Str::lower($data['name']) !== Str::lower($product->name)) {
            throw new ImportException("Le slug « {$product->slug} » est déjà utilisé par le produit « {$product->name} ».");
        }

        if ($data['attributes'] === []) {
            throw new ImportException("Le produit « {$product->name} » existe déjà : ajoutez les colonnes attribut:… pour distinguer cette variante, ou reprenez son SKU pour le mettre à jour.");
        }

        $variant = $this->stock->createVariant($product, [
            'sku' => $data['sku'],
            ...$this->variantAttributes($data),
        ], $data['stock'] ?? 0, $user);

        $this->assignAttributes($variant, $data['attributes']);
        $product->syncFromVariants();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createProduct(array $data, ?User $user, bool $preview): void
    {
        if (! $data['category']) {
            throw new ImportException('Catégorie obligatoire pour un nouveau produit.');
        }

        $image = $this->image($data, $data['slug'], $preview);

        if ($image === null) {
            throw new ImportException('Image obligatoire pour un nouveau produit.');
        }

        $product = Product::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'category_id' => $data['category']->id,
            'brand_id' => $data['brand']?->id,
            'is_active' => $data['active'] ?? true,
            'image' => $image,
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'],
            'sale_starts_at' => $data['sale_starts_at'],
            'sale_ends_at' => $data['sale_ends_at'],
            'stock' => $data['stock'] ?? 0,
        ]);

        $variant = $this->stock->createDefaultVariant($product, $data['sku'], $user);

        if ($data['low_stock_threshold'] !== null) {
            $variant->update(['low_stock_threshold' => $data['low_stock_threshold']]);
        }

        $this->assignAttributes($variant, $data['attributes']);
        $product->syncFromVariants();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function variantAttributes(array $data): array
    {
        return [
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'],
            'sale_starts_at' => $data['sale_starts_at'],
            'sale_ends_at' => $data['sale_ends_at'],
            'low_stock_threshold' => $data['low_stock_threshold'],
        ];
    }

    /**
     * @param  array<string, string>  $attributes  attribute name => value
     */
    private function assignAttributes(ProductVariant $variant, array $attributes): void
    {
        if ($attributes === []) {
            return;
        }

        $values = collect($attributes)->map(function (string $value, string $name): int {
            $attribute = ProductAttribute::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first()
                ?? throw new ImportException("Attribut inconnu : « {$name} ». Créez-le d’abord dans Catalogue › Attributs de variantes.");

            $existing = $attribute->values()->whereRaw('LOWER(value) = ?', [Str::lower($value)])->first();

            return ($existing ?? $attribute->values()->create(['value' => $value, 'position' => $attribute->values()->count()]))->getKey();
        });

        $variant->attributeValues()->sync($values->values()->all());
    }

    /**
     * Image path kept on the product: a file already on the site, or a web address downloaded once
     * (not during the preview). Null when the line gives no image.
     *
     * @param  array<string, mixed>  $data
     */
    private function image(array $data, string $slug, bool $preview): ?string
    {
        $image = $data['image'];

        if ($image === null) {
            return null;
        }

        if (! Str::startsWith($image, ['http://', 'https://'])) {
            $path = ltrim($image, '/');

            return Storage::disk('storefront')->exists($path) ? $path : throw new ImportException("Image introuvable sur le site : {$image}.");
        }

        if ($preview) {
            return $image;
        }

        try {
            $response = Http::timeout(15)->get($image);
        } catch (Throwable) {
            throw new ImportException("Image injoignable : {$image}.");
        }

        $extension = match (Str::before((string) $response->header('Content-Type'), ';')) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if (! $response->successful() || $extension === null) {
            throw new ImportException("Image non téléchargeable (JPG, PNG ou WebP attendu) : {$image}.");
        }

        $path = "uploads/products/{$slug}-".Str::lower(Str::random(6)).".{$extension}";
        Storage::disk('storefront')->put($path, $response->body());

        return $path;
    }

    /**
     * Checks a line and turns it into typed values.
     *
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function validate(array $row): array
    {
        $number = fn (?string $value) => filled($value) ? preg_replace('/[\s\x{00A0}\x{202F}]|FCFA|F$/iu', '', $value) : null;

        $values = [
            'sku' => $row['sku'] ?? null,
            'produit' => $row['produit'] ?? null,
            'slug' => $row['slug'] ?? null,
            'prix' => $number($row['prix'] ?? null),
            'prix_barre' => $number($row['prix_barre'] ?? null),
            'stock' => $number($row['stock'] ?? null),
            'seuil_alerte' => $number($row['seuil_alerte'] ?? null),
            'actif' => filled($row['actif'] ?? null) ? Str::lower($row['actif']) : null,
        ];

        $validator = Validator::make($values, [
            'sku' => ['required', 'max:64', 'alpha_dash'],
            'produit' => ['required', 'max:255'],
            'slug' => ['nullable', 'max:255', 'alpha_dash'],
            'prix' => ['required', 'integer', 'min:0'],
            'prix_barre' => ['nullable', 'integer', 'gt:prix'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
            'actif' => ['nullable', 'in:oui,non,1,0,vrai,faux'],
        ], [], [
            'produit' => 'nom du produit', 'prix_barre' => 'prix barré', 'seuil_alerte' => 'seuil d’alerte',
        ]);

        if ($validator->fails()) {
            throw new ImportException($validator->errors()->first());
        }

        $category = null;

        if (filled($row['categorie'] ?? null)) {
            $category = Category::where('slug', Str::slug($row['categorie']))
                ->orWhereRaw('LOWER(name) = ?', [Str::lower($row['categorie'])])
                ->first() ?? throw new ImportException("Catégorie inconnue : « {$row['categorie']} ».");
        }

        $brand = null;

        if (filled($row['marque'] ?? null)) {
            $brand = Brand::where('slug', Str::slug($row['marque']))
                ->orWhereRaw('LOWER(name) = ?', [Str::lower($row['marque'])])
                ->first() ?? throw new ImportException("Marque inconnue : « {$row['marque']} ».");
        }

        $startsAt = $this->date($row['promo_debut'] ?? null, 'promo_debut');
        $endsAt = $this->date($row['promo_fin'] ?? null, 'promo_fin');

        if ($startsAt && $endsAt && $endsAt->lte($startsAt)) {
            throw new ImportException('La fin de la promotion doit suivre son début.');
        }

        return [
            'sku' => $values['sku'],
            'name' => $values['produit'],
            'slug' => filled($values['slug']) ? Str::lower($values['slug']) : Str::slug($values['produit']),
            'slug_given' => filled($values['slug']),
            'description' => filled($row['description'] ?? null) ? $row['description'] : null,
            'category' => $category,
            'brand' => $brand,
            'price' => (int) $values['prix'],
            'compare_at_price' => $values['prix_barre'] !== null ? (int) $values['prix_barre'] : null,
            'sale_starts_at' => $startsAt,
            'sale_ends_at' => $endsAt,
            'stock' => $values['stock'] !== null ? (int) $values['stock'] : null,
            'low_stock_threshold' => $values['seuil_alerte'] !== null ? (int) $values['seuil_alerte'] : null,
            'image' => filled($row['image'] ?? null) ? $row['image'] : null,
            'active' => $values['actif'] === null ? null : in_array($values['actif'], ['oui', '1', 'vrai'], true),
            'attributes' => collect($row)
                ->filter(fn (string $value, string $column) => Str::startsWith($column, self::ATTRIBUTE_PREFIX) && filled($value))
                ->mapWithKeys(fn (string $value, string $column) => [trim(Str::after($column, self::ATTRIBUTE_PREFIX)) => $value])
                ->all(),
        ];
    }

    private function date(?string $value, string $column): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        foreach (['d/m/Y H:i', 'd/m/Y', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat("!{$format}", $value);
            } catch (Throwable) {
                continue;
            }

            if ($date && $date->format($format) === $value) {
                return $date;
            }
        }

        throw new ImportException("Date illisible dans {$column} : « {$value} » (attendu 31/12/2026 ou 31/12/2026 18:00).");
    }

    /**
     * Reads the file: UTF-8 or Windows-1252 (Excel), ";" or "," separator, header names in any case.
     *
     * @return array<int, array<string, string>> line number => values by column
     *
     * @throws ImportException
     */
    private function read(string $path): array
    {
        $content = @file_get_contents($path);

        if ($content === false || trim($content) === '') {
            throw new ImportException('Le fichier est vide ou illisible.');
        }

        $content = preg_replace('/^\x{FEFF}/u', '', $content) ?? $content;

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($content, "\n");
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $header = array_map(fn (?string $column) => $this->column((string) $column), fgetcsv($stream, null, $delimiter, '"', '') ?: []);
        $missing = array_diff(self::REQUIRED, $header);

        if ($missing !== []) {
            throw new ImportException('Colonnes obligatoires absentes : '.implode(', ', $missing).'. Partez du modèle à télécharger.');
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $line++;

            if ($values === [null] || collect($values)->every(fn ($value) => blank($value))) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                throw new ImportException('Le fichier dépasse '.self::MAX_ROWS.' lignes : découpez-le en plusieurs fichiers.');
            }

            $rows[$line] = collect($header)
                ->mapWithKeys(fn (string $column, int $index) => [$column => trim((string) ($values[$index] ?? ''))])
                ->all();
        }

        fclose($stream);

        if ($rows === []) {
            throw new ImportException('Le fichier ne contient aucune ligne de produit.');
        }

        return $rows;
    }

    /**
     * "Prix barré" → "prix_barre"; "Attribut: Couleur" keeps the attribute name.
     */
    private function column(string $name): string
    {
        $name = trim($name);

        if (Str::startsWith(Str::lower($name), self::ATTRIBUTE_PREFIX)) {
            return self::ATTRIBUTE_PREFIX.trim(Str::after($name, ':'));
        }

        return Str::slug($name, '_');
    }
}
