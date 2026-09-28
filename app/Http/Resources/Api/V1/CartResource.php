<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Cart\CartLine;
use App\Services\Cart\CartSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The cart and what it costs now (F-040 to F-044). A guest cart carries its token, to send back in the
 * X-Cart-Token header; a customer's cart is found through the account.
 *
 * @property CartSummary $resource
 */
class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = $this->resource;
        $cart = $summary->cart;

        return [
            'token' => $cart && ! $cart->user_id ? $cart->token : null,
            'lines' => $summary->lines->map(fn (CartLine $line) => [
                'id' => $line->item->id,
                'variant_id' => $line->variant->id,
                'product' => [
                    'id' => $line->product->id,
                    'name' => $line->product->name,
                    'slug' => $line->product->slug,
                    'image' => $line->product->image ? asset($line->product->image) : null,
                ],
                'variant_label' => $line->variant->attributeValues->isEmpty() ? null : $line->variant->label(),
                'unit_price' => $line->unitPrice(),
                'quantity' => $line->item->quantity,
                'orderable_quantity' => $line->quantity,
                'available' => $line->available,
                'notice' => $line->notice,
                'total' => $line->total(),
            ])->values(),
            'item_count' => $summary->count(),
            'subtotal' => $summary->subtotal,
            'coupon' => $summary->coupon ? [
                'code' => $summary->coupon->code,
                'applies' => $summary->couponApplies(),
                'issue' => $summary->couponIssue,
            ] : null,
            'discount' => $summary->discount,
            'commune' => $summary->commune ? CommuneResource::make($summary->commune) : null,
            'shipping_fee' => $summary->shippingFee,
            'free_shipping' => $summary->freeShipping || $summary->hasFreeShippingCoupon(),
            'missing_for_free_shipping' => $summary->missingForFreeShipping(),
            'total' => $summary->total(),
        ];
    }
}
