@extends('layouts.storefront')

@section('title', 'Commande '.$order->number)
@section('robots', 'noindex, nofollow')
@section('whatsapp_message', 'Bonjour, je viens de passer la commande '.$order->number.' : '.$order->items->map(fn ($item) => $item->quantityLabel().' × '.$item->product_name)->join(', ').'. Total : '.\App\Support\Money::format($order->total).'.')

@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;

    $awaitsPayment = $order->awaitsOnlinePayment();
    // While CinetPay has not answered for an attempt under way, the page refreshes itself.
    $confirming = $awaitsPayment && $order->payments()->whereIn('status', ['initie', 'en_attente'])->where('created_at', '>=', now()->subMinutes($paymentTimeout))->exists();
    $cancelled = $order->status === OrderStatus::Cancelled;
    $paid = $order->payment_status === PaymentStatus::Paid;
    $firstName = \Illuminate\Support\Str::of($order->customer_name)->explode(' ')->first();
    $delay = $order->commune?->zone?->delay_label;
    $contact = app(\App\Services\Storefront\StoreSettings::class)->contact();
    $state = $cancelled ? 'cancelled' : ($awaitsPayment ? 'pending' : 'done');
@endphp

@if ($confirming && ! session('payment_error'))
    @push('meta')
        <meta http-equiv="refresh" content="10">
    @endpush
@endif

@section('content')
    {{-- Thanks banner: what happened, and the order number to keep. --}}
    <section class="kova-thanks-hero kova-thanks-hero--{{ $state }}">
        <div class="container">
            <nav class="kova-thanks-hero__trail" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a> <span aria-hidden="true">/</span> Commande {{ $order->number }}</nav>
            <div class="kova-thanks-hero__main">
                <span class="kova-thanks-hero__icon" aria-hidden="true">
                    <i class="fa-regular {{ match ($state) { 'cancelled' => 'fa-circle-xmark', 'pending' => 'fa-hourglass-half', default => 'fa-check' } }}"></i>
                </span>
                <div class="kova-thanks-hero__text">
                    <h1>{{ match ($state) {
                        'cancelled' => 'Commande annulée',
                        'pending' => 'Plus qu’une étape : le paiement',
                        default => 'Merci pour votre commande, '.$firstName.' !',
                    } }}</h1>
                    <p>{{ match ($state) {
                        'cancelled' => 'Cette commande n’est plus en cours. Vous pouvez repasser commande à tout moment.',
                        'pending' => 'Votre commande est réservée. Réglez-la en ligne pour que nous la préparions.',
                        default => 'Nous l’avons bien reçue et nous vous appelons au '.$order->formattedPhone().' pour organiser la livraison.',
                    } }}</p>
                </div>
                <div class="kova-thanks-ticket">
                    <span class="kova-thanks-ticket__label">Votre numéro de commande</span>
                    <strong class="kova-thanks-ticket__number" id="order-number">{{ $order->number }}</strong>
                    <button type="button" class="kova-thanks-ticket__copy" data-copy="{{ $order->number }}" aria-label="Copier le numéro de commande"><i class="fa-regular fa-copy mr--4"></i><span>Copier</span></button>
                    <span class="kova-thanks-ticket__hint">Conservez-le : il vous sera demandé pour suivre votre commande.</span>
                </div>
            </div>
            <ul class="kova-thanks-facts">
                <li><i class="fa-regular fa-calendar"></i> Passée le {{ $order->created_at->format('d/m/Y à H\hi') }}</li>
                <li><i class="fa-regular fa-bag-shopping"></i> {{ $order->itemCount() }} article{{ $order->itemCount() > 1 ? 's' : '' }}</li>
                <li><i class="fa-regular fa-wallet"></i> Total @money($order->total)</li>
            </ul>
        </div>
    </section>

    <div class="kova-thanks-body">
        <div class="container">
            @if (session('payment_error'))
                <div class="alert alert-danger" data-popup role="alert">{{ session('payment_error') }}</div>
            @elseif (session('payment_status'))
                <div class="alert alert-info" data-popup role="status">{{ session('payment_status') }}</div>
            @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    {{-- Online payment (F-060 to F-062): paid, waiting, or cancelled past the timeout (F-056). --}}
                    @if ($order->payment_method->isOnline())
                        @if ($paid)
                            <div class="kova-thanks-card kova-thanks-pay kova-thanks-pay--paid">
                                <p class="mb-0"><i class="fa-regular fa-circle-check mr--8"></i><strong>Paiement reçu : votre commande est enregistrée.</strong></p>
                            </div>
                        @elseif ($awaitsPayment)
                            <div class="kova-thanks-card kova-thanks-pay kova-thanks-pay--due">
                                <div>
                                    <p class="kova-thanks-pay__amount">@money($order->total) à régler</p>
                                    <p class="mb-0">Sans paiement, la commande est annulée au bout de {{ $paymentTimeout }} minutes. Orange Money, MTN MoMo, Moov Money, Wave ou carte bancaire, sur la page sécurisée de CinetPay.</p>
                                </div>
                                <form method="POST" action="{{ route('payments.pay', $order) }}">
                                    @csrf
                                    <button type="submit" class="rbt-btn"><i class="fa-regular fa-lock mr--8"></i>Payer maintenant</button>
                                </form>
                            </div>
                        @else
                            <div class="kova-thanks-card kova-thanks-pay kova-thanks-pay--cancelled">
                                <p class="mb-0">Cette commande n’a pas été payée à temps : elle a été annulée.</p>
                            </div>
                        @endif
                    @endif

                    {{-- What happens next. --}}
                    @unless ($cancelled)
                        <section class="kova-thanks-card" aria-labelledby="next-steps">
                            <h2 id="next-steps" class="kova-thanks-card__title">La suite de votre commande</h2>
                            {{-- Ticked off as the store, the picker and the courier act; refreshed by assets/js/order-progress.js. --}}
                            <div data-order-progress data-url="{{ route('checkout.progress', $order) }}" @if ($progressSettled) data-settled @endif aria-live="polite">
                                @include('partials.order-progress', ['steps' => $progress])
                            </div>
                            <p class="kova-thanks-card__note"><i class="fa-brands fa-whatsapp mr--8"></i>Vous êtes prévenu sur WhatsApp au {{ $order->formattedPhone() }} à chaque étape{{ $order->email ? ', et par e-mail au '.$order->email : '' }}.</p>
                        </section>
                    @endunless

                    {{-- The order itself. --}}
                    <section class="kova-thanks-card" aria-labelledby="summary">
                        <h2 id="summary" class="kova-thanks-card__title">Récapitulatif</h2>
                        <ul class="kova-thanks-items">
                            @foreach ($order->items as $item)
                                <li>
                                    <span class="kova-thanks-items__img">
                                        @if ($item->image)
                                            <img src="{{ asset($item->image) }}" alt="" loading="lazy" decoding="async">
                                        @else
                                            <i class="fa-regular fa-box" aria-hidden="true"></i>
                                        @endif
                                    </span>
                                    <span class="kova-thanks-items__text">
                                        <strong>{{ $item->quantityLabel() }} × {{ $item->product_name }}</strong>
                                        @if ($item->variant_label)<span>{{ $item->variant_label }}</span>@endif
                                        @if ($item->contentsSummary())<span>{{ $item->contentsSummary() }}</span>@endif
                                        @if ($item->weighNote())<span>{{ $item->weighNote() }}</span>@endif
                                        <span>@money($item->unit_price){{ $item->saleQuantity()->priceSuffix() ?: ' l’unité' }}</span>
                                    </span>
                                    <span class="kova-thanks-items__total">@money($item->line_total)</span>
                                </li>
                            @endforeach
                        </ul>
                        <dl class="kova-thanks-totals">
                            <div><dt>Sous-total</dt><dd>@money($order->subtotal)</dd></div>
                            @if ($order->discount > 0)<div><dt>Remise ({{ $order->coupon_code }})</dt><dd>−@money($order->discount)</dd></div>@endif
                            <div><dt>Livraison ({{ $order->commune_name }})</dt><dd>{{ $order->shipping_fee === 0 ? 'Offerte' : \App\Support\Money::format($order->shipping_fee) }}</dd></div>
                            <div class="is-total"><dt>Total</dt><dd>@money($order->total)</dd></div>
                        </dl>
                    </section>
                </div>

                <aside class="col-lg-4">
                    <div class="kova-thanks-side">
                        <section class="kova-thanks-card">
                            <h2 class="kova-thanks-card__title"><i class="fa-regular fa-location-dot"></i> Livraison</h2>
                            <p class="mb-0"><strong>{{ $order->customer_name }}</strong><br>{{ $order->district }}, {{ $order->commune_name }}@if ($order->landmark)<br><span class="kova-thanks-muted">Repère : {{ $order->landmark }}</span>@endif<br>{{ $order->formattedPhone() }}</p>
                        </section>

                        <section class="kova-thanks-card">
                            <h2 class="kova-thanks-card__title"><i class="fa-regular fa-wallet"></i> Paiement</h2>
                            @if ($order->payment_method->isOnline())
                                <p class="mb-0">{{ $order->payment_method->getLabel() }} (CinetPay)</p>
                                <p class="kova-thanks-amount {{ $paid ? 'is-paid' : '' }}">@money($order->total) · {{ mb_strtolower($order->payment_status->getLabel()) }}</p>
                            @else
                                <p class="mb-0">{{ $order->payment_method->getLabel() }}</p>
                                <p class="kova-thanks-amount">@money($order->total) à régler à la réception</p>
                                <p class="kova-thanks-muted mb-0">En espèces ou par Mobile Money, au livreur.</p>
                            @endif
                        </section>

                        <section class="kova-thanks-card kova-thanks-actions">
                            {{-- The receipt: "Reçu de commande" while payment is due, "Reçu de paiement" once paid. --}}
                            <a class="rbt-btn" href="{{ route('orders.receipt', $order) }}" target="_blank" rel="noopener">
                                <i class="fa-regular fa-file-invoice me-2" aria-hidden="true"></i>Télécharger mon reçu (PDF)
                            </a>
                            <a class="rbt-btn rbt-btn-border" href="{{ route('tracking.show') }}"><i class="fa-regular fa-location-crosshairs me-2" aria-hidden="true"></i>Suivre ma commande</a>
                            <a class="kova-thanks-link" href="{{ route('shop.index') }}">Continuer mes achats <i class="fa-regular fa-arrow-right ml--4"></i></a>
                        </section>

                        @guest
                            <section class="kova-thanks-card kova-thanks-account">
                                <h2 class="kova-thanks-card__title"><i class="fa-regular fa-user-plus"></i> Gagnez du temps la prochaine fois</h2>
                                <p>Créez votre compte avec le numéro {{ $order->formattedPhone() }} : vos commandes passées sans compte y sont rattachées et vos adresses sont retenues.</p>
                                <a href="#!" class="kova-thanks-link" data-bs-toggle="modal" data-bs-target="#signupModal">Créer mon compte <i class="fa-regular fa-arrow-right ml--4"></i></a>
                            </section>
                        @endguest

                        <p class="kova-thanks-help"><i class="fa-regular fa-headset mr--8"></i>Une question ? Appelez-nous au <strong>{{ $contact['phone'] }}</strong> ({{ $contact['opening_hours'] }}).</p>
                    </div>
                </aside>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // "Copier": the order number to the clipboard, to paste it in a message or the tracking page.
        document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', () => {
            const label = button.querySelector('span');
            navigator.clipboard?.writeText(button.dataset.copy).then(() => {
                label.textContent = 'Copié';
                setTimeout(() => { label.textContent = 'Copier'; }, 2000);
            }).catch(() => {});
        }));
    </script>
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/order-progress.js') }}?v={{ @filemtime(public_path('assets/js/order-progress.js')) }}"></script>
@endpush
