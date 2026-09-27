<?php

namespace App\Filament\Resources\Couriers\Tables;

use App\Filament\Resources\Couriers\CourierActions;
use App\Models\Courier;
use App\Models\DeliveryZone;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouriersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                ImageColumn::make('photo')->label('')->disk('storefront')->circular(),
                TextColumn::make('user.name')
                    ->label('Livreur')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Courier $record) => $record->formattedPhone()),
                TextColumn::make('zones.name')->label('Zones')->badge()->color('gray'),
                TextColumn::make('transport')->label('Transport'),
                TextColumn::make('open_orders_count')->label('Livraisons en cours')->sortable(),
                TextColumn::make('status')
                    ->label('Compte')
                    ->badge()
                    ->state(fn (Courier $record) => $record->isSuspended() ? 'Suspendu' : 'Actif')
                    ->color(fn (string $state) => $state === 'Actif' ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('zone')
                    ->label('Zone')
                    ->options(fn () => DeliveryZone::orderBy('position')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $query, $zone) => $query->whereHas('zones', fn (Builder $query) => $query->whereKey($zone)))),
                TernaryFilter::make('active')
                    ->label('Compte')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Suspendus')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('user', fn (Builder $query) => $query->whereNull('suspended_at')),
                        false: fn (Builder $query) => $query->whereHas('user', fn (Builder $query) => $query->whereNotNull('suspended_at')),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    CourierActions::resetPassword(),
                    CourierActions::suspend(),
                    CourierActions::reactivate(),
                ]),
            ]);
    }
}
