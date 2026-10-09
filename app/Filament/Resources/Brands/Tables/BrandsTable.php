<?php

namespace App\Filament\Resources\Brands\Tables;

use App\Models\Brand;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
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
                    ->disk('storefront')
                    ->imageHeight(40)
                    ->imageWidth(72)
                    ->extraImgAttributes(['class' => 'kl-thumb kl-thumb--logo', 'loading' => 'lazy']),
                TextColumn::make('name')
                    ->label('Marque')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('promo_label')
                    ->label('Accroche')
                    ->placeholder('—')
                    ->color('gray')
                    ->limit(60),
                TextColumn::make('products_count')
                    ->label('Produits')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'gray' : 'warning')
                    ->formatStateUsing(fn (int $state) => $state > 0 ? (string) $state : 'Aucun')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('storefront')
                    ->label('Voir en boutique')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('Voir en boutique')
                    ->url(fn (Brand $record) => route('brands.show', $record), shouldOpenInNewTab: true),
                EditAction::make()->iconButton()->tooltip('Modifier'),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Supprimer')
                    ->modalDescription('Les produits de cette marque seront conservés, sans marque.'),
            ])
            ->emptyStateIcon('heroicon-o-check-badge')
            ->emptyStateHeading('Aucune marque ici')
            ->emptyStateDescription('Changez d’onglet ou ajoutez une marque.');
    }
}
