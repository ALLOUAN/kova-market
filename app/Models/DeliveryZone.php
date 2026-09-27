<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Delivery zone: a fee in whole FCFA and an indicative delay for the communes it groups (F-120).
 */
#[Fillable(['name', 'fee', 'delay_label', 'is_active', 'position'])]
class DeliveryZone extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class)->orderBy('position')->orderBy('name');
    }

    /**
     * Deliverable: switched on and priced.
     */
    public function isDeliverable(): bool
    {
        return $this->is_active && $this->fee !== null;
    }
}
