<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\SaleUnit;
use App\Filament\Support\BadgeVariant;
use App\Filament\Support\QuantityInput;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugInput;
use App\Filament\Support\StorefrontImage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use App\Support\SaleQuantity;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                        Placeholder::make('rating_summary')
                            ->label('Note des clients')
                            ->content(fn (?Product $record) => $record && $record->reviews_count > 0
                                ? number_format($record->rating, 1, ',', ' ').' / 5 ('.$record->reviews_count.' avis publiés)'
                                : 'Aucun avis publié')
                            ->helperText('Calculée à partir des avis publiés (onglet « Avis clients »). Sans avis, aucune étoile n’est affichée.')
                            ->visibleOn('edit'),
                    ]),
                // Mon Marché: by the piece, by weight, by volume… Prices are per unit, quantities in that unit.
                Section::make('Mode de vente')
                    ->description('Comment le client achète ce produit. Le prix et le stock s’indiquent dans cette unité : par kg, par litre, par tas…')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        ToggleButtons::make('sale_unit')
                            ->label('Vendu')
                            ->options(SaleUnit::class)
                            ->default(SaleUnit::Piece->value)
                            ->inline()
                            ->live()
                            ->required()
                            // Stock is counted in the unit (grams for "kg"): switching with stock would change its meaning.
                            ->disabled(fn (?Product $record) => $record !== null && $record->stock > 0)
                            ->helperText(fn (?Product $record) => $record !== null && $record->stock > 0
                                ? 'Pour changer le mode de vente, ramenez d’abord le stock à 0 : il est compté dans l’unité actuelle.'
                                : null)
                            ->columnSpanFull(),
                        TextInput::make('unit_label')
                            ->label('Nom de l’unité')
                            ->placeholder('tas, botte, seau, régime…')
                            ->helperText('Affiché après le prix et la quantité : « 500 FCFA / tas », « 3 tas ».')
                            ->maxLength(30)
                            ->required(fn (Get $get) => QuantityInput::unit($get('sale_unit')) === SaleUnit::Local)
                            ->visible(fn (Get $get) => QuantityInput::unit($get('sale_unit')) === SaleUnit::Local),
                        QuantityInput::make('min_quantity', 'Quantité minimale', fn (Get $get) => $get('sale_unit'))
                            ->helperText(fn (Get $get) => 'Vide : '.self::rules($get)->format(self::rules($get)->minimum()).'.'),
                        QuantityInput::make('quantity_step', 'Pas (incrément)', fn (Get $get) => $get('sale_unit'))
                            ->helperText(fn (Get $get) => 'Le client choisit par '.self::rules($get)->format(self::rules($get)->step()).(QuantityInput::unit($get('sale_unit'))->isMeasured() ? ' : 0,25 · 0,5 · 0,75…' : '.')),
                        QuantityInput::make('max_quantity', 'Quantité maximale par commande', fn (Get $get) => $get('sale_unit'))
                            ->helperText(fn (Get $get) => 'Vide : '.self::rules($get)->format(self::rules($get)->maximum()).'.'),
                    ]),
                // On creation these fields make the default variant; afterwards prices and stock live on the
                // variants (tab "Variantes") and the product only shows their summary.
                Section::make('Prix et stock')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        self::amount('price', 'Prix de vente')
                            ->label(fn (Get $get) => 'Prix de vente'.self::rules($get)->priceSuffix())
                            ->required()
                            ->visibleOn('create'),
                        self::amount('compare_at_price', 'Prix barré')
                            ->helperText('Prix avant remise. Laisser vide s’il n’y a pas de promotion.')
                            ->gt('price')
                            ->visibleOn('create'),
                        QuantityInput::make('stock', 'Stock initial', fn (Get $get) => $get('sale_unit'))
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
                            ->content(fn (?Product $record) => $record?->saleQuantity()->format($record->stock))
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
                            ->label('Livraison offerte')
                            ->helperText('Affiche « Livraison offerte » sur la carte et la fiche du produit.'),
                        TextInput::make('return_days')
                            ->label('Délai de retour (jours)')
                            ->helperText('Affiche « N jours pour changer d’avis ». Vide : rien d’affiché.')
                            ->integer()
                            ->minValue(0)
                            ->maxValue(365),
                    ]),
                Section::make('Visuels')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        StorefrontImage::make('image', 'products', [1246, 976])
                            ->label('Image principale')
                            ->required(),
                        StorefrontImage::make('hover_image', 'products', [1246, 976], 'même format que l’image principale')
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
                                StorefrontImage::make('image', 'products', [1246, 976])->label('Image'),
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

    /** The quantity rules as the form currently sets them (unit chosen, minimum, step, ceiling typed). */
    private static function rules(Get $get): SaleQuantity
    {
        return new SaleQuantity(QuantityInput::unit($get('sale_unit')), $get('unit_label'));
    }

    private static function priceSummary(Product $record): string
    {
        $price = Money::format($record->price).$record->saleQuantity()->priceSuffix();

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
