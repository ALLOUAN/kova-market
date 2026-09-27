<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Online products out of stock or under the "limited stock" threshold, lowest stock first.
 */
class ProductsToRestock extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Produits à réapprovisionner';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewCatalog->value);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Product::active()
                ->with('category')
                ->where('stock', '<=', config('storefront.product_card.limited_stock_threshold'))
                ->orderBy('stock'))
            ->columns([
                TextColumn::make('name')->label('Produit')->limit(60),
                TextColumn::make('category.name')->label('Catégorie'),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : 'warning'),
            ])
            ->recordUrl(fn (Product $record) => ProductResource::canEdit($record) ? ProductResource::getUrl('edit', ['record' => $record]) : null)
            ->emptyStateHeading('Aucun produit à réapprovisionner')
            ->paginated([5, 10, 25]);
    }
}
