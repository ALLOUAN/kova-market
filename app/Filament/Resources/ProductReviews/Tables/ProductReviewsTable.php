<?php

namespace App\Filament\Resources\ProductReviews\Tables;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\ProductReviews\ReviewActions;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductReview;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductReviewsTable
{
    public static function configure(Table $table, bool $withProduct = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'orderItem.order']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->wrap()
                    ->url(fn (ProductReview $record) => $record->product ? ProductResource::getUrl('edit', ['record' => $record->product]) : null)
                    ->visible($withProduct),
                TextColumn::make('rating')
                    ->label('Note')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color(fn (int $state) => $state <= 2 ? 'danger' : 'warning')
                    ->size('lg')
                    ->sortable(),
                TextColumn::make('comment')
                    ->label('Commentaire')
                    ->placeholder('Sans commentaire')
                    ->limit(160)
                    ->tooltip(fn (ProductReview $record) => mb_strlen((string) $record->comment) > 160 ? $record->comment : null)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('author_name')
                    ->label('Client')
                    ->weight('semibold')
                    ->searchable()
                    ->description(fn (ProductReview $record) => $record->orderItem?->order?->number),
                TextColumn::make('status')->label('Statut')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(ReviewStatus::class),
                SelectFilter::make('rating')->label('Note')->options([5 => '5 étoiles', 4 => '4 étoiles', 3 => '3 étoiles', 2 => '2 étoiles', 1 => '1 étoile']),
            ])
            ->recordActions([
                ReviewActions::approve(),
                ReviewActions::reject(),
                Action::make('order')
                    ->label('Commande')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('gray')
                    ->visible(fn (ProductReview $record) => $record->orderItem?->order !== null)
                    ->url(fn (ProductReview $record) => OrderResource::getUrl('view', ['record' => $record->orderItem->order])),
            ])
            ->toolbarActions([
                ReviewActions::approveSelected(),
            ])
            ->emptyStateHeading('Aucun avis')
            ->emptyStateDescription('Les clients donnent leur avis depuis leur espace, une fois leur commande livrée.');
    }
}
