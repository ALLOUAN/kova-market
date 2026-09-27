<?php

namespace App\Filament\Resources\Brands\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('products'))
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('logo')
                    ->label('')
                    ->disk('storefront'),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('promo_label')
                    ->label('Accroche'),
                TextColumn::make('products_count')
                    ->label('Produits')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Les produits de cette marque seront conservés, sans marque.'),
            ]);
    }
}
