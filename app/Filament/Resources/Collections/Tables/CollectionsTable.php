<?php

namespace App\Filament\Resources\Collections\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('products'))
            ->columns([
                TextColumn::make('name')
                    ->label('Titre')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Identifiant')
                    ->color('gray'),
                TextColumn::make('products_count')
                    ->label('Produits'),
                TextColumn::make('ends_at')
                    ->label('Fin de l’offre')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
