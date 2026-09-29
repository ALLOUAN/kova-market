<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's review of a product they received (verified purchase: one per order line). Shown on the site once the
 * back-office approves it; the product's rating and review count follow the approved reviews.
 */
#[Fillable(['product_id', 'user_id', 'order_item_id', 'author_name', 'rating', 'comment', 'status', 'moderated_by', 'moderated_at'])]
class ProductReview extends Model
{
    protected static function booted(): void
    {
        static::saved(fn (ProductReview $review) => $review->product?->refreshRating());
        static::deleted(fn (ProductReview $review) => $review->product?->refreshRating());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', ReviewStatus::Approved);
    }

    public function moderate(ReviewStatus $status, User $by): void
    {
        $this->update(['status' => $status, 'moderated_by' => $by->getKey(), 'moderated_at' => now()]);
    }
}
