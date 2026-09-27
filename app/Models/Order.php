<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer order (F-050). Every amount and delivery detail is a copy taken when the order was placed.
 */
#[Fillable([
    'number', 'user_id', 'status', 'payment_method', 'payment_status', 'source',
    'customer_name', 'phone', 'email', 'commune_id', 'commune_name', 'zone_name', 'district', 'landmark', 'note',
    'subtotal', 'shipping_fee', 'discount', 'total', 'marketing_opt_in', 'terms_accepted_at',
])]
class Order extends Model
{
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'marketing_opt_in' => 'boolean',
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->oldest('id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->oldest('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function formattedPhone(): string
    {
        return PhoneNumber::format($this->phone);
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
