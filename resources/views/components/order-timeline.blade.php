@props(['order', 'vertical' => false])

{{-- Order progress (F-073): the steps of the order's own flow with the date each was reached — across on wide
     screens, down on phones. In Abidjan there is no "Expédiée" step, towards the interior no "En livraison".
     Shared by the tracking page and the customer's order page. --}}
@php
    use App\Enums\OrderStatus;

    $reached = $order->statusHistory->groupBy(fn ($entry) => $entry->to_status->value)->map->last();
    $icons = [
        OrderStatus::Received->value => 'fa-receipt',
        OrderStatus::Confirmed->value => 'fa-phone',
        OrderStatus::Preparing->value => 'fa-box-open',
        OrderStatus::Shipped->value => 'fa-truck-ramp-box',
        OrderStatus::OutForDelivery->value => 'fa-truck-fast',
        OrderStatus::Delivered->value => 'fa-house-circle-check',
    ];
    $steps = array_map(fn (OrderStatus $status) => [$status, $icons[$status->value]], $order->flow());
    $currentIndex = array_search($order->status, array_column($steps, 0), true);
    // An Abidjan order left "Expédiée" by the former flow: shown as ready to leave.
    if ($currentIndex === false && $order->status === OrderStatus::Shipped) {
        $currentIndex = array_search(OrderStatus::Preparing, array_column($steps, 0), true) + 1;
    }
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
