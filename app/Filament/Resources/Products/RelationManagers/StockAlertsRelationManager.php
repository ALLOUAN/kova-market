<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\Permission;
use App\Models\StockAlert;
use App\Support\PhoneNumber;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Visitors waiting for the product to come back (EX-17): how many ask, for which variant, to decide on restocking.
 * Alerts are sent automatically at the restock; a catalog manager can delete one on the customer's request.
 */
class StockAlertsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockAlerts';

    protected static ?string $title = 'Alertes de réassort';

    protected static ?string $modelLabel = 'alerte';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $waiting = $ownerRecord->stockAlerts()->whereNull('notified_at')->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['variant.attributeValues.attribute', 'user']))
            ->defaultSort('id', 'desc')
            ->description('Les personnes en attente sont prévenues automatiquement, une seule fois, dès que le stock revient.')
            ->columns([
                TextColumn::make('created_at')->label('Demandée le')->dateTime('d/m/Y H:i'),
                TextColumn::make('variant')
                    ->label('Variante')
                    ->state(fn (StockAlert $record) => match (true) {
                        $record->product_variant_id === null => 'Toutes',
                        $record->variant->attributeValues->isEmpty() => $record->variant->sku,
                        default => $record->variant->label(),
                    }),
                TextColumn::make('contact')
                    ->label('Contact')
                    ->state(fn (StockAlert $record) => $record->phone ? PhoneNumber::format($record->phone) : $record->email)
                    ->description(fn (StockAlert $record) => $record->user?->name),
                TextColumn::make('notified_at')
                    ->label('État')
                    ->badge()
                    ->state(fn (StockAlert $record) => $record->notified_at ? 'Prévenu le '.$record->notified_at->format('d/m/Y') : 'En attente')
                    ->color(fn (StockAlert $record) => $record->notified_at ? 'gray' : 'warning'),
            ])
            ->filters([
                TernaryFilter::make('waiting')
                    ->label('État')
                    ->placeholder('Toutes')
                    ->trueLabel('En attente')
                    ->falseLabel('Déjà prévenues')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('notified_at'),
                        false: fn (Builder $query) => $query->whereNotNull('notified_at'),
                    ),
            ])
            ->recordActions([
                DeleteAction::make()->visible(fn () => auth()->user()?->can(Permission::ManageCatalog->value) ?? false),
            ])
            ->recordAction(null)
            ->emptyStateHeading('Personne n’attend ce produit');
    }
}
