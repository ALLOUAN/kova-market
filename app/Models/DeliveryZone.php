<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Delivery zone: a fee in whole FCFA and an indicative delay for the communes it groups (F-120), delivered by
 * courier in Abidjan or shipped to the interior of the country (delivery_mode).
 */
#[Fillable(['name', 'fee', 'min_order', 'free_shipping_threshold', 'delay_label', 'delivery_days', 'delivery_mode', 'is_active', 'position'])]
class DeliveryZone extends Model
{
    use LogsActivity;

    /** ISO weekdays, as stored in delivery_days. */
    public const DAYS = [1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche'];

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
            'fee' => 'integer',
            'min_order' => 'integer',
            'free_shipping_threshold' => 'integer',
            'delivery_days' => 'array',
            'delivery_mode' => DeliveryMode::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Amount of goods from which delivery is free: the zone's own, else the store's general one (Paramètres).
     */
    public function freeShippingThreshold(): ?int
    {
        if ($this->free_shipping_threshold !== null) {
            return $this->free_shipping_threshold;
        }

        $general = Setting::get('delivery.free_shipping_threshold');

        return filled($general) ? (int) $general : null;
    }

    /**
     * Delivery fee for this amount of goods: free once the threshold is reached.
     */
    public function feeFor(int $subtotal): int
    {
        $threshold = $this->freeShippingThreshold();

        return $threshold !== null && $subtotal > 0 && $subtotal >= $threshold ? 0 : (int) $this->fee;
    }

    /**
     * What is still missing to reach the zone's minimum order, null when there is none or it is reached.
     */
    public function missingForMinimum(int $subtotal): ?int
    {
        return $this->min_order !== null && $subtotal < $this->min_order ? $this->min_order - $subtotal : null;
    }

    /**
     * Weekdays the zone is delivered (ISO numbers, Monday first); empty means every day.
     *
     * @return list<int>
     */
    public function deliveryDays(): array
    {
        $days = array_values(array_unique(array_map('intval', $this->delivery_days ?? [])));
        sort($days);

        return count($days) === 7 ? [] : array_values(array_filter($days, fn (int $day) => isset(self::DAYS[$day])));
    }

    /**
     * "lundi, mercredi et vendredi", or null when the zone is delivered every day.
     */
    public function deliveryDaysLabel(): ?string
    {
        $names = array_map(fn (int $day) => self::DAYS[$day], $this->deliveryDays());

        return match (count($names)) {
            0 => null,
            1 => $names[0],
            default => implode(', ', array_slice($names, 0, -1)).' et '.end($names),
        };
    }

    /**
     * The zone's conditions in one line for the customer, e.g. "Livraison le lundi et le jeudi · commande minimum
     * 10 000 FCFA · offerte dès 50 000 FCFA"; null when there are none.
     */
    public function conditionsLabel(): ?string
    {
        $parts = array_filter([
            ($days = $this->deliveryDaysLabel()) ? "Livraison le {$days}" : null,
            $this->min_order ? 'commande minimum '.Money::format($this->min_order) : null,
            $this->free_shipping_threshold !== null ? 'livraison offerte dès '.Money::format($this->free_shipping_threshold) : null,
        ]);

        return $parts ? ucfirst(implode(' · ', $parts)) : null;
    }

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class)->orderBy('position')->orderBy('name');
    }

    /**
     * Couriers who deliver the zone (F-122).
     */
    public function couriers(): BelongsToMany
    {
        return $this->belongsToMany(Courier::class);
    }

    /**
     * Deliverable: switched on and priced.
     */
    public function isDeliverable(): bool
    {
        return $this->is_active && $this->fee !== null;
    }

    public function isInterior(): bool
    {
        return $this->delivery_mode === DeliveryMode::Interior;
    }
}
