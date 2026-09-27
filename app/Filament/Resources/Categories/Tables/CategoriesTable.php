<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent.parent')->withCount(['products', 'children']))
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('storefront')
                    ->square(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Rattachée à')
                    ->placeholder('Rayon principal')
                    ->description(fn (Category $record) => $record->parent?->parent?->name),
                TextColumn::make('products_count')
                    ->label('Produits')
                    ->sortable(),
                TextColumn::make('children_count')
                    ->label('Sous-catégories')
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('À l’accueil')
                    ->boolean(),
                TextColumn::make('position')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Rattachée à')
                    ->relationship('parent', 'name', fn (Builder $query) => $query->whereNull('parent_id')),
                TernaryFilter::make('roots')
                    ->label('Niveau')
                    ->placeholder('Tous')
                    ->trueLabel('Rayons principaux')
                    ->falseLabel('Sous-catégories')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('parent_id'),
                        false: fn (Builder $query) => $query->whereNotNull('parent_id'),
                    ),
                TernaryFilter::make('is_featured')->label('À l’accueil'),
            ])
            ->recordActions([
                EditAction::make(),
                // Products block the deletion (database constraint) and sub-categories would be deleted with it.
                DeleteAction::make()
                    ->hidden(fn (Category $record) => $record->products_count > 0 || $record->children_count > 0),
            ]);
    }
}
