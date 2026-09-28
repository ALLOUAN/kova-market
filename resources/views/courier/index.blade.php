@extends('courier.layout')

@section('title', 'Mes livraisons')
@section('heading', 'Bonjour '.Str::before($courier->name(), ' '))

@section('content')
    <div class="grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 8px">
        <div class="card" style="margin: 0">
            <div class="muted">Livrées aujourd’hui</div>
            <div class="big">{{ $deliveredToday }}</div>
        </div>
        <div class="card" style="margin: 0">
            <div class="muted">À reverser</div>
            <div class="big">@money($cashToHandOver)</div>
        </div>
    </div>

    <h2>Mes livraisons ({{ $mine->count() }})</h2>
    @forelse ($mine as $order)
        <a class="card" href="{{ route('courier.orders.show', $order) }}">
            <div class="row">
                <strong>{{ $order->commune_name }} · {{ $order->district }}</strong>
                <span @class(['badge', 'warn' => $order->status === \App\Enums\OrderStatus::OutForDelivery])>{{ $order->status->getLabel() }}</span>
            </div>
            <div class="muted">{{ $order->number }} · {{ $order->customer_name }}</div>
            @if ($order->amountToCollect() > 0)
                <div style="margin-top: 4px">À encaisser : <strong>@money($order->amountToCollect())</strong></div>
            @endif
        </a>
    @empty
        <p class="muted">Aucune livraison en cours.</p>
    @endforelse

    <h2>À prendre dans mes zones ({{ $queue->count() }})</h2>
    @forelse ($queue as $order)
        <div class="card">
            <div class="row">
                <strong>{{ $order->commune_name }} · {{ $order->district }}</strong>
                <span class="badge">{{ $order->status->getLabel() }}</span>
            </div>
            <div class="muted" style="margin-bottom: 10px">{{ $order->number }} · {{ $order->itemCount() }} article(s) · @money($order->total)</div>
            <form method="POST" action="{{ route('courier.orders.accept', $order) }}">
                @csrf
                <button type="submit" class="btn">Je prends cette livraison</button>
            </form>
        </div>
    @empty
        <p class="muted">Aucune commande en attente dans vos zones.</p>
    @endforelse

    <p style="margin-top: 32px"><a href="{{ route('courier.password.edit') }}">Changer mon mot de passe</a></p>
@endsection
