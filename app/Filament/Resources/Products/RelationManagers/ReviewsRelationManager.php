<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\ProductReviews\Tables\ProductReviewsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The product's customer reviews, with the same moderation as Catalogue › Avis clients.
 */
class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Avis clients';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->reviews_count > 0 ? number_format($ownerRecord->rating, 1, ',', ' ').' ★ ('.$ownerRecord->reviews_count.')' : null;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return ProductReviewsTable::configure($table, withProduct: false);
    }
}
