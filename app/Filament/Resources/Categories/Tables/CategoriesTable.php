<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
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
                    ->imageSize(44)
                    ->extraImgAttributes(['class' => 'kl-thumb', 'loading' => 'lazy']),
                TextColumn::make('name')
                    ->label('Nom')
                    ->weight('semibold')
                    ->description(fn (Category $record) => $record->tagline)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Rattachée à')
                    ->placeholder('Rayon principal')
                    ->description(fn (Category $record) => $record->parent?->parent?->name),
                TextColumn::make('products_count')
                    ->label('Produits')
                    ->badge()
                    // A department holding sub-categories keeps its products in them: not empty.
                    ->color(fn (int $state, Category $record) => $state > 0 || $record->children_count > 0 ? 'gray' : 'warning')
                    ->formatStateUsing(fn (int $state, Category $record) => match (true) {
                        $state > 0 => (string) $state,
                        $record->children_count > 0 => '—',
                        default => 'Vide',
                    })
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('children_count')
                    ->label('Sous-catégories')
                    ->alignCenter()
                    ->formatStateUsing(fn (int $state) => $state ?: '—')
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('À l’accueil')
                    ->alignCenter()
                    ->icon(fn (bool $state) => $state ? 'heroicon-s-star' : 'heroicon-o-minus')
                    ->color(fn (bool $state) => $state ? 'warning' : 'gray'),
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
                Action::make('storefront')
                    ->label('Voir en boutique')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('Voir en boutique')
                    ->url(fn (Category $record) => route('categories.show', $record), shouldOpenInNewTab: true),
                // Third-level categories are the last level: no sub-category under them.
                Action::make('addChild')
                    ->label('Ajouter une sous-catégorie')
                    ->icon(Heroicon::OutlinedFolderPlus)
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('Ajouter une sous-catégorie')
                    ->url(fn (Category $record) => CategoryResource::getUrl('create', ['parent' => $record->getKey()]))
                    ->visible(fn (Category $record) => $record->parent?->parent_id === null && CategoryResource::canCreate()),
                EditAction::make()->iconButton()->tooltip('Modifier'),
                // Products block the deletion (database constraint) and sub-categories would be deleted with it.
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Supprimer')
                    ->hidden(fn (Category $record) => $record->products_count > 0 || $record->children_count > 0),
            ]);
    }
}
