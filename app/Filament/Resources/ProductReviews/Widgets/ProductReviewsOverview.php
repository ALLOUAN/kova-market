<?php

namespace App\Filament\Resources\ProductReviews\Widgets;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ProductReviews\ProductReviewResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\ProductReview;

/**
 * Header of the customer reviews: those waiting for a decision, the average rating shown on the store, published,
 * refused and received this month, each card opening the matching tab.
 */
class ProductReviewsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $count = fn (ReviewStatus $status) => ProductReview::query()->where('status', $status)->count();
        $pending = $count(ReviewStatus::Pending);
        $approved = $count(ReviewStatus::Approved);
        $rejected = $count(ReviewStatus::Rejected);
        $average = ProductReview::query()->where('status', ReviewStatus::Approved)->avg('rating');
        $thisMonth = ProductReview::query()->where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = ProductReview::query()->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()->subSecond()])->count();
        $low = ProductReview::query()->where('status', ReviewStatus::Pending)->where('rating', '<=', 2)->count();
        $tab = fn (string $tab) => ProductReviewResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Avis clients',
            'icon' => 'heroicon-o-star',
            'lead' => $pending > 0
                ? '<strong>'.$pending.'</strong> avis à valider avant d’apparaître sur la boutique.'
                : 'Aucun avis en attente : tout est traité.',
            'kpis' => [
                self::kpi('À valider', (string) $pending, $low > 0 ? "dont <strong>{$low}</strong> note(s) de 1 ou 2 étoiles" : 'Publiés seulement une fois validés',
                    'heroicon-o-clock', 'orange', $tab('pending'), $pending > 0 ? 'orange' : null),
                self::kpi('Note moyenne', $average ? number_format((float) $average, 1, ',', ' ').' / 5' : '—', $average ? str_repeat('★', (int) round($average)).str_repeat('☆', 5 - (int) round($average)).' sur les avis publiés' : 'Aucun avis publié',
                    'heroicon-o-star', 'gold'),
                self::kpi('Publiés', (string) $approved, 'Visibles sur les fiches produits',
                    'heroicon-o-check-circle', 'green', $tab('approved')),
                self::kpi('Refusés', (string) $rejected, 'Jamais affichés',
                    'heroicon-o-x-circle', 'navy', $tab('rejected')),
                self::kpi('Reçus ce mois', (string) $thisMonth, 'Mois dernier : '.$lastMonth.self::trend($thisMonth, $lastMonth),
                    'heroicon-o-chat-bubble-left-right', 'navy', $tab('all')),
            ],
        ];
    }
}
