<?php

namespace App\Filament\Resources\Couriers\RelationManagers;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * History of a courier's deliveries (F-126), with the cash collected and whether it was handed over.
 */
class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Livraisons';

    protected static ?string $modelLabel = 'livraison';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('assigned_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Commande')
                    ->weight('bold')
                    ->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
                TextColumn::make('assigned_at')->label('Confiée le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('commune_name')->label('Commune')->description(fn (Order $record) => $record->zone_name),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('cash_collected')
                    ->label('Encaissé')
                    ->formatStateUsing(fn (?int $state) => $state === null ? null : Money::format($state))
                    ->description(fn (Order $record) => match (true) {
                        $record->cash_collected === null => null,
                        $record->cash_settled_at !== null => 'Reçu le '.$record->cash_settled_at->format('d/m/Y'),
                        $record->cash_remitted > 0 => Money::format($record->cash_collected - $record->cash_remitted).' à reverser',
                        default => 'À reverser',
                    })
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(OrderStatus::class)->multiple(),
                TernaryFilter::make('cash_due')
                    ->label('Encaissements')
                    ->placeholder('Tous')
                    ->trueLabel('À reverser')
                    ->falseLabel('Reçus')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('cash_collected')->whereNull('cash_settled_at'),
                        false: fn (Builder $query) => $query->whereNotNull('cash_settled_at'),
                    ),
                Filter::make('this_month')
                    ->label('Ce mois-ci')
                    ->query(fn (Builder $query) => $query->where('assigned_at', '>=', now()->startOfMonth())),
            ])
            ->recordAction(null)
            ->emptyStateHeading('Aucune livraison pour le moment');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
