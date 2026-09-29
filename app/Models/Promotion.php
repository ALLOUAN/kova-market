<?php

namespace App\Models;

use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['title', 'description', 'image', 'location_label', 'url', 'is_visible', 'position', 'starts_at', 'ends_at'])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory, LogsActivity;

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
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Visible promotions that have not ended yet, in the back-office order, then soonest first.
     */
    #[Scope]
    protected function current(Builder $query): Builder
    {
        return $query->where('is_visible', true)->where('ends_at', '>=', now())
            ->orderBy('position')->orderBy('starts_at')->orderBy('id');
    }
}
