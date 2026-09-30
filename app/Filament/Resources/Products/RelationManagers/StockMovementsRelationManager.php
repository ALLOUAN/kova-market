<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Support\SaleQuantity;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only stock history of all the product's variants (F-103).
 */
class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Mouvements de stock';

    protected static ?string $modelLabel = 'mouvement';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['variant', 'user']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
                TextColumn::make('variant.sku')->label('Référence'),
                TextColumn::make('reason')->label('Motif')->badge(),
                TextColumn::make('quantity')
                    ->label('Quantité')
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '−').$this->rules()->format(abs($state)))
                    ->color(fn (int $state) => $state < 0 ? 'danger' : 'success'),
                TextColumn::make('stock_after')->label('Stock après')->formatStateUsing(fn (int $state) => $this->rules()->format($state)),
                TextColumn::make('user.name')->label('Par')->placeholder('Système'),
                TextColumn::make('note')->label('Commentaire')->placeholder('—')->wrap(),
            ])
            ->filters([
                SelectFilter::make('reason')->label('Motif')->options(StockMovementReason::class),
            ])
            ->recordAction(null)
            ->emptyStateHeading('Aucun mouvement');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    /** Movements count base units: shown in the product's unit (−1,75 kg). */
    private function rules(): SaleQuantity
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        return $product->saleQuantity();
    }
}
