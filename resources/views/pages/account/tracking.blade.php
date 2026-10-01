@extends('layouts.storefront')

@section('title', 'Suivi de mes commandes')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-account-layout title="Suivi de mes commandes">
        @forelse ($orders as $order)
            <section class="kova-dash-card mb--20">
                <div class="kova-dash-card__head flex-wrap">
                    <div>
                        <h2 class="kova-dash-card__title">Commande {{ $order->number }}</h2>
                        <p class="kova-dash-note mb-0 mt--4">Passée le {{ $order->created_at->translatedFormat('j F Y') }} · {{ $order->items_count }} {{ Str::plural('article', $order->items_count) }} · @money($order->total)</p>
                    </div>
                    <span class="kova-pill kova-pill--{{ $order->status->getColor() }}">{{ $order->status->getLabel() }}</span>
                </div>
                <x-order-timeline :order="$order" />
                <x-order-delivery :order="$order" />
                <a class="kova-dash-card__more d-inline-block mt--16" href="{{ route('account.orders.show', $order) }}">Voir le détail de la commande <i class="fa-regular fa-arrow-right"></i></a>
            </section>
        @empty
            <section class="kova-dash-card">
                <div class="kova-dash-empty">
                    <span class="kova-dash-empty__icon"><i class="fa-regular fa-truck-fast"></i></span>
                    <p class="kova-dash-empty__title">Aucune commande en cours</p>
                    <p class="kova-dash-empty__text">Vos commandes livrées ou annulées restent dans « Mes commandes ».</p>
                    <a class="rbt-btn rbt-btn-sm" href="{{ route('account.orders') }}">Voir mes commandes</a>
                </div>
            </section>
        @endforelse

        <p class="kova-dash-note mt--20"><i class="fa-regular fa-circle-info"></i>Une commande passée sans compte ou avec un autre numéro ? <a href="{{ route('tracking.show') }}">Suivez-la avec son numéro</a></p>
    </x-account-layout>
@endsection
