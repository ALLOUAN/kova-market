@extends('layouts.storefront')

@php
    use App\Services\Storefront\ProductListing;

    $seoTitle = $category?->meta_title ?: $brand?->meta_title ?: $title;
    $trail = collect($breadcrumb)->mapWithKeys(fn ($ancestor) => [$ancestor->name => $ancestor->url()])->all();
    if ($category || $brand || $filters['q'] !== '') {
        $trail = ['Boutique' => route('shop.index'), ...$trail];
    }
    $activeFilters = $filters['min'] || $filters['max'] || $filters['in_stock'] || $filters['brands'] || $filters['values'] || $filters['categories'];
@endphp

@section('title', $seoTitle)
@section('description', $category?->meta_description ?: $brand?->meta_description ?: $category?->tagline ?: config('storefront.description'))

@section('content')
    <x-page-header :title="$title" :trail="$trail" />

    <div class="rbt-section-gap2">
        <div class="container">
            @if ($category?->children->isNotEmpty())
                <ul class="d-flex flex-wrap gap-2 list-unstyled mb--32" aria-label="Sous-catégories">
                    @foreach ($category->children as $child)
                        <li><a class="rbt-btn rbt-btn-border rbt-btn-sm" href="{{ $child->url() }}">{{ $child->name }}</a></li>
                    @endforeach
                </ul>
            @endif

            <div class="row g-5">
                {{-- Filters (F-023): a GET form, so every choice stays in the URL. --}}
                <aside class="col-lg-3">
                    <button class="rbt-btn rbt-btn-border w-100 d-lg-none mb--16" type="button" data-bs-toggle="collapse" data-bs-target="#catalog-filters" aria-expanded="false" aria-controls="catalog-filters">
                        <i class="fa-regular fa-sliders mr--8"></i> Filtres
                    </button>
                    <form id="catalog-filters" class="collapse d-lg-block" method="GET" action="{{ url()->current() }}">
                        @if ($filters['q'] !== '')
                            <input type="hidden" name="q" value="{{ $filters['q'] }}">
                        @endif
                        <input type="hidden" name="tri" value="{{ $filters['sort'] }}">

                        @if ($facets['categories']->isNotEmpty() && ! $category)
                            <fieldset class="mb--24">
                                <legend class="h6 mb--12">Catégories</legend>
                                @foreach ($facets['categories'] as $option)
                                    <div class="rbt-check-group mb--8">
                                        <input type="checkbox" id="filter-category-{{ $option->id }}" name="categories[]" value="{{ $option->id }}" @checked(in_array($option->id, $filters['categories'], true))>
                                        <label for="filter-category-{{ $option->id }}">{{ $option->name }}</label>
                                    </div>
                                @endforeach
                            </fieldset>
                        @endif

                        <fieldset class="mb--24">
                            <legend class="h6 mb--12">Prix (FCFA)</legend>
                            <div class="d-flex gap-2">
                                <input class="rbt-input-field" type="number" min="0" step="500" name="prix_min" value="{{ $filters['min'] }}" placeholder="{{ $facets['price']['min'] }}" aria-label="Prix minimum">
                                <input class="rbt-input-field" type="number" min="0" step="500" name="prix_max" value="{{ $filters['max'] }}" placeholder="{{ $facets['price']['max'] }}" aria-label="Prix maximum">
                            </div>
                        </fieldset>

                        <fieldset class="mb--24">
                            <legend class="h6 mb--12">Disponibilité</legend>
                            <div class="rbt-check-group">
                                <input type="checkbox" id="filter-in-stock" name="en_stock" value="1" @checked($filters['in_stock'])>
                                <label for="filter-in-stock">En stock uniquement</label>
                            </div>
                        </fieldset>

                        @if ($facets['brands']->isNotEmpty() && ! $brand)
                            <fieldset class="mb--24">
                                <legend class="h6 mb--12">Marques</legend>
                                @foreach ($facets['brands'] as $option)
                                    <div class="rbt-check-group mb--8">
                                        <input type="checkbox" id="filter-brand-{{ $option->id }}" name="marques[]" value="{{ $option->id }}" @checked(in_array($option->id, $filters['brands'], true))>
                                        <label for="filter-brand-{{ $option->id }}">{{ $option->name }}</label>
                                    </div>
                                @endforeach
                            </fieldset>
                        @endif

                        @foreach ($facets['attributes'] as $attribute)
                            <fieldset class="mb--24">
                                <legend class="h6 mb--12">{{ $attribute->name }}</legend>
                                @foreach ($attribute->values as $value)
                                    <div class="rbt-check-group mb--8">
                                        <input type="checkbox" id="filter-value-{{ $value->id }}" name="valeurs[]" value="{{ $value->id }}" @checked(in_array($value->id, $filters['values'], true))>
                                        <label for="filter-value-{{ $value->id }}">
                                            @if ($value->color_hex)
                                                <span class="d-inline-block rounded-circle border align-middle mr--4" style="width: 14px; height: 14px; background: {{ $value->color_hex }}"></span>
                                            @endif
                                            {{ $value->value }}
                                        </label>
                                    </div>
                                @endforeach
                            </fieldset>
                        @endforeach

                        <button type="submit" class="rbt-btn w-100">Appliquer les filtres</button>
                        @if ($activeFilters)
                            <a class="d-block text-center mt--12 b3" href="{{ url()->current().($filters['q'] !== '' ? '?q='.urlencode($filters['q']) : '') }}">Effacer les filtres</a>
                        @endif
                    </form>
                </aside>

                <div class="col-lg-9">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb--24">
                        <p class="mb-0 b2">{{ $products->total() }} {{ Str::plural('produit', $products->total()) }}</p>
                        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
                            @foreach (request()->except(['tri', 'page']) as $name => $value)
                                @foreach ((array) $value as $item)
                                    <input type="hidden" name="{{ is_array($value) ? $name.'[]' : $name }}" value="{{ $item }}">
                                @endforeach
                            @endforeach
                            <label for="catalog-sort" class="b3 mb-0 text-nowrap">Trier par</label>
                            <select id="catalog-sort" name="tri" class="form-select" onchange="this.form.submit()">
                                @foreach (ProductListing::SORTS as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <noscript><button type="submit" class="rbt-btn rbt-btn-sm">OK</button></noscript>
                        </form>
                    </div>

                    @if ($products->isEmpty())
                        <div class="text-center py-5">
                            <p class="h5 mb--16">Aucun produit ne correspond à votre recherche.</p>
                            <p class="mb--24">Essayez avec moins de filtres ou parcourez nos catégories.</p>
                            <a class="rbt-btn" href="{{ route('shop.index') }}">Voir tous les produits</a>
                        </div>
                    @else
                        <div class="row row--12 mt_dec--24">
                            @foreach ($products as $product)
                                <div class="col-xl-4 col-md-6 col-6 mt--24">
                                    <x-product.card :product="$product" :order="$loop->iteration % 4 + 1" shadow />
                                </div>
                            @endforeach
                        </div>

                        <div class="mt--40 d-flex justify-content-center">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
