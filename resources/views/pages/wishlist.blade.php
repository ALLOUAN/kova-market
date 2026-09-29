@extends('layouts.storefront')

@section('title', 'Mes favoris')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-page-header title="Mes favoris" />

    <div class="rbt-section-gap2">
        <div class="container">
            @if ($products->isNotEmpty())
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb--24">
                    <p class="mb-0">{{ $products->count() }} {{ $products->count() > 1 ? 'produits' : 'produit' }} dans vos favoris.</p>
                    @guest
                        <p class="b3 mb-0">
                            <a href="#!" data-bs-toggle="modal" data-bs-target="#signinModal">Connectez-vous</a> pour les retrouver sur tous vos appareils.
                        </p>
                    @endguest
                </div>
                <div class="row row--12 mt_dec--24">
                    @foreach ($products as $product)
                        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-6 col-6 mt--24" data-wishlist-card>
                            <x-product.card :product="$product" :order="$loop->iteration % 4 + 1" shadow />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <p class="h1 mb--16" style="color: var(--kova-gold)"><i class="fa-regular fa-heart" aria-hidden="true"></i></p>
                    <h2 class="h4 mb--8">Vous n’avez pas encore de favori</h2>
                    <p class="mb--24">Touchez le cœur d’un produit pour le garder ici et le retrouver plus tard.</p>
                    <a class="rbt-btn" style="width: auto; padding: 0 24px" href="{{ route('shop.index') }}">Parcourir la boutique</a>
                </div>
            @endif
        </div>
    </div>
@endsection
