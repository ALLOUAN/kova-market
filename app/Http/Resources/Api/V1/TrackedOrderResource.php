<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Courier;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public tracking of an order (F-073, F-127): its progress, planned date and courier, the commune only. Never the
 * address details, the phone number or the e-mail: whoever knows the number and phone sees no more than this.
 *
 * @mixin Order
 */
class TrackedOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $courier = in_array($this->status, Courier::OPEN_STATUSES, true) ? $this->courier : null;

        return [
            'number' => $this->number,
            'placed_at' => $this->created_at->toIso8601String(),
            'status' => ['code' => $this->status->value, 'label' => $this->status->getLabel()],
            'history' => $this->statusHistory->map(fn (OrderStatusHistory $entry) => [
                'status' => $entry->to_status->value,
                'label' => $entry->to_status->getLabel(),
                'at' => $entry->created_at->toIso8601String(),
            ])->values(),
            'item_count' => $this->itemCount(),
            'total' => $this->total,
            'payment_method' => ['code' => $this->payment_method->value, 'label' => $this->payment_method->getLabel()],
            // The app polls it after the customer paid on CinetPay's page (F-060).
            'payment_status' => ['code' => $this->payment_status->value, 'label' => $this->payment_status->getLabel()],
            'commune_name' => $this->commune_name,
            'delivery_date' => $this->delivery_date?->toDateString(),
            'courier' => $courier ? [
                'name' => $courier->name(),
                'phone' => $courier->user->phone,
                'photo' => $courier->photo ? asset($courier->photo) : null,
            ] : null,
        ];
    }
}
