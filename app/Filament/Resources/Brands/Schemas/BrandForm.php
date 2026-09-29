<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugInput;
use App\Filament\Support\StorefrontImage;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Marque')
                    ->columns(2)
                    ->schema([
                        ...SlugInput::make(),
                        TextInput::make('promo_label')
                            ->label('Accroche promotionnelle')
                            ->placeholder('Jusqu’à -20 %')
                            ->maxLength(60),
                        TextInput::make('position')
                            ->label('Ordre d’affichage')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        StorefrontImage::make('logo', 'brands', [140, 30], 'logo horizontal, fond transparent')
                            ->label('Logo')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                SeoFields::section(),
            ]);
    }
}
