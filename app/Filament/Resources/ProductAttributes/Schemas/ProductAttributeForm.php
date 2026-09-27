<?php

namespace App\Filament\Resources\ProductAttributes\Schemas;

use App\Filament\Support\SlugInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductAttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Attribut')
                    ->columns(3)
                    ->schema([
                        ...SlugInput::make('Nom (Couleur, Taille…)'),
                        TextInput::make('position')->label('Ordre')->integer()->minValue(0)->default(0)->required(),
                    ]),
                Section::make('Valeurs')
                    ->schema([
                        Repeater::make('values')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->columns(2)
                            ->schema([
                                TextInput::make('value')->label('Valeur')->required()->maxLength(100)->distinct(),
                                ColorPicker::make('color_hex')->label('Couleur de la pastille (facultatif)'),
                            ])
                            ->addActionLabel('Ajouter une valeur')
                            ->defaultItems(1),
                    ]),
            ]);
    }
}
