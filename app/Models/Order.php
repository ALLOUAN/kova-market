<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer order (F-050). Every amount and delivery detail is a copy taken when the order was placed.
 */
#[Fillable([
    'number', 'user_id', 'status', 'payment_method', 'payment_status', 'source',
    'customer_name', 'phone', 'email', 'commune_id', 'commune_name', 'zone_name', 'delivery_mode', 'destination_city', 'district', 'landmark', 'note',
    'subtotal', 'shipping_fee', 'discount', 'coupon_code', 'total', 'marketing_opt_in', 'terms_accepted_at',
])]
class Order extends Model
{
    use SoftDeletes;

    /** Same default as the column: an order is delivered in Abidjan unless shipped to the interior. */
    protected $attributes = [
        'delivery_mode' => 'abidjan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'delivery_mode' => DeliveryMode::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'marketing_opt_in' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'tracking' => 'array',
            'assigned_at' => 'datetime',
            'delivery_date' => 'date',
            'cash_collected' => 'integer',
            'cash_remitted' => 'integer',
            'cash_settled_at' => 'datetime',
        ];
    }

    /**
     * Courier delivering the order (F-123); set by the dispatch, never by a form.
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function cashSettledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cash_settled_by')->withTrashed();
    }

    /**
     * Courier's payments covering this order's cash (F-126), with the part of each one.
     */
    public function remittances(): BelongsToMany
    {
        return $this->belongsToMany(CourierRemittance::class)->withPivot('amount');
    }

    /**
     * Cash the courier must collect at the door (F-125): the total of an unpaid cash-on-delivery order.
     */
    public function amountToCollect(): int
    {
        return $this->payment_method === PaymentMethod::CashOnDelivery && $this->payment_status !== PaymentStatus::Paid ? $this->total : 0;
    }

    /**
     * Route to the customer in Google Maps (courier app): landmark, district, commune.
     */
    public function mapsUrl(): string
    {
        $place = collect([$this->landmark, $this->district, $this->commune_name, 'Côte d’Ivoire'])->filter()->join(', ');

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($place);
    }

    /**
     * WhatsApp conversation with the customer, opened with the courier's greeting (courier app).
     */
    public function whatsappUrl(): string
    {
        return 'https://wa.me/'.ltrim((string) $this->phone, '+').'?text='.rawurlencode('Bonjour '.$this->customer_name.', je suis le livreur '.config('storefront.name').' pour votre commande '.$this->number.'.');
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * Public tracking (F-073): the order matching a number and the phone given when it was placed, however typed.
     */
    public static function findForTracking(string $number, string $phone): ?self
    {
        return static::query()
            ->where('number', strtoupper(trim($number)))
            ->where('phone', PhoneNumber::normalize($phone) ?? '')
            ->with(['items', 'statusHistory', 'courier.user'])
            ->first();
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

    /**
     * Online payment attempts (F-060 to F-067), latest first.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * An online order still waiting for its payment, which the customer can (re)try.
     */
    public function awaitsOnlinePayment(): bool
    {
        return $this->payment_method === PaymentMethod::Online
            && $this->payment_status === PaymentStatus::Pending
            && $this->status === OrderStatus::Received;
    }

    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function isInterior(): bool
    {
        return $this->delivery_mode === DeliveryMode::Interior;
    }

    /**
     * Steps of the normal flow for this order: no "Expédiée" in Abidjan, no "En livraison" towards the interior.
     *
     * @return list<OrderStatus>
     */
    public function flow(): array
    {
        return $this->delivery_mode->flow();
    }

    /**
     * @return list<OrderStatus>
     */
    public function nextStatuses(): array
    {
        return $this->status->next($this->delivery_mode);
    }

    public function canBecome(OrderStatus $status): bool
    {
        return $this->status->canBecome($status, $this->delivery_mode);
    }

    public function previousStatus(): ?OrderStatus
    {
        return $this->status->previous($this->delivery_mode);
    }

    /**
     * Where the order goes: the commune in Abidjan, "Ville (intérieur)" otherwise.
     */
    public function destinationLabel(): string
    {
        return $this->isInterior() ? "{$this->destination_city} (intérieur)" : $this->commune_name;
    }

    public function formattedPhone(): string
    {
        return PhoneNumber::format($this->phone);
    }

    public function itemCount(): int
    {
        // Articles, not grams: a line sold by weight or volume counts as one.
        return (int) $this->items->sum(fn (OrderItem $item) => $item->soldUnits());
    }
}
