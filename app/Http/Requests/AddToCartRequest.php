<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Adding to the cart (F-036), from the storefront and the API: product pages send the chosen variant,
 * product cards send the product (its single, default variant).
 */
class AddToCartRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'variant_id' => ['required_without:product_id', 'integer', 'exists:product_variants,id'],
            'product_id' => ['required_without:variant_id', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.CartManager::MAX_QUANTITY],
        ];
    }

    public function variant(): ProductVariant
    {
        return $this->filled('variant_id')
            ? ProductVariant::with('product')->findOrFail($this->validated('variant_id'))
            : ProductVariant::with('product')->where('product_id', $this->validated('product_id'))->where('is_default', true)->firstOrFail();
    }

    public function quantity(): int
    {
        return (int) ($this->validated('quantity') ?? 1);
    }
}
