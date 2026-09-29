<?php

namespace App\Filament\Resources\Collections\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Collection')
                    ->description('Chaque collection alimente une section de la page d’accueil. Son identifiant technique ne peut pas être modifié. « Nouveautés » et « Populaires » se remplissent seules (produits les plus récents, les plus vendus) tant que vous ne leur ajoutez aucun produit.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Titre affiché')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Identifiant')
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('ends_at')
                            ->label('Fin de l’offre')
                            ->helperText('Pilote le compte à rebours de la section, s’il y en a un.')
                            ->seconds(false),
                    ]),
            ]);
    }
}
