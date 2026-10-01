@props(['order', 'vertical' => false])

{{-- Order progress (F-073): the steps of the normal flow with the date each was reached — across on wide screens,
     down on phones. Shared by the tracking page and the customer's order page. --}}
@php
    use App\Enums\OrderStatus;

    $reached = $order->statusHistory->groupBy(fn ($entry) => $entry->to_status->value)->map->last();
    $steps = [
        [OrderStatus::Received, 'fa-receipt'],
        [OrderStatus::Confirmed, 'fa-phone'],
        [OrderStatus::Preparing, 'fa-box-open'],
        [OrderStatus::Shipped, 'fa-warehouse'],
        [OrderStatus::OutForDelivery, 'fa-truck-fast'],
        [OrderStatus::Delivered, 'fa-house-circle-check'],
    ];
    $currentIndex = array_search($order->status, array_column($steps, 0), true);
@endphp

@if ($order->status === OrderStatus::Cancelled)
    <div class="kova-timeline-cancelled" role="status">
        <i class="fa-regular fa-circle-xmark" aria-hidden="true"></i>
        Cette commande a été annulée le {{ $reached->get(OrderStatus::Cancelled->value)?->created_at->format('d/m/Y à H:i') }}.
    </div>
@else
    <ol @class(['kova-timeline', 'kova-timeline--vertical' => $vertical])>
        @foreach ($steps as $index => [$step, $icon])
            @php
                $done = $currentIndex !== false && $index < $currentIndex;
                $current = $index === $currentIndex;
            @endphp
            <li @class(['is-done' => $done || ($current && $step === OrderStatus::Delivered), 'is-current' => $current && $step !== OrderStatus::Delivered])>
                <span class="kova-timeline__dot" aria-hidden="true">
                    <i class="fa-regular {{ $done || ($current && $step === OrderStatus::Delivered) ? 'fa-check' : $icon }}"></i>
                </span>
                <span class="kova-timeline__label">{{ $step->getLabel() }}@if ($current)<span class="visually-hidden"> (étape actuelle)</span>@endif</span>
                @if ($entry = $reached->get($step->value))
                    <span class="kova-timeline__date">{{ $entry->created_at->format('d/m/Y à H:i') }}</span>
                @endif
            </li>
        @endforeach
    </ol>
@endif
