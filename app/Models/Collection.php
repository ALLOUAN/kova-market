<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A curated, ordered selection of products (e.g. "Deals of The Day") displayed by merchandising sections.
 */
#[Fillable(['name', 'slug', 'starts_at', 'ends_at'])]
class Collection extends Model
{
    use LogsActivity;

    /**
     * Shown on the site: no start date, or a start date reached.
     */
    public function hasStarted(): bool
    {
        return $this->starts_at === null || $this->starts_at->isPast();
    }

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
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('position')
            ->orderByPivot('position');
    }
}
