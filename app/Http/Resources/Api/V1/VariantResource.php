<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AttributeValue;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A variant the customer can pick (F-035): the price it costs now and its stock.
 *
 * @mixin ProductVariant
 */
class VariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'label' => $this->label(),
            'price' => $this->currentPrice(),
            'compare_at_price' => $this->currentComparePrice(),
            'stock' => $this->stock,
            'is_default' => $this->is_default,
            'attributes' => $this->attributeValues->map(fn (AttributeValue $value) => [
                'attribute' => $value->attribute->name,
                'value_id' => $value->id,
                'value' => $value->value,
            ])->values(),
        ];
    }
}
