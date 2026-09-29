@extends('layouts.storefront')

@section('title', 'Paiement de la commande '.$order->number)
@section('robots', 'noindex, nofollow')

{{-- Back from CinetPay in a browser that did not place the order (e.g. after the operator's app): the outcome only,
     no personal detail. The order is followed with its number and phone on the tracking page. --}}
@section('content')
    <x-page-header title="Paiement" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <p class="b2 mb--8">Commande</p>
                    <p class="h3 mb--24">{{ $order->number }}</p>

                    @if ($payment->status === \App\Enums\TransactionStatus::Succeeded)
                        <div class="alert alert-success" role="status">Paiement reçu, merci ! Votre commande est enregistrée.</div>
                    @elseif (in_array($payment->status, [\App\Enums\TransactionStatus::Failed, \App\Enums\TransactionStatus::Cancelled], true))
                        <div class="alert alert-warning" role="status">Le paiement n’a pas abouti. Revenez sur la page de votre commande pour réessayer.</div>
                    @else
                        <div class="alert alert-info" role="status">Votre paiement est en cours de confirmation par CinetPay.</div>
                    @endif

                    <a class="rbt-btn" href="{{ route('tracking.show', ['number' => $order->number]) }}">Suivre ma commande</a>
                </div>
            </div>
        </div>
    </div>
@endsection
