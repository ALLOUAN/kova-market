<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * How a product is sold, for API clients: quantities (stock, cart, order) are whole numbers of base units — grams
 * for "kg", millilitres for "litre", units otherwise — and "factor" converts them to the displayed unit.
 *
 * @mixin Product
 */
class SaleUnitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rules = $this->saleQuantity();

        return [
            'code' => $rules->unit->value,
            'symbol' => $rules->unit->symbol($rules->localLabel),
            'factor' => $rules->unit->factor(),
            'minimum' => $rules->minimum(),
            'step' => $rules->step(),
            'maximum' => $rules->maximum(),
            'price_suffix' => $rules->priceSuffix(),
        ];
    }
}
