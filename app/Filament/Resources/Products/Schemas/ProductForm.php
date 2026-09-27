<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\BadgeVariant;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugInput;
use App\Filament\Support\StorefrontImage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Produit')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        ...SlugInput::make('Nom du produit'),
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
                        Toggle::make('is_active')
                            ->label('Visible sur la boutique')
                            ->default(true),
                        Placeholder::make('sold_count')
                            ->label('Ventes')
                            ->content(fn (?Product $record) => $record->sold_count ?? 0)
                            ->helperText('Mis à jour par les ventes payées.'),
                    ]),
                // On creation these fields make the default variant; afterwards prices and stock live on the
                // variants (tab "Variantes") and the product only shows their summary.
                Section::make('Prix et stock')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        self::amount('price', 'Prix de vente')->required()->visibleOn('create'),
                        self::amount('compare_at_price', 'Prix barré')
                            ->helperText('Prix avant remise. Laisser vide s’il n’y a pas de promotion.')
                            ->gt('price')
                            ->visibleOn('create'),
                        TextInput::make('stock')
                            ->label('Stock initial')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->visibleOn('create'),
                        TextInput::make('sku')
                            ->label('Référence (SKU)')
                            ->helperText('Laisser vide pour une référence automatique (KM-000123).')
                            ->maxLength(64)
                            ->alphaDash()
                            ->unique(ProductVariant::class, 'sku')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->visibleOn('create'),
                        Placeholder::make('price_summary')
                            ->label('Prix')
                            ->content(fn (?Product $record) => $record ? self::priceSummary($record) : null)
                            ->helperText('Les prix et les dates de promotion se modifient dans l’onglet « Variantes » ci-dessous.')
                            ->visibleOn('edit'),
                        Placeholder::make('stock_summary')
                            ->label('Stock total')
                            ->content(fn (?Product $record) => $record?->stock)
                            ->helperText('Le stock se modifie variante par variante (« Ajuster le stock »).')
                            ->visibleOn('edit'),
                        // F-090: the reduced price only applies between these dates, then the crossed-out price.
                        DateTimePicker::make('sale_starts_at')
                            ->label('Début de la promotion')
                            ->helperText('Vide : dès maintenant.')
                            ->seconds(false)
                            ->visibleOn('create'),
                        DateTimePicker::make('sale_ends_at')
                            ->label('Fin de la promotion')
                            ->helperText('Après cette date, le prix barré s’applique. Affiche un compte à rebours sur la carte produit.')
                            ->seconds(false)
                            ->after('sale_starts_at')
                            ->visibleOn('create'),
                    ]),
                Section::make('Livraison')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('free_shipping')
                            ->label('Livraison offerte'),
                        TextInput::make('return_days')
                            ->label('Délai de retour (jours)')
                            ->integer()
                            ->minValue(0)
                            ->maxValue(365),
                    ]),
                Section::make('Visuels')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        StorefrontImage::make('image', 'products')
                            ->label('Image principale')
                            ->required(),
                        StorefrontImage::make('hover_image', 'products')
                            ->label('Image au survol'),
                    ]),
                Section::make('Badges')
                    ->columnSpan(1)
                    ->schema([
                        Repeater::make('badges')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('label')->label('Texte')->required()->maxLength(30),
                                Select::make('variant')->label('Couleur')->options(BadgeVariant::options())->required(),
                            ])
                            ->maxItems(3)
                            ->addActionLabel('Ajouter un badge')
                            ->defaultItems(0),
                    ]),
                Section::make('Couleurs disponibles')
                    ->columnSpan(3)
                    ->collapsed()
                    ->schema([
                        Repeater::make('colors')
                            ->hiddenLabel()
                            ->columns(3)
                            ->schema([
                                TextInput::make('name')->label('Nom')->required(),
                                ColorPicker::make('hex')->label('Couleur')->required(),
                                StorefrontImage::make('image', 'products')->label('Image'),
                            ])
                            ->addActionLabel('Ajouter une couleur')
                            ->defaultItems(0),
                        TextInput::make('variants_count')
                            ->label('Nombre total de variantes')
                            ->helperText('Les variantes gérées (taille, couleur, stock par variante) arrivent à l’étape 3.4.')
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                    ]),
                Section::make('Description')
                    ->columnSpan(3)
                    ->schema([
                        RichEditor::make('description')
                            ->hiddenLabel()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ]),
                    ]),
                SeoFields::section(),
                Section::make('Caractéristiques')
                    ->columnSpan(3)
                    ->collapsed()
                    ->schema([
                        Repeater::make('specifications')
                            ->hiddenLabel()
                            ->columns(2)
                            ->schema([
                                TextInput::make('label')->label('Caractéristique')->required(),
                                TextInput::make('value')->label('Valeur')->required(),
                            ])
                            ->addActionLabel('Ajouter une caractéristique')
                            ->defaultItems(0),
                    ]),
            ]);
    }

    private static function priceSummary(Product $record): string
    {
        $price = Money::format($record->price);

        if ($record->price_max) {
            $price = 'de '.$price.' à '.Money::format($record->price_max);
        }

        return $record->isOnSale() ? $price.' (au lieu de '.Money::format($record->compare_at_price).')' : $price;
    }

    private static function amount(string $field, string $label): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->integer()
            ->minValue(0)
            ->suffix('FCFA');
    }
}
