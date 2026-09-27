<?php

namespace App\Filament\Resources\Bundles\Tables;

use App\Models\BundleItem;
use App\Models\Product;
use App\Support\Money;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BundlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('bundleItems.variant.product'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('image')->label('')->disk('storefront')->square(),
                TextColumn::make('name')
                    ->label('Pack')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record) => $record->bundleItems
                        ->map(fn (BundleItem $item) => "{$item->quantity} × {$item->variant->product->name}")
                        ->join(', ')),
                TextColumn::make('price')
                    ->label('Prix')
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->description(fn (Product $record) => $record->isOnSale() ? 'au lieu de '.Money::format($record->compare_at_price) : null)
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Disponibles')
                    ->badge()
                    ->color(fn (Product $record) => match (true) {
                        $record->isSoldOut() => 'danger',
                        $record->hasLimitedStock() => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                ToggleColumn::make('is_active')->label('En ligne'),
            ])
            // No delete: a pack leaves the storefront by being switched off, its orders keep their contents.
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
