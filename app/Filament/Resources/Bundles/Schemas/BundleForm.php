<?php

namespace App\Filament\Resources\Bundles\Schemas;

use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugInput;
use App\Filament\Support\StorefrontImage;
use App\Models\ProductVariant;
use App\Support\Money;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class BundleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Pack')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        ...SlugInput::make('Nom du pack'),
                        Select::make('category_id')
                            ->label('Catégorie')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('brand_id')
                            ->label('Marque')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                Section::make('Publication')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')->label('Visible sur la boutique')->default(true),
                        Placeholder::make('stock')
                            ->label('Packs disponibles')
                            ->content(fn ($record) => $record?->stock ?? '—')
                            ->helperText('Calculé à partir du stock des composants.')
                            ->visibleOn('edit'),
                    ]),
                Section::make('Composants')
                    ->columnSpan(3)
                    ->description('Les variantes vendues ensemble et leur quantité dans un pack. La vente d’un pack retire ces quantités du stock de chaque composant.')
                    ->schema([
                        Repeater::make('bundleItems')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->columns(4)
                            ->minItems(2)
                            ->live()
                            ->addActionLabel('Ajouter un composant')
                            ->schema([
                                Select::make('product_variant_id')
                                    ->label('Produit')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn (string $search) => self::components($search))
                                    ->getOptionLabelUsing(fn ($value) => ($variant = ProductVariant::with('product', 'attributeValues.attribute')->find($value)) ? self::label($variant) : null)
                                    ->required()
                                    ->distinct()
                                    ->live()
                                    ->columnSpan(3),
                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(99)
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true),
                            ]),
                    ]),
                Section::make('Prix')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->label('Prix du pack')
                            ->integer()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->required(),
                        TextInput::make('compare_at_price')
                            ->label('Prix barré')
                            ->helperText('Par exemple le total des composants achetés séparément.')
                            ->integer()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->gt('price'),
                        Placeholder::make('components_total')
                            ->label('Composants achetés séparément')
                            ->content(fn (Get $get) => Money::format(self::componentsTotal($get('bundleItems') ?? []))),
                        DateTimePicker::make('sale_ends_at')
                            ->label('Fin de l’offre')
                            ->helperText('Après cette date, le prix barré s’applique.')
                            ->seconds(false),
                    ]),
                Section::make('Visuel')
                    ->columnSpan(1)
                    ->schema([
                        StorefrontImage::make('image', 'products')->label('Image du pack')->required(),
                    ]),
                Section::make('Description')
                    ->columnSpan(3)
                    ->schema([
                        RichEditor::make('description')
                            ->hiddenLabel()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'link'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ]),
                    ]),
                SeoFields::section(),
            ]);
    }

    /**
     * Variants that can go in a pack (any product but packs), matched on product name or SKU.
     *
     * @return array<int, string>
     */
    private static function components(string $search): array
    {
        return ProductVariant::query()
            ->with('product', 'attributeValues.attribute')
            ->whereHas('product', fn (Builder $query) => $query->where('is_bundle', false))
            ->where(fn (Builder $query) => $query
                ->where('sku', 'like', "%{$search}%")
                ->orWhereHas('product', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")))
            ->limit(30)
            ->get()
            ->mapWithKeys(fn (ProductVariant $variant) => [$variant->id => self::label($variant)])
            ->all();
    }

    private static function label(ProductVariant $variant): string
    {
        $name = $variant->product->name.($variant->attributeValues->isNotEmpty() ? ' — '.$variant->label() : '');

        return "{$name} ({$variant->sku}) · ".Money::format($variant->currentPrice())." · stock {$variant->stock}";
    }

    /**
     * @param  array<array-key, array{product_variant_id?: mixed, quantity?: mixed}>  $items
     */
    private static function componentsTotal(array $items): int
    {
        $variants = ProductVariant::whereKey(collect($items)->pluck('product_variant_id')->filter())->get()->keyBy('id');

        return collect($items)->sum(fn (array $item) => ($variants->get($item['product_variant_id'] ?? 0)?->currentPrice() ?? 0) * max(1, (int) ($item['quantity'] ?? 1)));
    }
}
