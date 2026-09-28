@props(['order'])

{{-- F-127: planned delivery date and, while the order is on its way, who delivers it and how to reach them. --}}
@php
    $courier = in_array($order->status, \App\Models\Courier::OPEN_STATUSES, true) ? $order->courier : null;
@endphp

@if ($order->delivery_date || $courier)
    <div class="d-flex flex-wrap align-items-center gap-3 mt--16 p-3 rbt-radius bg-white">
        @if ($courier?->photo)
            <img src="{{ asset($courier->photo) }}" alt="" width="48" height="48" class="rounded-circle" style="object-fit: cover">
        @endif
        <div class="flex-grow-1">
            @if ($order->delivery_date && $order->status !== \App\Enums\OrderStatus::Delivered)
                <p class="b2 mb--4"><strong>Livraison prévue le {{ $order->delivery_date->translatedFormat('l j F') }}</strong></p>
            @endif
            @if ($courier)
                <p class="b3 mb-0">Votre livreur : {{ $courier->name() }}</p>
            @endif
        </div>
        @if ($courier)
            <a class="rbt-btn rbt-btn-sm" href="tel:{{ $courier->user->phone }}"><i class="fa-regular fa-phone mr--4"></i>{{ $courier->formattedPhone() }}</a>
        @endif
    </div>
@endif
