@props(['product' => null, 'variantId' => null, 'prefix' => 'stock-alert'])

@php
    $user = auth()->user();
    $contact = $user?->email ?? ($user?->phone ? \App\Support\PhoneNumber::format($user->phone) : null);
@endphp

{{-- "Me prévenir" (EX-17): phone number or e-mail, sent once when the product (or the chosen variant) is back. --}}
<form method="POST" action="{{ route('stock-alerts.store') }}" {{ $attributes }}>
    @csrf
    <input type="hidden" name="product_id" value="{{ $product?->id }}" data-stock-alert-product>
    <input type="hidden" name="variant_id" value="{{ $variantId }}" data-stock-alert-variant>
    <label class="b3 mb--8 d-block" for="{{ $prefix }}-contact">Téléphone ou e-mail</label>
    <input id="{{ $prefix }}-contact" class="rbt-input-field rbt-bg-color-white shadow-none plr--24" type="text" name="contact" value="{{ $contact }}" placeholder="07 01 02 03 04 ou vous@exemple.ci" maxlength="255" autocomplete="on" required>
    <button type="submit" class="rbt-btn rbt-btn-rounded w-100 mt--12">Me prévenir</button>
    <p class="b4 mt--8 mb-0">Un seul message, au retour en stock. Vos coordonnées ne servent qu’à cela.</p>
</form>
