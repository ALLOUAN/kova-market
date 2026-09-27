<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * A customer as the back-office sees it (F-108), read from the `customers` view: an account (id = user id) or a
 * guest grouped by phone number (negative id). Never written: accounts change through the customer area.
 */
class Customer extends Model
{
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'orders_count' => 'integer',
            'total_spent' => 'integer',
            'created_at' => 'datetime',
            'last_order_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * The customer's orders: those of the account, plus any order placed with its phone number.
     *
     * @return Builder<Order>
     */
    public function orders(): Builder
    {
        return Order::query()
            ->where(fn (Builder $query) => $query
                ->when($this->user_id, fn (Builder $query) => $query->orWhere('user_id', $this->user_id))
                ->when($this->phone, fn (Builder $query) => $query->orWhere('phone', $this->phone)))
            ->latest('id');
    }

    public function formattedPhone(): ?string
    {
        return $this->phone ? PhoneNumber::format($this->phone) : null;
    }

    public function save(array $options = []): bool
    {
        throw new LogicException('Customers are read from a view and cannot be saved.');
    }
}
