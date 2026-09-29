<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Enums\BannerPlacement;
use App\Filament\Support\StorefrontImage;
use App\Models\Banner;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        $showsPrice = fn (Get $get): bool => ($get('placement') instanceof BannerPlacement ? $get('placement') : BannerPlacement::tryFrom((string) $get('placement')))?->showsPrice() ?? false;

        return $schema
            ->columns(3)
            ->components([
                Section::make('Emplacement et visuel')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('placement')
                            ->label('Emplacement')
                            ->options(BannerPlacement::options())
                            ->required()
                            ->live(),
                        TextInput::make('url')
                            ->label('Lien du bouton')
                            ->placeholder('https://… ou /page/…')
                            ->helperText('Vide : la boutique.')
                            ->maxLength(255),
                        TextInput::make('button_label')
                            ->label('Texte du bouton')
                            ->placeholder(Banner::DEFAULT_BUTTON)
                            ->helperText('Vide : « '.Banner::DEFAULT_BUTTON.' ». Court de préférence (2 à 3 mots) : le bouton est rond.')
                            ->maxLength(40),
                        StorefrontImage::make('image', 'banners',
                            fn (Get $get) => BannerPlacement::tryFrom((string) $get('placement'))?->imageSize(),
                            fn (Get $get) => filled($get('placement')) ? null : 'choisissez l’emplacement pour voir les dimensions de l’image',
                        )
                            ->label('Image')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Diffusion')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_visible')->label('Visible')->default(true),
                        TextInput::make('position')->label('Ordre dans le carrousel')->integer()->minValue(0)->default(0)->required(),
                        DateTimePicker::make('starts_at')->label('À partir du')->seconds(false),
                        DateTimePicker::make('ends_at')->label('Jusqu’au')->seconds(false)->after('starts_at'),
                    ]),
                Section::make('Textes')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('subtitle')->label('Surtitre')->maxLength(255),
                        TextInput::make('highlight')->label('Mot mis en avant')->maxLength(255),
                        TextInput::make('title')->label('Titre')->maxLength(255),
                        TextInput::make('tagline')->label('Accroche')->maxLength(255),
                    ]),
                Section::make('Produit mis en avant')
                    ->columnSpan(2)
                    ->description('Choisissez le produit que la bannière présente : son prix, son prix barré et sa remise s’affichent automatiquement et suivent leurs changements, et le bouton mène à sa fiche (sauf lien saisi plus haut).')
                    ->schema([
                        Select::make('product_id')
                            ->label('Produit')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('Aucun : prix saisis à la main'),
                    ]),
                Section::make('Prix affiché (FCFA)')
                    ->columnSpan(1)
                    ->visible(fn (Get $get) => $showsPrice($get) && blank($get('product_id')))
                    ->schema([
                        TextInput::make('price')->label('Prix')->integer()->minValue(0)->suffix('FCFA'),
                        TextInput::make('compare_at_price')->label('Prix barré')->integer()->minValue(0)->suffix('FCFA')->gt('price'),
                        TextInput::make('badge')->label('Badge')->placeholder('-30 %')->maxLength(20),
                    ]),
            ]);
    }
}
