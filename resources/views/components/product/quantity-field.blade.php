@props(['rules', 'id', 'value' => null, 'stock' => null, 'price' => null, 'min' => null])

{{-- Quantity of a product in its sale unit (App\Support\SaleQuantity), posted in base units ("quantity").
     By the piece, pack, lot or local unit: a number field. By weight or volume: the quantities it can be bought in
     (0,25 kg, 0,5 kg…), with their price when known, computed here from the price per unit. --}}
@php
    $value ??= $rules->minimum();
@endphp

@if ($rules->unit->isMeasured())
    @php
        $ceiling = $rules->normalize(PHP_INT_MAX, $stock) ?: $rules->minimum();
        // A long list stays usable: at most 200 choices.
        $step = max($rules->step(), (int) (ceil(($ceiling - $rules->minimum()) / $rules->step() / 200) * $rules->step()));
        $choices = range($rules->minimum(), max($rules->minimum(), $ceiling), $step);
        if (! in_array($value, $choices, true)) {
            $choices[] = $value;
            sort($choices);
        }
    @endphp
    <select id="{{ $id }}" name="quantity" {{ $attributes->class(['form-select kova-quantity-select']) }}>
        @if ($min === 0)
            <option value="0">Retirer</option>
        @endif
        @foreach ($choices as $choice)
            <option value="{{ $choice }}" @selected($choice === $value)>{{ $rules->format($choice) }}@if ($price !== null) — @money($rules->lineTotal($price, $choice))@endif</option>
        @endforeach
    </select>
@else
    <input id="{{ $id }}" type="number" name="quantity" value="{{ $value }}" min="{{ $min ?? $rules->minimum() }}" step="{{ $rules->step() }}"
        max="{{ max($rules->minimum(), min($rules->maximum(), $stock ?? $rules->maximum())) }}" {{ $attributes->class(['rbt-input-field text-center']) }}>
@endif
