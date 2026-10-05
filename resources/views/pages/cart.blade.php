@extends('layouts.storefront')

@section('title', 'Panier')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-page-header title="Votre panier" />

    <div class="rbt-section-gap2">
        <div class="container">
            @if ($summary->isEmpty())
                <div class="text-center py-5">
                    <p class="h5 mb--16">Votre panier est vide.</p>
                    <a class="rbt-btn" href="{{ route('shop.index') }}">Découvrir la boutique</a>
                </div>
            @else
                <div class="row g-5">
                    <div class="col-lg-8">
                        <ul class="list-unstyled mb-0">
                            @foreach ($summary->lines as $line)
                                <li class="d-flex flex-wrap gap-3 align-items-center border-bottom py-3">
                                    <a href="{{ $line->product->url() }}" class="flex-shrink-0 rbt-bg-color-gray-light rbt-radius p-2" style="width: 88px">
                                        <img class="img-fluid" src="{{ asset($line->product->image) }}" alt="{{ $line->product->name }}">
                                    </a>
                                    <div class="flex-grow-1" style="min-width: 180px">
                                        <a class="h6 d-block mb--4" href="{{ $line->product->url() }}">{{ $line->product->name }}</a>
                                        @if ($line->variant->attributeValues->isNotEmpty())
                                            <p class="b4 mb--4">{{ $line->variant->label() }}</p>
                                        @endif
                                        <p class="b3 mb-0">@money($line->unitPrice()){{ $line->saleQuantity()->priceSuffix() ?: ' l’unité' }}</p>
                                        @if ($line->notice)
                                            <p class="b4 mt--4 mb-0 rbt-text-color-danger">{{ $line->notice }}</p>
                                        @endif
                                    </div>
                                    @if ($line->available)
                                        <form method="POST" action="{{ route('cart.items.update', $line->item->id) }}" class="d-flex align-items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <label class="visually-hidden" for="quantity-{{ $line->item->id }}">Quantité de {{ $line->product->name }}</label>
                                            <x-product.quantity-field :rules="$line->saleQuantity()" :id="'quantity-'.$line->item->id" :value="$line->quantity"
                                                :stock="$line->variant->stock" :price="$line->unitPrice()" :min="0"
                                                :style="$line->saleQuantity()->unit->isMeasured() ? 'width: auto; min-width: 190px' : 'width: 80px'" />
                                            <button type="submit" class="rbt-btn rbt-btn-sm rbt-btn-border">Mettre à jour</button>
                                        </form>
                                    @endif
                                    <p class="mb-0 rbt-text-bold text-end" style="min-width: 110px">@money($line->total())</p>
                                    <form method="POST" action="{{ route('cart.items.destroy', $line->item->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rbt-round-btn" aria-label="Retirer {{ $line->product->name }}"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                        <a class="d-inline-block mt--24" href="{{ route('shop.index') }}"><i class="fa-regular fa-arrow-left mr--4"></i> Continuer mes achats</a>
                    </div>

                    <aside class="col-lg-4">
                        <div class="rbt-bg-color-gray-light rbt-radius p-4">
                            <h2 class="h5 mb--16">Récapitulatif</h2>

                            {{-- F-043: the commune gives the delivery fee. --}}
                            <form method="POST" action="{{ route('cart.commune') }}" class="mb--16">
                                @csrf
                                <label for="cart-commune" class="b3 mb--8 d-block">Destination de livraison</label>
                                <p class="b4 mb--8">À Abidjan, votre commune. Ailleurs en Côte d’Ivoire, « Intérieur » : vous indiquerez votre ville à la commande.</p>
                                <div class="d-flex gap-2">
                                    <select id="cart-commune" name="commune_id" class="form-select" required onchange="this.form.submit()">
                                        <option value="">Choisir votre commune</option>
                                        @foreach ($communes as $zone => $zoneCommunes)
                                            <optgroup label="{{ $zone }} — @money($zoneCommunes->first()->zone->fee){{ $zoneCommunes->first()->zone->delay_label ? ', '.$zoneCommunes->first()->zone->delay_label : '' }}">
                                                @foreach ($zoneCommunes as $commune)
                                                    <option value="{{ $commune->id }}" @selected($summary->commune?->is($commune))>{{ $commune->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <noscript><button type="submit" class="rbt-btn rbt-btn-sm">OK</button></noscript>
                                </div>
                                @if ($communes->isEmpty())
                                    <p class="b4 mt--8 mb-0">Les zones de livraison seront bientôt disponibles.</p>
                                @elseif ($summary->commune?->zone->delay_label)
                                    <p class="b4 mt--8 mb-0">Délai indicatif : {{ $summary->commune->zone->delay_label }}</p>
                                @endif
                            </form>

                            {{-- F-042: one promo code per cart, checked again at every change. --}}
                            <div class="mb--16">
                                @if ($summary->coupon)
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <p class="b3 mb-0">Code <strong>{{ $summary->coupon->code }}</strong> · {{ $summary->coupon->benefitLabel() }}</p>
                                        <form method="POST" action="{{ route('cart.coupon.destroy') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="b3 p-0 border-0 bg-transparent text-decoration-underline">Retirer</button>
                                        </form>
                                    </div>
                                    @if ($summary->couponIssue)
                                        <p class="b4 mt--4 mb-0 rbt-text-color-danger">{{ $summary->couponIssue }}</p>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('cart.coupon.store') }}">
                                        @csrf
                                        <label for="coupon-code" class="b3 mb--8 d-block">Code promo</label>
                                        <div class="d-flex gap-2">
                                            <input id="coupon-code" name="code" type="text" class="form-control text-uppercase" value="{{ old('code') }}" maxlength="40" autocomplete="off" required>
                                            <button type="submit" class="rbt-btn rbt-btn-sm">Appliquer</button>
                                        </div>
                                    </form>
                                @endif
                                @if (session('coupon_error'))
                                    <p class="b4 mt--8 mb-0 rbt-text-color-danger" role="alert">{{ session('coupon_error') }}</p>
                                @endif
                                @if ($publicCoupons->isNotEmpty())
                                    <button type="button" class="b4 mt--8 p-0 border-0 bg-transparent text-decoration-underline" data-bs-toggle="modal" data-bs-target="#couponCollectionModal">
                                        Voir les codes disponibles ({{ $publicCoupons->count() }})
                                    </button>
                                @endif
                            </div>

                            <dl class="mb-0">
                                <div class="d-flex justify-content-between mb--8">
                                    <dt class="fw-normal">Sous-total ({{ $summary->count() }} {{ Str::plural('article', $summary->count()) }})</dt>
                                    <dd class="mb-0">@money($summary->subtotal)</dd>
                                </div>
                                @if ($summary->discount > 0)
                                    <div class="d-flex justify-content-between mb--8">
                                        <dt class="fw-normal">Remise ({{ $summary->coupon->code }})</dt>
                                        <dd class="mb-0">−@money($summary->discount)</dd>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between mb--8">
                                    <dt class="fw-normal">Livraison</dt>
                                    <dd class="mb-0">
                                        @if ($summary->shippingFee === null)
                                            Choisissez votre commune
                                        @elseif ($summary->shippingFee === 0)
                                            Offerte
                                        @else
                                            @money($summary->shippingFee)
                                        @endif
                                    </dd>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                    <dt class="h6 mb-0">Total</dt>
                                    <dd class="h6 mb-0">@money($summary->total())</dd>
                                </div>
                            </dl>

                            @if ($missing = $summary->missingForFreeShipping())
                                <p class="b4 mt--16 mb-0">Plus que <strong>@money($missing)</strong> d’achats pour la livraison offerte.</p>
                            @endif

                            @if ($summary->count() > 0)
                                <a class="rbt-btn w-100 mt--24 text-center" href="{{ route('checkout.show') }}">Commander</a>
                            @else
                                <button type="button" class="rbt-btn w-100 mt--24" disabled>Commander</button>
                            @endif
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </div>
@endsection

@if ($publicCoupons->isNotEmpty())
    @push('modals')
        @include('partials.modals.coupons', ['coupons' => $publicCoupons])
    @endpush
@endif
