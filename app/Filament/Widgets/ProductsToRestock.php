<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Enums\SaleUnit;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Variants of online products at or under their stock alert threshold, lowest stock first (F-103).
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
            ->query(fn () => ProductVariant::query()
                ->with(['product', 'attributeValues'])
                ->whereHas('product', fn (Builder $query) => $query->active())
                // The general threshold counts displayed units: × 1 000 for a product sold by weight or volume.
                ->whereRaw('stock <= COALESCE(low_stock_threshold, ? * (SELECT '.SaleUnit::sqlFactor('products.sale_unit').' FROM products WHERE products.id = product_variants.product_id))', [config('storefront.product_card.limited_stock_threshold')])
                ->orderBy('stock'))
            ->columns([
                TextColumn::make('product.name')->label('Produit')->limit(50),
                TextColumn::make('variant')->label('Variante')->state(fn (ProductVariant $record) => $record->label()),
                TextColumn::make('sku')->label('Référence'),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : 'warning')
                    ->formatStateUsing(fn (int $state, ProductVariant $record) => $record->product->saleQuantity()->format($state))
                    ->description(fn (ProductVariant $record) => 'seuil : '.$record->lowStockThresholdLabel()),
            ])
            ->recordUrl(fn (ProductVariant $record) => ProductResource::canEdit($record->product) ? ProductResource::getUrl('edit', ['record' => $record->product]) : null)
            ->emptyStateHeading('Aucun produit à réapprovisionner')
            ->paginated([5, 10, 25]);
    }
}
