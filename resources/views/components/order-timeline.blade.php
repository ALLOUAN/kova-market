@props(['order'])

{{-- Order progress (F-073): the steps of the normal flow with the date each was reached. --}}
@php
    use App\Enums\OrderStatus;

    $reached = $order->statusHistory->groupBy(fn ($entry) => $entry->to_status->value)->map->last();
    $steps = [OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Shipped, OrderStatus::OutForDelivery, OrderStatus::Delivered];
    $currentIndex = array_search($order->status, $steps, true);
@endphp

@if ($order->status === OrderStatus::Cancelled)
    <div class="alert alert-danger mb-0" role="status">
        Cette commande a été annulée le {{ $reached->get(OrderStatus::Cancelled->value)?->created_at->format('d/m/Y à H:i') }}.
    </div>
@else
    <ol class="list-unstyled mb-0">
        @foreach ($steps as $index => $step)
            @php($done = $currentIndex !== false && $index <= $currentIndex)
            <li class="d-flex align-items-start gap-3 mb--12">
                <span @class(['d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0', 'text-white' => $done, 'border' => ! $done]) style="width: 28px; height: 28px; @if ($done) background: var(--color-primary); @endif">
                    @if ($done)<i class="fa-solid fa-check" aria-hidden="true"></i>@else{{ $index + 1 }}@endif
                </span>
                <div>
                    <p @class(['mb-0', 'rbt-text-bold' => $index === $currentIndex])>{{ $step->getLabel() }}</p>
                    @if ($entry = $reached->get($step->value))
                        <p class="b4 mb-0">{{ $entry->created_at->format('d/m/Y à H:i') }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
