<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Commune (or town) customers pick for delivery; its zone gives the fee and the delay (F-043, F-120).
 */
#[Fillable(['delivery_zone_id', 'name', 'position'])]
class Commune extends Model
{
    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    /**
     * Communes that can be delivered to (zone switched on and priced), grouped by zone order.
     */
    #[Scope]
    protected function deliverable(Builder $query): Builder
    {
        return $query
            ->whereHas('zone', fn (Builder $query) => $query->where('is_active', true)->whereNotNull('fee'))
            ->orderBy(DeliveryZone::select('position')->whereColumn('delivery_zones.id', 'communes.delivery_zone_id'))
            ->orderBy('position')
            ->orderBy('name');
    }

    public function isDeliverable(): bool
    {
        return (bool) $this->zone?->isDeliverable();
    }

    /**
     * The "Intérieur" destination: shipped by carrier, the customer gives the town.
     */
    public function isInterior(): bool
    {
        return (bool) $this->zone?->isInterior();
    }
}
