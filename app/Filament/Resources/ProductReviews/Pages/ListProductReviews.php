<?php

namespace App\Filament\Resources\ProductReviews\Pages;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ProductReviews\ProductReviewResource;
use App\Filament\Resources\ProductReviews\Widgets\ProductReviewsOverview;
use App\Models\ProductReview;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProductReviews extends ListRecords
{
    protected static string $resource = ProductReviewResource::class;

    /**
     * The header band (ProductReviewsOverview) carries the title and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [ProductReviewsOverview::class];
    }

    public function getTabs(): array
    {
        $status = fn (ReviewStatus $status) => fn (Builder $query) => $query->where('status', $status);

        return [
            'pending' => Tab::make('À valider')
                ->modifyQueryUsing($status(ReviewStatus::Pending))
                ->badge(fn () => ProductReview::where('status', ReviewStatus::Pending)->count() ?: null)
                ->badgeColor('warning'),
            'approved' => Tab::make('Publiés')->modifyQueryUsing($status(ReviewStatus::Approved)),
            'rejected' => Tab::make('Refusés')->modifyQueryUsing($status(ReviewStatus::Rejected)),
            'all' => Tab::make('Tous'),
        ];
    }
}
