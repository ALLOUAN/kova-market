<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\BadgeVariant;
use App\Filament\Support\SlugInput;
use App\Filament\Support\StorefrontImage;
use App\Models\Product;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ]),
                Section::make('Prix (FCFA)')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        self::amount('price', 'Prix de vente')->required(),
                        self::amount('compare_at_price', 'Prix barré')
                            ->helperText('Prix avant remise. Laisser vide s’il n’y a pas de promotion.')
                            ->gt('price'),
                        self::amount('price_max', 'Prix maximum')
                            ->helperText('Pour afficher une fourchette « de … à … ».')
                            ->gte('price'),
                        DateTimePicker::make('sale_ends_at')
                            ->label('Fin de la promotion')
                            ->helperText('Affiche un compte à rebours sur la carte produit.')
                            ->seconds(false)
                            ->visible(fn (Get $get) => filled($get('compare_at_price'))),
                    ]),
                Section::make('Stock et livraison')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('stock')
                            ->label('Quantité en stock')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
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

    private static function amount(string $field, string $label): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            // Prices are still stored as decimals until the whole-FCFA columns land (step 3.2).
            ->formatStateUsing(fn ($state) => $state === null ? null : (int) round((float) $state))
            ->integer()
            ->minValue(0)
            ->suffix('FCFA');
    }
}
