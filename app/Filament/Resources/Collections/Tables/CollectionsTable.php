<?php

namespace App\Filament\Resources\Collections\Tables;

use App\Filament\Resources\Collections\CollectionStatus;
use App\Models\Collection;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
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
                    ->weight('semibold')
                    ->description(fn (Collection $record) => '/selection/'.$record->slug)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->state(fn (Collection $record) => CollectionStatus::of($record)[0])
                    ->badge()
                    ->color(fn (Collection $record) => CollectionStatus::of($record)[1])
                    ->icon(fn (Collection $record) => CollectionStatus::of($record)[2]),
                TextColumn::make('products_count')
                    ->label('Produits')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'gray' : 'danger')
                    ->formatStateUsing(fn (int $state) => $state > 0 ? (string) $state : 'Aucun')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('À partir du')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Dès maintenant')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fin de l’offre')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (Collection $record) => $record->ends_at?->isFuture() ? 'dans '.$record->ends_at->diffForHumans(syntax: true) : null)
                    ->placeholder('Sans fin')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('storefront')
                    ->label('Voir en boutique')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('Voir en boutique')
                    ->url(fn (Collection $record) => route('collections.show', $record), shouldOpenInNewTab: true)
                    ->visible(fn (Collection $record) => $record->hasStarted()),
                EditAction::make()->iconButton()->tooltip('Modifier'),
            ])
            ->emptyStateIcon('heroicon-o-sparkles')
            ->emptyStateHeading('Aucune sélection ici')
            ->emptyStateDescription('Changez d’onglet pour voir les autres sélections.');
    }
}
