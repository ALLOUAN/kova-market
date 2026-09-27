<?php

namespace App\Models;

use App\Enums\CourierTransport;
use App\Enums\OrderStatus;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Courier profile (F-122): the account is a user with the "livreur" role; name, phone and suspension live on it.
 */
#[Fillable(['user_id', 'transport', 'photo'])]
class Courier extends Model
{
    use LogsActivity;

    /** Statuses of an order a courier still has to finish. */
    public const OPEN_STATUSES = [OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Shipped, OrderStatus::OutForDelivery];

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
            'transport' => CourierTransport::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryZone::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Orders assigned to the courier and not finished yet.
     */
    public function openOrders(): HasMany
    {
        return $this->orders()->whereIn('status', self::OPEN_STATUSES);
    }

    /**
     * Couriers who may receive orders: account not suspended.
     */
    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $query) => $query->whereNull('suspended_at'));
    }

    public function name(): string
    {
        return $this->user->name;
    }

    public function formattedPhone(): string
    {
        return PhoneNumber::format($this->user->phone);
    }

    public function isSuspended(): bool
    {
        return $this->user->suspended_at !== null;
    }
}
