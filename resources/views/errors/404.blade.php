@extends('layouts.storefront')

@section('title', 'Page introuvable')

@push('meta')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    {{-- A broken link keeps the visitor in the store: search, shop and home at hand. --}}
    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 text-center">
                    <p class="h1 mb--8" style="color: var(--kova-gold); font-size: 64px">404</p>
                    <h1 class="h3 mb--16">Cette page est introuvable</h1>
                    <p class="mb--32">Le lien est peut-être ancien, ou le produit n’est plus en vente. Cherchez ce qu’il vous faut ou reprenez depuis la boutique.</p>
                    <form action="{{ route('shop.index') }}" method="get" class="d-flex gap-2 mb--24 mx-auto" style="max-width: 480px" role="search">
                        <label for="not-found-search" class="visually-hidden">Rechercher un produit</label>
                        <input id="not-found-search" type="search" name="q" class="rbt-input-field flex-grow-1" placeholder="Rechercher un produit…">
                        <button type="submit" class="rbt-btn rbt-btn-secondary" style="width: auto; padding: 0 20px">Rechercher</button>
                    </form>
                    <div class="d-flex justify-content-center flex-wrap gap-3">
                        <a class="rbt-btn" style="width: auto; padding: 0 24px" href="{{ route('shop.index') }}">Voir la boutique</a>
                        <a class="rbt-btn rbt-btn-border" style="width: auto; padding: 0 24px" href="{{ route('home') }}">Retour à l’accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
