<?php

namespace App\Filament\Resources\ProductReviews;

use App\Enums\Permission;
use App\Enums\ReviewStatus;
use App\Models\ProductReview;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Moderation of customer reviews, shared by the reviews list and the product's "Avis clients" tab. The content of a
 * review is never edited: it is published as the customer wrote it, or refused.
 */
class ReviewActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Publier')
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn (ProductReview $record) => $record->status !== ReviewStatus::Approved && self::mayModerate())
            ->action(function (ProductReview $record): void {
                $record->moderate(ReviewStatus::Approved, auth()->user());
                Notification::make()->title('Avis publié')->success()->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Refuser')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('L’avis ne sera pas affiché sur le site. Refusez seulement un avis injurieux, hors sujet ou contenant des données personnelles : un avis négatif mais honnête doit être publié.')
            ->visible(fn (ProductReview $record) => $record->status !== ReviewStatus::Rejected && self::mayModerate())
            ->action(function (ProductReview $record): void {
                $record->moderate(ReviewStatus::Rejected, auth()->user());
                Notification::make()->title('Avis refusé')->success()->send();
            });
    }

    public static function approveSelected(): BulkAction
    {
        return BulkAction::make('approveSelected')
            ->label('Publier la sélection')
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn () => self::mayModerate())
            ->action(function (Collection $records): void {
                $records->each(fn (ProductReview $review) => $review->moderate(ReviewStatus::Approved, auth()->user()));
                Notification::make()->title($records->count().' avis publié(s)')->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function mayModerate(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageCatalog->value);
    }
}
