@extends('layouts.storefront')

@section('title', 'Mes commandes')

@section('content')
    <x-account-layout title="Mes commandes">
        @forelse ($orders as $order)
            <a href="{{ route('account.orders.show', $order) }}" class="d-block border rbt-radius p-3 mb--12">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $order->number }}</strong>
                    <span class="rbt-text-bold">{{ $order->status->getLabel() }}</span>
                </div>
                <p class="b3 mb-0">{{ $order->created_at->format('d/m/Y') }} · {{ $order->itemCount() }} {{ Str::plural('article', $order->itemCount()) }} · @money($order->total) · Livraison à {{ $order->commune_name }}</p>
            </a>
        @empty
            <p>Vous n’avez pas encore passé de commande.</p>
            <a class="rbt-btn" href="{{ route('shop.index') }}">Découvrir la boutique</a>
        @endforelse

        <div class="mt--24">{{ $orders->links() }}</div>
    </x-account-layout>
@endsection
