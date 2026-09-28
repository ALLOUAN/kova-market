@extends('layouts.storefront')

@section('title', 'Commande '.$order->number)

@section('content')
    <x-account-layout :title="'Commande '.$order->number">
        <div class="row g-4">
            <div class="col-md-5">
                <h2 class="h6 mb--16">Suivi</h2>
                <x-order-timeline :order="$order" />
                <x-order-delivery :order="$order" />
            </div>
            <div class="col-md-7">
                <h2 class="h6 mb--16">Articles</h2>
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
                    <div class="d-flex justify-content-between mb--8"><dt class="fw-normal">Livraison</dt><dd class="mb-0">{{ $order->shipping_fee === 0 ? 'Offerte' : \App\Support\Money::format($order->shipping_fee) }}</dd></div>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2"><dt class="h6 mb-0">Total</dt><dd class="h6 mb-0">@money($order->total)</dd></div>
                </dl>
                <p class="b3 mb--4"><strong>Livraison :</strong> {{ $order->customer_name }}, {{ $order->district }}, {{ $order->commune_name }}@if ($order->landmark) ({{ $order->landmark }})@endif</p>
                <p class="b3 mb-0"><strong>Paiement :</strong> {{ $order->payment_method->getLabel() }} — {{ $order->payment_status->getLabel() }}</p>
            </div>
        </div>
    </x-account-layout>
@endsection
