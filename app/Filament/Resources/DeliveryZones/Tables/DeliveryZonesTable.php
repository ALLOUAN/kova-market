<?php

namespace App\Filament\Resources\DeliveryZones\Tables;

use App\Models\DeliveryZone;
use App\Support\Money;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveryZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('communes'))
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')->label('Zone'),
                TextColumn::make('fee')
                    ->label('Frais')
                    ->formatStateUsing(fn (?int $state) => $state === 0 ? 'Gratuit' : Money::format($state))
                    ->placeholder('À définir'),
                TextColumn::make('delay_label')->label('Délai')->placeholder('—'),
                TextColumn::make('conditions')
                    ->label('Conditions')
                    ->state(fn (DeliveryZone $record) => $record->conditionsLabel())
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('communes.name')->label('Communes')->badge()->limitList(6)->expandableLimitedList(),
                ToggleColumn::make('is_active')
                    ->label('Ouverte')
                    // A zone cannot be opened before its fee is set.
                    ->disabled(fn (DeliveryZone $record) => $record->fee === null),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
