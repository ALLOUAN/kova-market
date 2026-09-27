<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        $type = fn (Get $get): ?CouponType => $get('type') instanceof CouponType ? $get('type') : CouponType::tryFrom((string) $get('type'));
        $target = fn (Get $get): ?CouponTarget => $get('target') instanceof CouponTarget ? $get('target') : CouponTarget::tryFrom((string) $get('target'));

        return $schema
            ->columns(3)
            ->components([
                Section::make('Code et avantage')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->helperText('Lettres et chiffres ; le client peut le saisir en minuscules.')
                            ->required()
                            ->maxLength(40)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state) => mb_strtoupper(trim($state))),
                        TextInput::make('description')
                            ->label('Description')
                            ->helperText('Affichée dans la fenêtre « Codes promo » de la boutique.')
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Type')
                            ->options(CouponType::class)
                            ->default(CouponType::Percentage)
                            ->required()
                            ->live(),
                        TextInput::make('value')
                            ->label(fn (Get $get) => $type($get) === CouponType::Percentage ? 'Pourcentage' : 'Montant')
                            ->suffix(fn (Get $get) => $type($get) === CouponType::Percentage ? '%' : 'FCFA')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(fn (Get $get) => $type($get) === CouponType::Percentage ? 100 : null)
                            ->required(fn (Get $get) => $type($get) !== CouponType::FreeShipping)
                            ->hidden(fn (Get $get) => $type($get) === CouponType::FreeShipping)
                            ->dehydrateStateUsing(fn ($state) => (int) $state),
                        TextInput::make('minimum_subtotal')
                            ->label('Minimum d’achat (hors livraison)')
                            ->suffix('FCFA')
                            ->integer()
                            ->minValue(1),
                    ]),
                Section::make('Diffusion')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')->label('Actif')->default(true),
                        Toggle::make('is_public')
                            ->label('Affiché dans la boutique')
                            ->helperText('Sinon, seuls les clients qui connaissent le code peuvent l’utiliser.'),
                        DateTimePicker::make('starts_at')->label('À partir du')->seconds(false),
                        DateTimePicker::make('ends_at')->label('Jusqu’au')->seconds(false)->after('starts_at'),
                    ]),
                Section::make('Plafonds')
                    ->columnSpan(2)
                    ->columns(2)
                    ->description('Laisser vide pour ne pas limiter. Le client est reconnu à son numéro de téléphone.')
                    ->schema([
                        TextInput::make('usage_limit')
                            ->label('Utilisations au total')
                            ->integer()
                            ->minValue(1),
                        TextInput::make('usage_limit_per_customer')
                            ->label('Utilisations par client')
                            ->integer()
                            ->minValue(1)
                            ->default(1),
                    ]),
                Section::make('Articles concernés')
                    ->columnSpan(2)
                    ->schema([
                        Select::make('target')
                            ->label('S’applique à')
                            ->options(CouponTarget::class)
                            ->default(CouponTarget::All)
                            ->required()
                            ->live(),
                        Select::make('categories')
                            ->label('Catégories')
                            ->helperText('Un rayon inclut ses sous-catégories.')
                            ->relationship('categories', 'name', fn (Builder $query) => $query->orderBy('name'))
                            ->multiple()
                            ->preload()
                            ->required(fn (Get $get) => $target($get) === CouponTarget::Categories)
                            ->visible(fn (Get $get) => $target($get) === CouponTarget::Categories),
                        Select::make('products')
                            ->label('Produits')
                            ->relationship('products', 'name')
                            ->multiple()
                            ->searchable()
                            ->required(fn (Get $get) => $target($get) === CouponTarget::Products)
                            ->visible(fn (Get $get) => $target($get) === CouponTarget::Products),
                    ]),
            ]);
    }
}
