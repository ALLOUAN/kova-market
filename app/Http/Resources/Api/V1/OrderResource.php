<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * An order seen by the customer who placed it: the tracking view plus the delivery details, amounts and lines.
 *
 * @mixin Order
 */
class OrderResource extends TrackedOrderResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'payment_status' => ['code' => $this->payment_status->value, 'label' => $this->payment_status->getLabel()],
            'source' => $this->source,
            'customer_name' => $this->customer_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'zone_name' => $this->zone_name,
            'district' => $this->district,
            'landmark' => $this->landmark,
            'note' => $this->note,
            'subtotal' => $this->subtotal,
            'shipping_fee' => $this->shipping_fee,
            'discount' => $this->discount,
            'coupon_code' => $this->coupon_code,
            'items' => OrderItemResource::collection($this->items),
        ];
    }
}
