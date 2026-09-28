@extends('layouts.storefront')

@section('title', 'Accueil')

@push('meta')
    <x-json-ld :data="app(\App\Services\Storefront\StructuredData::class)->organization()" />
@endpush

@section('content')
    <h1 class="visually-hidden">{{ config('storefront.name') }} - Boutique en ligne à Abidjan</h1>

    @include('pages.home.hero')
    @include('pages.home.popular-categories')

    @if ($dealsOfTheDay)
        @include('pages.home.deals-of-the-day')
    @endif

    @if ($bestDeals)
        @include('pages.home.best-deals')
    @endif

    @if ($highlights)
        @include('pages.home.highlights')
    @endif

    @if ($featuredProduct)
        @include('pages.home.featured-products')
    @endif

    @include('pages.home.brands')
    @include('pages.home.closing-banner')
@endsection
