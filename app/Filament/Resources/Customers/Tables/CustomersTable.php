<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use App\Support\Money;
use App\Support\PhoneNumber;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_order_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->formatStateUsing(fn (?string $state) => $state ? PhoneNumber::format($state) : null)
                    ->placeholder('—')
                    // Typed as on the storefront ("07 01 02 03 04") or stored ("+2250701020304").
                    ->searchable(query: fn (Builder $query, string $search) => filled($digits = preg_replace('/\D/', '', $search))
                        ? $query->where('phone', 'like', "%{$digits}%")
                        : $query->whereRaw('1 = 0')),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->state(fn (Customer $record) => $record->isGuest() ? 'Invité' : 'Compte')
                    ->color(fn (string $state) => $state === 'Compte' ? 'success' : 'gray'),
                TextColumn::make('orders_count')
                    ->label('Commandes')
                    ->sortable(),
                TextColumn::make('total_spent')
                    ->label('Total dépensé')
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->sortable(),
                TextColumn::make('last_order_at')
                    ->label('Dernière commande')
                    ->dateTime('d/m/Y')
                    ->placeholder('Aucune')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Client depuis')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('account')
                    ->label('Type')
                    ->placeholder('Tous')
                    ->trueLabel('Comptes')
                    ->falseLabel('Invités')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('user_id'),
                        false: fn (Builder $query) => $query->whereNull('user_id'),
                    ),
                TernaryFilter::make('buyers')
                    ->label('Commandes')
                    ->placeholder('Tous')
                    ->trueLabel('Ont commandé')
                    ->falseLabel('Aucune commande')
                    ->queries(
                        true: fn (Builder $query) => $query->where('orders_count', '>', 0),
                        false: fn (Builder $query) => $query->where('orders_count', 0),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
