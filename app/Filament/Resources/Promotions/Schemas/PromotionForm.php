<?php

namespace App\Filament\Resources\Promotions\Schemas;

use App\Filament\Support\StorefrontImage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Carte « Offres spéciales »')
                    ->description('Affichée dans le panneau « Offres spéciales » jusqu’à sa date de fin.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('location_label')
                            ->label('Portée')
                            ->required()
                            ->default('Sur tout le site')
                            ->maxLength(255),
                        TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('url')
                            ->label('Lien « Voir le détail »')
                            ->placeholder('https://… ou /categorie/…')
                            ->helperText('Facultatif : la catégorie, le produit ou la page de l’offre. Sans lien, la carte n’a pas de bouton.')
                            ->regex('#^(https?://|/)#')
                            ->validationMessages(['regex' => 'Le lien doit commencer par https:// ou /.'])
                            ->maxLength(255)
                            ->columnSpanFull(),
                        DateTimePicker::make('starts_at')
                            ->label('Début')
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label('Fin')
                            ->seconds(false)
                            ->required()
                            ->after('starts_at'),
                        StorefrontImage::make('image', 'promotions', [720, 720], 'carrée')
                            ->label('Visuel')
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('is_visible')
                            ->label('Visible dans « Offres spéciales »')
                            ->helperText('Décochez pour masquer l’offre sans la supprimer.')
                            ->default(true),
                        TextInput::make('position')
                            ->label('Ordre d’affichage')
                            ->helperText('Les plus petits numéros en premier ; à égalité, la date de début.')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
