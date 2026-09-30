@extends('layouts.storefront')

@php
    $withProducts = $rows->filter(fn ($row) => $row['count'] > 0);
    $allProducts = route('categories.show', $category);
@endphp

@section('title', $category->meta_title ?: $market->title())
@section('description', $category->meta_description ?: $market->subtitle())

@push('meta')
    <x-json-ld :data="app(\App\Services\Storefront\StructuredData::class)->breadcrumbs([$market->title() => url()->current()])" />
@endpush

@section('content')
    {{-- Banner: the picture set in Paramètres › Mon Marché, else the night blue of the logo. --}}
    <section @class(['kova-market-hero', 'kova-market-hero--image' => $market->banner()])
        @if ($market->banner()) style="background-image: url('{{ asset($market->banner()) }}')" @endif>
        <div class="container">
            <nav class="kova-market-hero__trail" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a> <span aria-hidden="true">/</span> {{ $market->title() }}</nav>
            <div class="kova-market-hero__content">
                <span class="kova-market-hero__icon" aria-hidden="true"><i class="{{ $category->icon ?: 'fa-regular fa-basket-shopping' }}"></i></span>
                <h1>{{ $market->title() }}</h1>
                <p>{{ $market->subtitle() }}</p>
                <div class="kova-market-hero__actions">
                    <a class="rbt-btn" href="{{ $allProducts }}">Tous les produits du marché</a>
                    @if ($weighedCount > 0)
                        <a class="kova-market-hero__secondary" href="{{ $allProducts }}?vente=poids"><i class="fa-regular fa-scale-balanced mr--8"></i>Vendus au poids ({{ $weighedCount }})</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- The market's categories, as shortcuts. --}}
    <section class="kova-market-rayons">
        <div class="container">
            <ul class="kova-market-rayons__list" aria-label="Rayons de {{ $market->title() }}">
                @foreach ($category->children as $rayon)
                    <li>
                        <a href="{{ $rayon->url() }}" class="kova-market-rayon">
                            <span class="kova-market-rayon__icon" aria-hidden="true">
                                @if ($rayon->image)
                                    <img src="{{ asset($rayon->image) }}" alt="" loading="lazy" decoding="async">
                                @else
                                    <i class="{{ $rayon->icon ?: 'fa-regular fa-tag' }}"></i>
                                @endif
                            </span>
                            <span class="kova-market-rayon__name">{{ $rayon->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <div class="kova-market-body">
        <div class="container">
            @forelse ($withProducts as $row)
                <section class="kova-market-row" aria-labelledby="rayon-{{ $row['category']->id }}">
                    <div class="kova-market-row__head">
                        <div>
                            <h2 id="rayon-{{ $row['category']->id }}" class="h4 mb-0"><i class="{{ $row['category']->icon ?: 'fa-regular fa-tag' }} mr--8"></i>{{ $row['category']->name }}</h2>
                            @if ($row['category']->children->isNotEmpty())
                                <p class="kova-market-row__subs">
                                    @foreach ($row['category']->children as $sub)
                                        <a href="{{ $sub->url() }}">{{ $sub->name }}</a>@if (! $loop->last) · @endif
                                    @endforeach
                                </p>
                            @endif
                        </div>
                        <a class="kova-market-row__all" href="{{ $row['category']->url() }}">Tout voir ({{ $row['count'] }}) <i class="fa-regular fa-arrow-right ml--4"></i></a>
                    </div>
                    <div class="row row--12 mt_dec--24">
                        @foreach ($row['products'] as $product)
                            <div class="col-xl-3 col-md-4 col-6 mt--24">
                                <x-product.card :product="$product" :order="$loop->iteration % 4 + 1" shadow />
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="kova-market-empty">
                    <span class="kova-category-icon kova-category-icon--lg" aria-hidden="true"><i class="fa-regular fa-basket-shopping"></i></span>
                    <h2 class="h4">Les étals se remplissent</h2>
                    <p>Nos produits du marché arrivent très bientôt. En attendant, découvrez le reste de la boutique.</p>
                    <a class="rbt-btn" href="{{ route('shop.index') }}">Voir toute la boutique</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
