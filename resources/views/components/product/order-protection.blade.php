@props(['product'])

{{--
    "Commande protégée" on the product page: what the store actually guarantees, nothing more. Secure online payment
    (only while CinetPay is set up), payment on delivery, the product's own return window when it has one, order
    tracking, and a WhatsApp conversation about this product. The returns page gives the details.
--}}
@php
    $onlinePayment = app(\App\Services\Payments\OnlinePayments::class)->isAvailable();
    $whatsapp = app(\App\Services\Storefront\StoreSettings::class)->whatsappUrl(
        'Bonjour, j’ai une question sur « '.$product->name.' » : '.$product->url(),
    );
@endphp
<section class="kova-protection" aria-labelledby="kova-protection-title-{{ $product->id }}">
    <a class="kova-protection__head" href="{{ route('pages.show', 'retours-et-remboursements') }}">
        <span class="kova-protection__badge" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>
        <span class="kova-protection__heading">
            <span class="kova-protection__eyebrow">Achetez en toute confiance</span>
            <h2 class="kova-protection__title" id="kova-protection-title-{{ $product->id }}">Commande protégée {{ config('storefront.name') }}</h2>
        </span>
        <i class="fa-regular fa-chevron-right kova-protection__arrow" aria-hidden="true"></i>
    </a>

    <ul class="kova-protection__list">
        @if ($onlinePayment)
            <li>
                <i class="fa-solid fa-lock kova-protection__icon kova-protection__icon--green" aria-hidden="true"></i>
                <div>
                    <p class="kova-protection__label">
                        Paiement en ligne sécurisé
                        <span class="kova-protection__brands" aria-label="Orange Money, MTN MoMo, Moov Money, Wave, Visa, Mastercard">
                            @foreach (['orange-money' => 'Orange Money', 'mtn-momo' => 'MTN MoMo', 'moov-money' => 'Moov Money', 'wave' => 'Wave'] as $file => $name)
                                <img src="{{ asset('assets/images/payment/'.$file.'.webp') }}" alt="" title="{{ $name }}" width="28" height="18" loading="lazy">
                            @endforeach
                            <i class="fa-brands fa-cc-visa" aria-hidden="true"></i>
                            <i class="fa-brands fa-cc-mastercard" aria-hidden="true"></i>
                        </span>
                    </p>
                    <p class="kova-protection__text">Mobile Money ou carte via CinetPay : vos codes et votre carte ne passent jamais par notre site, et chaque paiement est vérifié avant validation.</p>
                </div>
            </li>
        @endif
        <li>
            <i class="fa-solid fa-hand-holding-dollar kova-protection__icon kova-protection__icon--orange" aria-hidden="true"></i>
            <div>
                <p class="kova-protection__label">Paiement à la livraison</p>
                <p class="kova-protection__text">Vous payez en recevant votre colis, après l’avoir vu.</p>
            </div>
        </li>
        @if ($product->return_days)
            <li>
                <i class="fa-solid fa-rotate-left kova-protection__icon kova-protection__icon--gold" aria-hidden="true"></i>
                <div>
                    <p class="kova-protection__label">{{ $product->return_days }} jours pour changer d’avis</p>
                    <p class="kova-protection__text">Conditions de retour et de remboursement détaillées sur notre page dédiée.</p>
                </div>
            </li>
        @endif
        <li>
            <i class="fa-solid fa-truck-fast kova-protection__icon kova-protection__icon--navy" aria-hidden="true"></i>
            <div>
                <p class="kova-protection__label">Commande suivie</p>
                <p class="kova-protection__text">Chaque étape vous est envoyée par WhatsApp, jusqu’à la remise par notre livreur.</p>
            </div>
        </li>
    </ul>

    @if ($whatsapp)
        <a class="kova-protection__chat" href="{{ $whatsapp }}" target="_blank" rel="noopener">
            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Discuter avec nous
        </a>
    @endif

    <p class="kova-protection__note"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Valable pour les commandes passées sur la boutique {{ config('storefront.name') }}, payées en ligne ou à la livraison.</p>
</section>
