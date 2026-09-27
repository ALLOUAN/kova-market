<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Enums\BannerPlacement;
use App\Filament\Support\StorefrontImage;
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
                            ->maxLength(255),
                        StorefrontImage::make('image', 'banners')
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
                Section::make('Prix affiché (FCFA)')
                    ->columnSpan(1)
                    ->visible($showsPrice)
                    ->schema([
                        TextInput::make('price')->label('Prix')->integer()->minValue(0)->suffix('FCFA'),
                        TextInput::make('compare_at_price')->label('Prix barré')->integer()->minValue(0)->suffix('FCFA')->gt('price'),
                        TextInput::make('badge')->label('Badge')->placeholder('-30 %')->maxLength(20),
                    ]),
            ]);
    }
}
