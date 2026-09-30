@extends('layouts.storefront')

@section('title', 'Comparer des produits')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-page-header title="Comparer des produits" />

    <div class="rbt-section-gap2">
        <div class="container">
            @if ($products->count() >= 1)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb--24">
                    <p class="mb-0">{{ $products->count() }} {{ $products->count() > 1 ? 'produits comparés' : 'produit' }} (jusqu’à {{ \App\Services\Storefront\Comparison::MAX }}).</p>
                    <form method="POST" action="{{ route('compare.clear') }}" class="m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rbt-btn rbt-btn-border rbt-btn-sm" style="width: auto; padding: 0 16px">Vider le comparateur</button>
                    </form>
                </div>

                {{-- One column per product; the first column names the rows. Scrolls sideways on small screens. --}}
                <div class="kova-compare-table-wrap">
                    <table class="kova-compare-table">
                        <thead>
                            <tr>
                                <th scope="col"><span class="visually-hidden">Caractéristique</span></th>
                                @foreach ($products as $product)
                                    <th scope="col">
                                        <form method="POST" action="{{ route('compare.toggle', $product) }}" class="kova-compare-remove">
                                            @csrf
                                            <button type="submit" class="rbt-round-btn" aria-label="Retirer « {{ $product->name }} » du comparateur"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                        </form>
                                        <a href="{{ $product->url() }}"><img src="{{ asset($product->image) }}" alt="" loading="lazy"></a>
                                        <a href="{{ $product->url() }}" class="kova-compare-name">{{ $product->name }}</a>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">Prix</th>
                                @foreach ($products as $product)
                                    <td>
                                        <span class="kova-compare-price">@money($product->price)@if ($product->price_max) – @money($product->price_max)@endif{{ $product->saleQuantity()->priceSuffix() }}</span>
                                        @if ($product->isOnSale())<br><del>@money($product->compare_at_price)</del> <span class="rbt-offer-badge">-{{ $product->discountPercentage() }}%</span>@endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row">Disponibilité</th>
                                @foreach ($products as $product)
                                    <td>{!! $product->isSoldOut() ? '<span class="kova-compare-no">Épuisé</span>' : ($product->hasLimitedStock() ? 'Stock limité' : '<span class="kova-compare-yes">En stock</span>') !!}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row">Avis clients</th>
                                @foreach ($products as $product)
                                    <td>{{ $product->reviews_count > 0 ? number_format($product->rating, 1, ',', ' ').' / 5 ('.$product->reviews_count.' avis)' : '—' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row">Marque</th>
                                @foreach ($products as $product)
                                    <td>{{ $product->brand?->name ?? '—' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row">Catégorie</th>
                                @foreach ($products as $product)
                                    <td>{{ $product->category?->name ?? '—' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row">Options</th>
                                @foreach ($products as $product)
                                    <td>{{ $product->variants->count() > 1 ? $product->variants->count().' choix ('.$product->variants->map->label()->filter()->take(4)->join(', ').($product->variants->count() > 4 ? '…' : '').')' : 'Modèle unique' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row">Livraison</th>
                                @foreach ($products as $product)
                                    <td>{{ $product->free_shipping ? 'Offerte' : 'Selon la commune' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row">Retour</th>
                                @foreach ($products as $product)
                                    <td>{{ $product->return_days ? $product->return_days.' jours pour changer d’avis' : '—' }}</td>
                                @endforeach
                            </tr>
                            @foreach ($labels as $label)
                                <tr>
                                    <th scope="row">{{ $label }}</th>
                                    @foreach ($products as $product)
                                        <td>{{ \App\Services\Storefront\Comparison::specification($product, $label) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr>
                                <th scope="row"><span class="visually-hidden">Acheter</span></th>
                                @foreach ($products as $product)
                                    <td><x-product.actions :product="$product" /></td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if ($products->count() === 1)
                    <p class="text-center mt--24">Ajoutez un autre produit avec le bouton <i class="fa-regular fa-code-compare" aria-hidden="true"></i> des cartes produit pour le comparer.</p>
                @endif
            @else
                <div class="text-center py-5">
                    <p class="h1 mb--16" style="color: var(--kova-gold)"><i class="fa-regular fa-code-compare" aria-hidden="true"></i></p>
                    <h2 class="h4 mb--8">Aucun produit à comparer</h2>
                    <p class="mb--24">Touchez le bouton <i class="fa-regular fa-code-compare" aria-hidden="true"></i> sur 2 à {{ \App\Services\Storefront\Comparison::MAX }} produits pour les voir côte à côte : prix, disponibilité, caractéristiques.</p>
                    <a class="rbt-btn" style="width: auto; padding: 0 24px" href="{{ route('shop.index') }}">Parcourir la boutique</a>
                </div>
            @endif
        </div>
    </div>
@endsection
