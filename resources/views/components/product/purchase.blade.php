@props(['product', 'variants', 'options', 'prefix' => 'product', 'open' => null])

@php
    $default = $product->variants->first();
    $rules = $product->saleQuantity();
@endphp

{{-- Price, stock, variant selector and cart form of a product (F-035, F-036); storefront.js keeps them in step
     with the selected variant. Used by the product page and the quick view, hence the id prefix. --}}
<div data-purchase data-variants='@json($variants)' data-limited-stock="{{ config('storefront.product_card.limited_stock_threshold') }}">
    <div class="pricing-part mb--16" data-product-price>
        <del class="price-text" data-compare @if (! $default?->currentComparePrice()) hidden @endif>{{ $default?->currentComparePrice() ? \App\Support\Money::format($default->currentComparePrice()) : '' }}</del>
        <span class="price-text h4" data-price>{{ \App\Support\Money::format($default?->currentPrice() ?? $product->price) }}</span>
        @if ($rules->priceSuffix())
            <span class="kova-price-unit">{{ $rules->priceSuffix() }}</span>
        @endif
    </div>
    @if ($rules->unit->isMeasured())
        <p class="b4 mb--8"><i class="fa-regular fa-scale-balanced mr--4"></i> Vendu {{ $rules->unit === \App\Enums\SaleUnit::Kilogram ? 'au poids' : 'au volume' }}, par {{ $rules->format($rules->step()) }} à partir de {{ $rules->format($rules->minimum()) }}.</p>
    @endif
    <p class="b3 mb--8" data-stock></p>
    <p class="b4 mb--24">Réf. : <span data-sku>{{ $default?->sku }}</span></p>

    @foreach ($options as $attribute => $values)
        <fieldset class="mb--16">
            <legend class="b2 rbt-text-bold mb--8">{{ $attribute }}</legend>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($values as $value)
                    <input type="radio" class="btn-check" name="{{ $prefix }}-attribute-{{ $value->attribute_id }}" id="{{ $prefix }}-value-{{ $value->id }}" value="{{ $value->id }}" data-variant-option @checked($default?->attributeValues->contains($value))>
                    <label class="rbt-btn rbt-btn-border rbt-btn-sm" for="{{ $prefix }}-value-{{ $value->id }}">
                        @if ($value->color_hex)
                            <span class="d-inline-block rounded-circle border align-middle mr--4" style="width: 14px; height: 14px; background: {{ $value->color_hex }}"></span>
                        @endif
                        {{ $value->value }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach

    {{-- The selected variant goes to the cart; "Acheter maintenant" continues to the cart page. --}}
    <form method="POST" action="{{ route('cart.items.store') }}" class="d-flex flex-wrap align-items-center gap-3 mt--24">
        @csrf
        <input type="hidden" name="variant_id" value="{{ $default?->id }}" data-variant-id>
        @if ($open)
            <input type="hidden" name="open" value="{{ $open }}">
        @endif
        <label class="visually-hidden" for="{{ $prefix }}-quantity">Quantité</label>
        <x-product.quantity-field :rules="$rules" :id="$prefix.'-quantity'" :stock="$default?->stock"
            :price="$product->variants->count() === 1 ? $default?->currentPrice() : null"
            :style="$rules->unit->isMeasured() ? 'width: auto; min-width: 190px' : 'width: 90px'" data-quantity />
        @if ($rules->unit !== \App\Enums\SaleUnit::Piece && ! $rules->unit->isMeasured())
            <span class="b3">{{ $rules->unit->symbol($rules->localLabel) }}</span>
        @endif
        <button type="submit" class="rbt-btn" data-buy @disabled(! $default || $default->stock === 0)>Ajouter au panier</button>
        <button type="submit" name="buy_now" value="1" class="rbt-btn rbt-btn-border" data-buy @disabled(! $default || $default->stock === 0)>Acheter maintenant</button>
    </form>

    {{-- Shown while the selected variant is sold out (EX-17). --}}
    <div class="rbt-bg-color-gray-light rbt-radius p-3 mt--16" data-stock-alert @if (! $default || $default->stock > 0) hidden @endif>
        <p class="b2 rbt-text-bold mb--8"><i class="fa-regular fa-bell mr--4"></i> Épuisé : soyez prévenu de son retour</p>
        <x-product.stock-alert-form :product="$product" :variant-id="$default?->id" :prefix="$prefix.'-stock-alert'" />
    </div>
</div>
