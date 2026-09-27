<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shopping cart of a guest (cookie token) or of a customer (F-040). Guest carts expire after 30 days
 * without activity and are pruned by the scheduler.
 */
#[Fillable(['token', 'user_id', 'commune_id', 'expires_at'])]
class Cart extends Model
{
    use Prunable;

    public const LIFETIME_DAYS = 30;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->oldest('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    /**
     * Expired guest carts; a customer's cart is kept with the account.
     */
    public function prunable(): Builder
    {
        return static::query()->whereNull('user_id')->where('expires_at', '<', now());
    }
}
