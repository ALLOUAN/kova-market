@extends('layouts.storefront')

@section('title', 'Commande '.$order->number)
@section('robots', 'noindex, nofollow')
@section('whatsapp_message', 'Bonjour, je viens de passer la commande '.$order->number.' : '.$order->items->map(fn ($item) => $item->quantityLabel().' × '.$item->product_name)->join(', ').'. Total : '.\App\Support\Money::format($order->total).'.')

@php
    use App\Enums\PaymentStatus;

    $awaitsPayment = $order->awaitsOnlinePayment();
    // While CinetPay has not answered for an attempt under way, the page refreshes itself.
    $confirming = $awaitsPayment && $order->payments()->whereIn('status', ['initie', 'en_attente'])->where('created_at', '>=', now()->subMinutes($paymentTimeout))->exists();
@endphp

@if ($confirming && ! session('payment_error'))
    @push('meta')
        <meta http-equiv="refresh" content="10">
    @endpush
@endif

@section('content')
    <x-page-header :title="$awaitsPayment ? 'Plus qu’une étape : le paiement' : 'Merci pour votre commande !'" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @if (session('payment_error'))
                        <div class="alert alert-danger" role="alert">{{ session('payment_error') }}</div>
                    @elseif (session('payment_status'))
                        <div class="alert alert-info" role="status">{{ session('payment_status') }}</div>
                    @endif

                    {{-- Online payment (F-060 to F-062): paid, waiting, or cancelled past the timeout (F-056). --}}
                    @if ($order->payment_method->isOnline())
                        <div @class(['rbt-radius p-4 mb--32 text-center', 'rbt-bg-color-gray-light' => ! $awaitsPayment, 'border border-warning' => $awaitsPayment])>
                            @if ($order->payment_status === PaymentStatus::Paid)
                                <p class="h6 mb-0"><i class="fa-regular fa-circle-check mr--4"></i> Paiement reçu : votre commande est enregistrée.</p>
                            @elseif ($awaitsPayment)
                                <p class="b2 mb--16">Votre commande est réservée, en attente de son paiement de <strong>@money($order->total)</strong>. Sans paiement, elle est annulée au bout de {{ $paymentTimeout }} minutes.</p>
                                <form method="POST" action="{{ route('payments.pay', $order) }}">
                                    @csrf
                                    <button type="submit" class="rbt-btn">Payer maintenant</button>
                                </form>
                                <p class="b4 mt--12 mb-0">Orange Money, MTN MoMo, Moov Money, Wave ou carte bancaire, sur la page sécurisée de CinetPay.</p>
                            @else
                                <p class="b2 mb-0">Cette commande n’a pas été payée à temps : elle a été annulée.</p>
                            @endif
                        </div>
                    @endif

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
                                    <span>{{ $item->quantityLabel() }} × {{ $item->product_name }}@if ($item->variant_label)<span class="b4 d-block">{{ $item->variant_label }}</span>@endif@if ($item->contentsSummary())<span class="b4 d-block">{{ $item->contentsSummary() }}</span>@endif</span>
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
                                @if ($order->payment_method->isOnline())
                                    <p class="mb-0">{{ $order->payment_method->getLabel() }} (CinetPay) : @money($order->total), {{ mb_strtolower($order->payment_status->getLabel()) }}.</p>
                                @else
                                    <p class="mb-0">{{ $order->payment_method->getLabel() }} : @money($order->total) à régler à la réception.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-3 mt--32">
                        {{-- The receipt: "Reçu de commande" while payment is due, "Reçu de paiement" once paid. --}}
                        <a class="rbt-btn rbt-btn-border" style="width: auto; padding: 0 24px" href="{{ route('orders.receipt', $order) }}" target="_blank" rel="noopener">
                            <i class="fa-regular fa-file-invoice me-2" aria-hidden="true"></i>Télécharger mon reçu (PDF)
                        </a>
                        <a class="rbt-btn" style="width: auto; padding: 0 24px" href="{{ route('shop.index') }}">Continuer mes achats</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
