<?php

namespace App\Filament\Resources\Coupons\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\CouponUsage;
use App\Support\Money;
use App\Support\PhoneNumber;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only list of the orders that used the code (F-091). A cancelled order gives its use back.
 */
class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    protected static ?string $title = 'Utilisations';

    protected static ?string $modelLabel = 'utilisation';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('order'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
                TextColumn::make('order.number')
                    ->label('Commande')
                    ->url(fn (CouponUsage $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                TextColumn::make('order.customer_name')->label('Client'),
                TextColumn::make('phone')->label('Téléphone')->formatStateUsing(fn (string $state) => PhoneNumber::format($state)),
                TextColumn::make('amount')->label('Économie')->formatStateUsing(fn (int $state) => Money::format($state)),
            ])
            ->recordAction(null)
            ->emptyStateHeading('Pas encore utilisé');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
