<?php

namespace App\Filament\Resources\Testimonials\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('author_name')->label('Client')->searchable()->description(fn ($record) => $record->city),
                TextColumn::make('content')->label('Avis')->limit(80)->wrap()->searchable(),
                TextColumn::make('rating')->label('Note')->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                IconColumn::make('is_verified')->label('Vérifié')->boolean(),
                ToggleColumn::make('is_published')->label('Publié'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Aucun témoignage')
            ->emptyStateDescription('Sans témoignage, les fenêtres de connexion montrent les garanties de la boutique (livraison, paiement).');
    }
}
