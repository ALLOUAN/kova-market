<?php

namespace App\Filament\Resources\Collections\RelationManagers;

use App\Support\Money;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Products of a merchandising collection, in the order the home page displays them (pivot "position").
 */
class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Produits de la collection';

    protected static ?string $modelLabel = 'produit';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('position')
                    ->label('#'),
                ImageColumn::make('image')
                    ->label('')
                    ->disk('storefront')
                    ->square(),
                TextColumn::make('name')
                    ->label('Produit')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('price')
                    ->label('Prix')
                    ->formatStateUsing(fn ($state) => Money::format($state)),
                TextColumn::make('stock')
                    ->label('Stock'),
                IconColumn::make('is_active')
                    ->label('En ligne')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Ajouter un produit')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()->label('Produit'),
                        // New products go to the end of the list.
                        Hidden::make('position')
                            ->default(fn () => (int) $this->getOwnerRecord()->products()->max('collection_product.position') + 1),
                    ]),
            ])
            ->recordActions([
                DetachAction::make()->label('Retirer'),
            ])
            ->toolbarActions([
                DetachBulkAction::make()->label('Retirer la sélection'),
            ])
            ->emptyStateHeading('Aucun produit dans cette collection');
    }
}
