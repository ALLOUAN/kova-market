<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\AnonymousNotifiable;

/**
 * Back-in-stock alert (EX-17). Without a variant, any variant of the product coming back is enough.
 * Contact details are kept 90 days after the alert is sent, 1 year at most while waiting.
 */
#[Fillable(['product_id', 'product_variant_id', 'user_id', 'phone', 'email'])]
class StockAlert extends Model
{
    use Prunable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Alerts still waiting for this variant: the ones on the variant itself and the ones on its whole product.
     */
    #[Scope]
    protected function waitingFor(Builder $query, ProductVariant $variant): Builder
    {
        return $query->whereNull('notified_at')
            ->where('product_id', $variant->product_id)
            ->where(fn (Builder $query) => $query->whereNull('product_variant_id')->orWhere('product_variant_id', $variant->getKey()));
    }

    public function recipient(): AnonymousNotifiable
    {
        $recipient = new AnonymousNotifiable;

        if ($this->phone) {
            $recipient->route('sms', $this->phone);
        }

        return $this->email ? $recipient->route('mail', $this->email) : $recipient;
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where(fn (Builder $query) => $query->where('notified_at', '<', now()->subDays(90))
                ->orWhere('created_at', '<', now()->subYear()));
    }
}
