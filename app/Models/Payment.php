<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One online payment attempt of an order at CinetPay (F-060 to F-067), with the journal of what CinetPay answered
 * (F-066). Addressed in URLs by our reference (merchant_transaction_id), never by its numeric id.
 */
#[Fillable([
    'order_id', 'provider', 'merchant_transaction_id', 'gateway_transaction_id', 'payment_token', 'notify_token_hash',
    'payment_url', 'amount', 'currency', 'status', 'operator', 'payer_phone', 'failure_reason', 'events', 'paid_at',
    'refunded_at', 'refunded_by', 'refund_reason',
])]
class Payment extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'amount' => 'integer',
            'events' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'merchant_transaction_id';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by')->withTrashed();
    }

    /**
     * Money received on an order cancelled since: to give back in CinetPay's merchant space, then record.
     */
    #[Scope]
    protected function toRefund(Builder $query): void
    {
        $query->where('status', TransactionStatus::Succeeded)
            ->whereHas('order', fn (Builder $order) => $order->where('status', OrderStatus::Cancelled));
    }

    /**
     * Adds a line to the journal: what happened, and what CinetPay said (codes and ids, no secret).
     *
     * @param  array<string, mixed>  $details
     */
    public function record(string $event, array $details = []): void
    {
        $this->events = [...($this->events ?? []), ['at' => now()->toIso8601String(), 'event' => $event, ...$details]];
    }

    /**
     * What identifies the payment at CinetPay for a status check.
     */
    public function gatewayIdentifier(): string
    {
        return $this->payment_token ?: ($this->gateway_transaction_id ?: $this->merchant_transaction_id);
    }

    /**
     * The operator as customers know it (CinetPay gives a code: "OM", "MOMO", "FLOOZ"…).
     */
    public function operatorLabel(): ?string
    {
        if (blank($this->operator)) {
            return null;
        }

        $code = strtoupper((string) $this->operator);

        return match (true) {
            str_starts_with($code, 'OM'), str_contains($code, 'ORANGE') => 'Orange Money',
            str_starts_with($code, 'MOMO'), str_contains($code, 'MTN') => 'MTN MoMo',
            str_starts_with($code, 'FLOOZ'), str_contains($code, 'MOOV') => 'Moov Money',
            str_contains($code, 'WAVE') => 'Wave',
            str_contains($code, 'CARD'), str_contains($code, 'VISA'), str_contains($code, 'MASTER') => 'carte bancaire',
            default => $this->operator,
        };
    }

    public function notifyTokenMatches(?string $token): bool
    {
        return filled($token) && filled($this->notify_token_hash) && hash_equals($this->notify_token_hash, hash('sha256', $token));
    }
}
