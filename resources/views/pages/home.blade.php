@extends('layouts.storefront')

{{-- The whole browser and Google title of the home page, set in Paramètres de la boutique. --}}
@section('document_title', config('storefront.home_title') ?: config('storefront.name').' - Boutique en ligne à Abidjan')

@push('meta')
    <x-json-ld :data="app(\App\Services\Storefront\StructuredData::class)->organization()" />
@endpush

@section('content')
    <h1 class="visually-hidden">{{ config('storefront.name') }} - Boutique en ligne à Abidjan</h1>

    {{-- Sections in the order and with the visibility set in Paramètres de la boutique › Page d'accueil. A section
         without content published in the back-office is left out, never filled with sample content. --}}
    @foreach ($sections as $section)
        @switch($section)
            @case('hero')
                @if ($hero)
                    @include('pages.home.hero')
                @endif
                @break
            @case('guarantees')
                @include('pages.home.guarantees')
                @break
            @case('categories')
                @if ($categories->isNotEmpty() || $banners['categories'])
                    @include('pages.home.popular-categories')
                @endif
                @break
            @case('deals_of_the_day')
                @if ($dealsOfTheDay)
                    @include('pages.home.deals-of-the-day')
                @endif
                @break
            @case('best_deals')
                @if ($bestDeals)
                    @include('pages.home.best-deals')
                @endif
                @break
            @case('new_arrivals')
                @include('pages.home.product-row', ['row' => $newArrivals, 'listId' => 'new_arrivals'])
                @break
            @case('highlights')
                @if ($highlights)
                    @include('pages.home.highlights')
                @endif
                @break
            @case('popular')
                @include('pages.home.product-row', ['row' => $popular, 'listId' => 'popular'])
                @break
            @case('featured')
                @if ($featuredProduct)
                    @include('pages.home.featured-products')
                @endif
                @break
            @case('brands')
                @if ($brands->isNotEmpty())
                    @include('pages.home.brands')
                @endif
                @break
            @case('closing')
                @if ($banners['closing'])
                    @include('pages.home.closing-banner')
                @endif
                @break
        @endswitch
    @endforeach
@endsection
