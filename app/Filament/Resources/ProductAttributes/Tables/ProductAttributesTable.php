<?php

namespace App\Filament\Resources\ProductAttributes\Tables;

use App\Models\ProductAttribute;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductAttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('values')->withCount('values')
                // Variants built on one of its values: an attribute in use cannot be deleted.
                ->addSelect(['variants_count' => DB::table('attribute_value_product_variant')
                    ->join('attribute_values', 'attribute_values.id', '=', 'attribute_value_product_variant.attribute_value_id')
                    ->whereColumn('attribute_values.attribute_id', 'attributes.id')
                    ->selectRaw('count(distinct attribute_value_product_variant.product_variant_id)')]))
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Attribut')
                    ->weight('semibold')
                    ->description(fn (ProductAttribute $record) => $record->values_count.' valeur'.($record->values_count > 1 ? 's' : ''))
                    ->searchable(),
                TextColumn::make('values.value')
                    ->label('Valeurs')
                    ->badge()
                    ->color('gray')
                    ->limitList(8)
                    ->expandableLimitedList(),
                TextColumn::make('variants_count')
                    ->label('Variantes')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state.' variante'.($state > 1 ? 's' : '') : 'Inutilisé')
                    ->alignCenter(),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Modifier'),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Supprimer')
                    ->hidden(fn (ProductAttribute $record) => $record->variants_count > 0),
            ])
            ->emptyStateIcon('heroicon-o-adjustments-horizontal')
            ->emptyStateHeading('Aucun attribut ici')
            ->emptyStateDescription('Créez par exemple « Couleur » ou « Taille » pour proposer des variantes.');
    }
}
