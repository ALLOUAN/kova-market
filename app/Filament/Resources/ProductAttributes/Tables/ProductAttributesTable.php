<?php

namespace App\Filament\Resources\ProductAttributes\Tables;

use App\Models\ProductAttribute;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductAttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('values'))
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')->label('Attribut')->searchable(),
                TextColumn::make('values.value')->label('Valeurs')->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (ProductAttribute $record) => $record->values()->whereHas('variants')->exists()),
            ]);
    }
}
