@extends('courier.layout')

@php
    use App\Enums\OrderStatus;

    $place = collect([$order->district, $order->commune_name, 'Côte d’Ivoire'])->join(', ');
    $maps = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(($order->landmark ? $order->landmark.', ' : '').$place);
    $whatsapp = 'https://wa.me/'.ltrim($order->phone, '+').'?text='.rawurlencode('Bonjour '.$order->customer_name.', je suis le livreur '.config('storefront.name').' pour votre commande '.$order->number.'.');
    $toCollect = $order->amountToCollect();
@endphp

@section('title', $order->number)
@section('heading', $order->number)

@section('content')
    <p><a href="{{ route('courier.home') }}">← Mes livraisons</a></p>

    <div class="card">
        <div class="row">
            <span class="big">{{ $order->customer_name }}</span>
            <span class="badge">{{ $order->status->getLabel() }}</span>
        </div>
        <p style="margin: 8px 0 0"><strong>{{ $order->district }}</strong>, {{ $order->commune_name }}</p>
        @if ($order->landmark)
            <p style="margin: 4px 0 0">Repère : {{ $order->landmark }}</p>
        @endif
        @if ($order->note)
            <p class="muted" style="margin: 8px 0 0">Note du client : {{ $order->note }}</p>
        @endif
    </div>

    @if ($isMine)
        {{-- One tap to reach the customer or open the route (F-125). --}}
        <div class="grid">
            <a class="btn secondary" href="tel:{{ $order->phone }}"><span aria-hidden="true">📞</span>Appeler</a>
            <a class="btn whatsapp" href="{{ $whatsapp }}" target="_blank" rel="noopener"><span aria-hidden="true">💬</span>WhatsApp</a>
            <a class="btn secondary" href="{{ $maps }}" target="_blank" rel="noopener"><span aria-hidden="true">🧭</span>Itinéraire</a>
        </div>
        <p class="muted" style="text-align: center">{{ $order->formattedPhone() }}</p>
    @endif

    <div class="card">
        <div class="row">
            <span>{{ $toCollect > 0 ? 'À encaisser' : 'Déjà payée' }}</span>
            <span class="big">@money($toCollect > 0 ? $toCollect : $order->total)</span>
        </div>
        <div class="muted">{{ $order->payment_method->getLabel() }}</div>
    </div>

    <details class="card">
        <summary>Articles ({{ $order->itemCount() }})</summary>
        <ul class="plain">
            @foreach ($order->items as $item)
                <li>
                    {{ $item->quantityLabel() }} × {{ $item->product_name }}
                    @if ($item->variant_label)<span class="muted">({{ $item->variant_label }})</span>@endif
                    @if ($item->contentsSummary())<div class="muted">{{ $item->contentsSummary() }}</div>@endif
                </li>
            @endforeach
        </ul>
    </details>

    @if ($isMine && in_array(OrderStatus::Delivered, $steps, true))
        {{-- F-125: a failed delivery needs its reason; the order is then cancelled and its stock put back. --}}
        <details class="card">
            <summary style="color: var(--danger)">Livraison impossible ?</summary>
            <form method="POST" action="{{ route('courier.orders.fail', $order) }}">
                @csrf
                <label for="reason">Motif</label>
                <select id="reason" name="reason" required>
                    <option value="">Choisir</option>
                    @foreach (['Client injoignable', 'Client absent', 'Commande refusée par le client', 'Adresse introuvable', 'Client sans l’argent'] as $reason)
                        <option>{{ $reason }}</option>
                    @endforeach
                </select>
                @error('reason')<p class="error">{{ $message }}</p>@enderror
                <button type="submit" class="btn danger" style="margin-top: 12px" onclick="return confirm('Confirmer l’échec de la livraison ?')">Déclarer l’échec</button>
            </form>
        </details>
    @endif
@endsection

@section('actions')
    <div class="actions">
        <div class="inner">
            @if (! $isMine)
                <form method="POST" action="{{ route('courier.orders.accept', $order) }}">
                    @csrf
                    <button type="submit" class="btn">Je prends cette livraison</button>
                </form>
            @elseif (in_array(OrderStatus::OutForDelivery, $steps, true))
                <form method="POST" action="{{ route('courier.orders.start', $order) }}">
                    @csrf
                    <button type="submit" class="btn">Je pars livrer</button>
                </form>
            @elseif (in_array(OrderStatus::Delivered, $steps, true))
                <form method="POST" action="{{ route('courier.orders.deliver', $order) }}">
                    @csrf
                    @if ($toCollect > 0)
                        <label for="cash_collected" style="margin-top: 0">Montant encaissé (FCFA)</label>
                        <input id="cash_collected" name="cash_collected" type="number" inputmode="numeric" min="0" value="{{ old('cash_collected', $toCollect) }}" required>
                        @error('cash_collected')<p class="error">{{ $message }}</p>@enderror
                    @endif
                    <button type="submit" class="btn ok" style="margin-top: 8px">Commande livrée</button>
                </form>
            @else
                <p class="muted" style="margin: 0; text-align: center">
                    @switch($order->status)
                        @case(OrderStatus::Delivered)
                            Livraison terminée{{ $order->cash_collected !== null ? ' · '.\App\Support\Money::format($order->cash_collected).' encaissés' : '' }}.
                            @break
                        @case(OrderStatus::Cancelled)
                            Commande annulée.
                            @break
                        @default
                            La commande est en préparation : vous pourrez partir une fois qu’elle sera expédiée.
                    @endswitch
                </p>
            @endif
        </div>
    </div>
@endsection
