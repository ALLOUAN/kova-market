@extends('layouts.storefront')

@section('title', 'Suivre ma commande')
@section('robots', 'noindex, nofollow')

@php
    use App\Enums\OrderStatus;

    $contact = app(\App\Services\Storefront\StoreSettings::class)->contact();
    $state = match (true) {
        ! $order => null,
        $order->status === OrderStatus::Cancelled => 'cancelled',
        $order->status === OrderStatus::Delivered => 'delivered',
        default => 'progress',
    };
@endphp

@section('content')
    <section class="kova-track-hero">
        <div class="container">
            <nav class="kova-thanks-hero__trail" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a> <span aria-hidden="true">/</span> Suivre ma commande</nav>
            <div class="kova-track-hero__title">
                <span class="kova-track-hero__icon" aria-hidden="true"><i class="fa-regular fa-location-crosshairs"></i></span>
                <div>
                    <h1>Suivre ma commande</h1>
                    <p>Saisissez le numéro reçu sur WhatsApp et le téléphone utilisé pour commander : vous voyez où en est votre commande, sans compte.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="kova-track-body">
        <div class="container">
            {{-- The search, raised over the banner. --}}
            <form method="POST" action="{{ route('tracking.search') }}" class="kova-track-form" novalidate>
                @csrf
                <div class="kova-track-form__field">
                    <label class="rbt-field-label" for="number">Numéro de commande</label>
                    <input class="rbt-input-field" id="number" name="number" type="text" value="{{ old('number', request('number', $order?->number)) }}" placeholder="KM-260927-0042" autocomplete="off" required>
                    @error('number')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <div class="kova-track-form__field">
                    <label class="rbt-field-label" for="phone">Téléphone utilisé pour la commande</label>
                    <input class="rbt-input-field" id="phone" name="phone" type="tel" value="{{ old('phone', request('phone')) }}" placeholder="07 01 02 03 04" autocomplete="tel" required>
                    @error('phone')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="rbt-btn kova-track-form__submit"><i class="fa-regular fa-magnifying-glass mr--8"></i>Suivre</button>
                <div class="kova-track-form__captcha"><x-turnstile /></div>
                <p class="kova-track-form__hint"><i class="fa-regular fa-circle-info mr--4"></i>Le numéro commence par KM- : il figure dans le message WhatsApp et l’e-mail de confirmation, et sur votre reçu.</p>
            </form>

            @if ($notFound ?? false)
                {{-- Same answer whatever is wrong: the page never reveals whether an order number exists. --}}
                <div class="kova-track-notfound" role="status">
                    <span class="kova-track-notfound__icon" aria-hidden="true"><i class="fa-regular fa-magnifying-glass-minus"></i></span>
                    <div>
                        <p class="kova-track-notfound__title">Commande introuvable.</p>
                        <p class="mb-0">Vérifiez le numéro et le téléphone indiqués lors de la commande. Toujours bloqué ? Appelez-nous au <strong>{{ $contact['phone'] }}</strong>.</p>
                    </div>
                </div>
            @elseif ($order)
                <section class="kova-track-result kova-track-result--{{ $state }}" aria-labelledby="order-title">
                    <header class="kova-track-result__head">
                        <div>
                            <h2 id="order-title">Commande {{ $order->number }}</h2>
                            <p>Passée le {{ $order->created_at->format('d/m/Y à H\hi') }}</p>
                        </div>
                        <span class="kova-track-status">{{ $order->status->getLabel() }}</span>
                    </header>

                    <x-order-timeline :order="$order" />
                    <x-order-delivery :order="$order" />

                    <ul class="kova-track-facts">
                        <li><i class="fa-regular fa-bag-shopping"></i>{{ $order->itemCount() }} {{ Str::plural('article', $order->itemCount()) }}</li>
                        <li><i class="fa-regular fa-wallet"></i>@money($order->total) · {{ $order->payment_method->getLabel() }}</li>
                        {{-- Only the commune: the full address and the e-mail are never shown here. --}}
                        <li><i class="fa-regular fa-location-dot"></i>Livraison à {{ $order->commune_name }}.</li>
                    </ul>
                </section>
            @endif

            <div class="kova-track-help">
                <div>
                    <span class="kova-track-help__icon" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span>
                    <p><strong>Prévenu à chaque étape</strong>Un message WhatsApp vous est envoyé à la confirmation, à l’expédition, au départ du livreur et à la livraison.</p>
                </div>
                <div>
                    <span class="kova-track-help__icon" aria-hidden="true"><i class="fa-regular fa-user"></i></span>
                    <p><strong>Toutes vos commandes</strong>
                        @auth
                            Retrouvez-les dans <a href="{{ route('account.orders') }}">Mes commandes</a>, avec leurs reçus.
                        @else
                            Avec un compte, retrouvez-les dans « Mes commandes », avec leurs reçus. <a href="#!" data-bs-toggle="modal" data-bs-target="#signinModal">Se connecter</a>
                        @endauth
                    </p>
                </div>
                <div>
                    <span class="kova-track-help__icon" aria-hidden="true"><i class="fa-regular fa-headset"></i></span>
                    <p><strong>Une question ?</strong>Appelez le {{ $contact['phone'] }} ({{ $contact['opening_hours'] }}) ou <a href="{{ route('contact.show') }}">écrivez-nous</a>.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
