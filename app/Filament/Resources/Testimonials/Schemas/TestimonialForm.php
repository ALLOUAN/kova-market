<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Témoignage')
                    ->description('Affiché dans les fenêtres de connexion et de création de compte. Publiez uniquement de vrais avis, avec l’accord du client.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('author_name')
                            ->label('Nom affiché')
                            ->placeholder('Awa K.')
                            ->helperText('Prénom et initiale suffisent.')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('city')
                            ->label('Commune ou ville')
                            ->placeholder('Cocody')
                            ->maxLength(80),
                        Textarea::make('content')
                            ->label('Avis')
                            ->required()
                            ->rows(3)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        Select::make('rating')
                            ->label('Note')
                            ->options([5 => '★★★★★ (5)', 4 => '★★★★☆ (4)', 3 => '★★★☆☆ (3)', 2 => '★★☆☆☆ (2)', 1 => '★☆☆☆☆ (1)'])
                            ->default(5)
                            ->required(),
                        TextInput::make('position')->label('Ordre d’affichage')->integer()->minValue(0)->default(0)->required(),
                        Toggle::make('is_verified')
                            ->label('Client vérifié')
                            ->helperText('Seulement si ce client a réellement commandé chez vous.'),
                        Toggle::make('is_published')->label('Publié')->default(true),
                    ]),
            ]);
    }
}
