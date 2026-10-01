@extends('layouts.storefront')

@section('title', 'Mes favoris')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-account-layout title="Mes favoris">
        @if ($products->isNotEmpty())
            <p class="kova-dash-note mb--16">{{ $products->count() }} {{ $products->count() > 1 ? 'produits' : 'produit' }} dans vos favoris, retrouvés sur tous vos appareils.</p>
            <div class="row row--12 mt_dec--24">
                @foreach ($products as $product)
                    <div class="col-xl-4 col-lg-6 col-md-4 col-6 mt--24" data-wishlist-card>
                        <x-product.card :product="$product" :order="$loop->iteration % 4 + 1" shadow />
                    </div>
                @endforeach
            </div>
        @else
            <section class="kova-dash-card">
                <div class="kova-dash-empty">
                    <span class="kova-dash-empty__icon kova-dash-stat__icon--rose"><i class="fa-regular fa-heart"></i></span>
                    <p class="kova-dash-empty__title">Vous n’avez pas encore de favori</p>
                    <p class="kova-dash-empty__text">Touchez le cœur d’un produit pour le garder ici et le retrouver plus tard.</p>
                    <a class="rbt-btn rbt-btn-sm" href="{{ route('shop.index') }}">Parcourir la boutique</a>
                </div>
            </section>
        @endif
    </x-account-layout>
@endsection
