<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Support\Money;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'brand'])
                ->withCount(['stockAlerts as waiting_alerts_count' => fn (Builder $query) => $query->whereNull('notified_at')]))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('storefront')
                    ->square(),
                TextColumn::make('name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->description(fn (Product $record) => $record->brand?->name),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Prix')
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->description(fn (Product $record) => $record->isOnSale() ? 'au lieu de '.Money::format($record->compare_at_price) : null)
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (Product $record) => match (true) {
                        $record->isSoldOut() => 'danger',
                        $record->hasLimitedStock() => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                // Visitors waiting for a restock (EX-17): a demand signal for sold-out products.
                TextColumn::make('waiting_alerts_count')
                    ->label('Alertes')
                    ->tooltip('Personnes qui attendent le retour en stock')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (int $state) => $state ?: null)
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('sold_count')
                    ->label('Ventes')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('En ligne'),
                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('brand_id')
                    ->label('Marque')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')->label('En ligne'),
                Filter::make('sold_out')
                    ->label('En rupture')
                    ->query(fn (Builder $query) => $query->where('stock', 0)),
                Filter::make('awaited')
                    ->label('Attendus par des clients')
                    ->query(fn (Builder $query) => $query->whereHas('stockAlerts', fn (Builder $query) => $query->whereNull('notified_at'))),
                Filter::make('low_stock')
                    ->label('Stock bas')
                    ->query(fn (Builder $query) => $query->whereBetween('stock', [1, config('storefront.product_card.limited_stock_threshold')])),
                Filter::make('on_sale')
                    ->label('En promotion')
                    ->query(fn (Builder $query) => $query->whereColumn('compare_at_price', '>', 'price')),
            ])
            // No delete: products leave the storefront by being switched off (soft deletes come with orders).
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
