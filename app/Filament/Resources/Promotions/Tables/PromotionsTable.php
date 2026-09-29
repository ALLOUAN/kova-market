<?php

namespace App\Filament\Resources\Promotions\Tables;

use App\Models\Promotion;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('storefront'),
                TextColumn::make('title')
                    ->label('Offre')
                    ->searchable()
                    ->description(fn (Promotion $record) => $record->description),
                TextColumn::make('starts_at')
                    ->label('Début')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('État')
                    ->badge()
                    ->state(fn (Promotion $record) => match (true) {
                        $record->ends_at->isPast() => 'Terminée',
                        $record->starts_at->isFuture() => 'À venir',
                        default => 'En cours',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'En cours' => 'success',
                        'À venir' => 'info',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_visible')->label('Visible'),
            ])
            ->filters([
                TernaryFilter::make('finished')
                    ->label('Terminées')
                    ->placeholder('Toutes')
                    ->trueLabel('Seulement les terminées')
                    ->falseLabel('En cours et à venir')
                    ->queries(
                        true: fn (Builder $query) => $query->where('ends_at', '<', now()),
                        false: fn (Builder $query) => $query->where('ends_at', '>=', now()),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Aucune offre spéciale')
            ->emptyStateDescription('Sans offre visible, le panneau « Offres spéciales » invite le client à parcourir la boutique.');
    }
}
