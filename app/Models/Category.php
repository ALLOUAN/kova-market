<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOldSlugs;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['parent_id', 'name', 'slug', 'icon', 'tagline', 'badge_label', 'badge_variant', 'image', 'promo', 'is_featured', 'position', 'meta_title', 'meta_description'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, LogsActivity, RedirectsOldSlugs;

    protected static function booted(): void
    {
        // An untouched promo block from the back-office form means "no promo", not an empty one.
        static::saving(function (Category $category): void {
            if (is_array($category->promo) && array_filter($category->promo, filled(...)) === []) {
                $category->promo = null;
            }
        });
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
            'promo' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Ids of the category and of all its sub-categories (the tree has three levels at most).
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [$this->getKey()];
        $level = [$this->getKey()];

        while ($level !== []) {
            $level = static::query()->whereIn('parent_id', $level)->pluck('id')->all();
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }

    /**
     * Root-first chain of ancestors, the category included (breadcrumb).
     *
     * @return list<Category>
     */
    public function ancestry(): array
    {
        $chain = [$this];

        while ($chain[0]->parent) {
            array_unshift($chain, $chain[0]->parent);
        }

        return $chain;
    }

    /**
     * Public listing of the category ("#" until the category page route is registered).
     */
    public function url(): string
    {
        return Route::has('categories.show') ? route('categories.show', $this) : '#';
    }

    #[Scope]
    protected function roots(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('position')->orderBy('id');
    }

    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
