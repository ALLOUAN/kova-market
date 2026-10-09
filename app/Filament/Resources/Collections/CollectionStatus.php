<?php

namespace App\Filament\Resources\Collections;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Where a home page selection stands: scheduled (start date ahead), running, or its offer over (end date passed).
 * Used by the list's header band, tabs and status column.
 */
final class CollectionStatus
{
    public static function scheduled(Builder $query): Builder
    {
        return $query->whereNotNull('starts_at')->where('starts_at', '>', now());
    }

    public static function running(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public static function over(Builder $query): Builder
    {
        return $query->whereNotNull('ends_at')->where('ends_at', '<=', now());
    }

    /**
     * @return array{0: string, 1: string, 2: string} label, colour, icon
     */
    public static function of(Collection $collection): array
    {
        return match (true) {
            ! $collection->hasStarted() => ['Programmée', 'info', 'heroicon-m-calendar-days'],
            $collection->ends_at?->isPast() => ['Offre terminée', 'warning', 'heroicon-m-clock'],
            default => ['En cours', 'success', 'heroicon-m-play-circle'],
        };
    }
}
