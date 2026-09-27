<?php

namespace App\Models;

use App\Enums\BannerPlacement;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Home page banner (F-010). Displayed while visible and within its optional date window.
 */
#[Fillable([
    'placement', 'image', 'subtitle', 'highlight', 'title', 'tagline', 'badge', 'price', 'compare_at_price', 'url',
    'position', 'starts_at', 'ends_at', 'is_visible',
])]
class Banner extends Model
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
            'placement' => BannerPlacement::class,
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_visible' => 'boolean',
        ];
    }

    #[Scope]
    protected function live(Builder $query): Builder
    {
        return $query
            ->where('is_visible', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Shape expected by the home page partials (same keys as config/homepage.php).
     *
     * @return array<string, mixed>
     */
    public function toStorefront(): array
    {
        return $this->only([
            'image', 'subtitle', 'highlight', 'title', 'tagline', 'badge', 'price', 'compare_at_price', 'url',
        ]);
    }
}
