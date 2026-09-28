@extends('layouts.storefront')

@section('title', 'Commande '.$order->number)
@section('robots', 'noindex, nofollow')
@section('whatsapp_message', 'Bonjour, je viens de passer la commande '.$order->number.' : '.$order->items->map(fn ($item) => $item->quantity.' × '.$item->product_name)->join(', ').'. Total : '.\App\Support\Money::format($order->total).'.')

@section('content')
    <x-page-header title="Merci pour votre commande !" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb--32">
                        <p class="b2 mb--8">Votre numéro de commande</p>
                        <p class="h3 mb--16">{{ $order->number }}</p>
                        <p class="mb-0">Conservez-le : il vous sera demandé pour suivre votre commande.
                            Nous vous contacterons au {{ $order->formattedPhone() }} pour organiser la livraison.</p>
                    </div>

                    <div class="rbt-bg-color-gray-light rbt-radius p-4">
                        <h2 class="h5 mb--16">Récapitulatif</h2>
                        <ul class="list-unstyled mb--16">
                            @foreach ($order->items as $item)
                                <li class="d-flex justify-content-between gap-3 mb--8">
                                    <span>{{ $item->quantity }} × {{ $item->product_name }}@if ($item->variant_label)<span class="b4 d-block">{{ $item->variant_label }}</span>@endif@if ($item->contentsSummary())<span class="b4 d-block">{{ $item->contentsSummary() }}</span>@endif</span>
                                    <span class="text-nowrap">@money($item->line_total)</span>
                                </li>
                            @endforeach
                        </ul>
                        <dl class="border-top pt-3 mb--24">
                            <div class="d-flex justify-content-between mb--8"><dt class="fw-normal">Sous-total</dt><dd class="mb-0">@money($order->subtotal)</dd></div>
                            @if ($order->discount > 0)<div class="d-flex justify-content-between mb--8"><dt class="fw-normal">Remise ({{ $order->coupon_code }})</dt><dd class="mb-0">−@money($order->discount)</dd></div>@endif
                            <div class="d-flex justify-content-between mb--8"><dt class="fw-normal">Livraison ({{ $order->commune_name }})</dt><dd class="mb-0">{{ $order->shipping_fee === 0 ? 'Offerte' : \App\Support\Money::format($order->shipping_fee) }}</dd></div>
                            <div class="d-flex justify-content-between border-top pt-2 mt-2"><dt class="h6 mb-0">Total</dt><dd class="h6 mb-0">@money($order->total)</dd></div>
                        </dl>
                        <div class="row g-3 b3">
                            <div class="col-md-6">
                                <p class="rbt-text-bold mb--4">Livraison</p>
                                <p class="mb-0">{{ $order->customer_name }}<br>{{ $order->district }}, {{ $order->commune_name }}@if ($order->landmark)<br>{{ $order->landmark }}@endif</p>
                            </div>
                            <div class="col-md-6">
                                <p class="rbt-text-bold mb--4">Paiement</p>
                                <p class="mb-0">{{ $order->payment_method->getLabel() }} : @money($order->total) à régler à la réception.</p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt--32">
                        <a class="rbt-btn" href="{{ route('shop.index') }}">Continuer mes achats</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
