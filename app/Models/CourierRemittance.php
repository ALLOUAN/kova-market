<?php

namespace App\Models;

use App\Enums\RemittanceMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Cash a courier handed over to the store (F-126), spread over their oldest orders not settled yet. Never deleted:
 * a mistake is cancelled with its reason, and the orders it covered owe their cash again.
 */
#[Fillable(['courier_id', 'amount', 'balance_after', 'method', 'reference', 'received_at', 'received_by', 'note'])]
class CourierRemittance extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'method' => RemittanceMethod::class,
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by')->withTrashed();
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by')->withTrashed();
    }

    /**
     * The orders this payment covers, with the part of each one.
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)->withPivot('amount');
    }

    /**
     * Payments that count: not cancelled.
     */
    #[Scope]
    protected function valid(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Number shown on the receipt, e.g. "VER-000012".
     */
    public function number(): string
    {
        return 'VER-'.str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }
}
