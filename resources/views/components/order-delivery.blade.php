@props(['order'])

{{-- F-127: planned delivery date and, while the order is on its way, who delivers it and how to reach them. --}}
@php
    $courier = in_array($order->status, \App\Models\Courier::OPEN_STATUSES, true) ? $order->courier : null;
@endphp

@if (($order->delivery_date && $order->status !== \App\Enums\OrderStatus::Delivered) || $courier)
    <div class="kova-delivery-card">
        <span class="kova-delivery-card__avatar" aria-hidden="true">
            @if ($courier?->photo)
                <img src="{{ asset($courier->photo) }}" alt="" width="52" height="52">
            @else
                <i class="fa-regular {{ $courier ? 'fa-person-biking' : 'fa-calendar-check' }}"></i>
            @endif
        </span>
        <div class="kova-delivery-card__text">
            @if ($order->delivery_date && $order->status !== \App\Enums\OrderStatus::Delivered)
                <p class="kova-delivery-card__date">Livraison prévue le {{ $order->delivery_date->translatedFormat('l j F') }}</p>
            @endif
            @if ($courier)
                <p class="mb-0">Votre livreur : {{ $courier->name() }}</p>
            @endif
        </div>
        @if ($courier)
            <a class="rbt-btn rbt-btn-sm" href="tel:{{ $courier->user->phone }}"><i class="fa-regular fa-phone mr--4"></i>{{ $courier->formattedPhone() }}</a>
        @endif
    </div>
@endif
