@extends('layouts.storefront')

@php
    use App\Support\Money;

    $field = fn (string $name) => old($name, $defaults[$name] ?? null);
@endphp

@section('title', 'Commande')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-page-header title="Finaliser ma commande" :trail="['Panier' => route('cart.show')]" />

    <div class="rbt-section-gap2">
        <div class="container">
            <form method="POST" action="{{ route('checkout.store') }}" novalidate>
                @csrf
                <div class="row g-5">
                    <div class="col-lg-7">
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">Merci de corriger les champs signalés ci-dessous.</div>
                        @endif

                        <fieldset class="mb--32">
                            <legend class="h5 mb--16">Vos coordonnées</legend>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="rbt-field-label" for="customer_name">Nom complet<span class="rbt-text-color-danger">*</span></label>
                                    <input class="rbt-input-field" type="text" id="customer_name" name="customer_name" value="{{ $field('customer_name') }}" autocomplete="name" required>
                                    @error('customer_name')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="rbt-field-label" for="phone">Téléphone<span class="rbt-text-color-danger">*</span></label>
                                    <input class="rbt-input-field" type="tel" id="phone" name="phone" value="{{ $field('phone') }}" placeholder="07 01 02 03 04" autocomplete="tel" required>
                                    @error('phone')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="rbt-field-label" for="email">E-mail <span class="b4">(facultatif, pour recevoir le récapitulatif)</span></label>
                                    <input class="rbt-input-field" type="email" id="email" name="email" value="{{ $field('email') }}" autocomplete="email">
                                    @error('email')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="mb--32">
                            <legend class="h5 mb--16">Livraison</legend>
                            @if ($addresses->isNotEmpty())
                                {{-- Picking a saved address fills the fields below (F-071). --}}
                                <label class="rbt-field-label" for="saved_address">Mes adresses enregistrées</label>
                                <select id="saved_address" class="form-select mb--16" data-saved-address>
                                    <option value="">Saisir une autre adresse</option>
                                    @foreach ($addresses as $address)
                                        <option value="{{ $address->id }}" @selected($address->is_default && ! old('_token'))
                                            data-recipient="{{ $address->recipient_name }}" data-phone="{{ \App\Support\PhoneNumber::format($address->phone) }}"
                                            data-commune="{{ $address->commune_id }}" data-district="{{ $address->district }}" data-landmark="{{ $address->landmark }}">
                                            {{ $address->label }} — {{ $address->summary() }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="rbt-field-label" for="commune_id">Commune<span class="rbt-text-color-danger">*</span></label>
                                    <select id="commune_id" name="commune_id" class="form-select" required data-commune>
                                        <option value="">Choisir votre commune</option>
                                        @foreach ($communes as $zone => $zoneCommunes)
                                            <optgroup label="{{ $zone }}">
                                                @foreach ($zoneCommunes as $commune)
                                                    <option value="{{ $commune->id }}" data-fee="{{ $commune->zone->fee }}" data-delay="{{ $commune->zone->delay_label }}" @selected((int) $field('commune_id') === $commune->id)>{{ $commune->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('commune_id')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                    <span class="d-block mt--4 b4" data-delay-text></span>
                                </div>
                                <div class="col-md-6">
                                    <label class="rbt-field-label" for="district">Quartier<span class="rbt-text-color-danger">*</span></label>
                                    <input class="rbt-input-field" type="text" id="district" name="district" value="{{ $field('district') }}" placeholder="Riviera 2, Angré…" required>
                                    @error('district')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="rbt-field-label" for="landmark">Repère <span class="b4">(pour aider le livreur)</span></label>
                                    <input class="rbt-input-field" type="text" id="landmark" name="landmark" value="{{ $field('landmark') }}" placeholder="Près de la pharmacie…">
                                    @error('landmark')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="rbt-field-label" for="note">Note pour la commande <span class="b4">(facultatif)</span></label>
                                    <textarea class="rbt-text-field" id="note" name="note" rows="3">{{ $field('note') }}</textarea>
                                    @error('note')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                @auth
                                    <div class="col-12">
                                        <div class="rbt-check-group">
                                            <input type="checkbox" id="save_address" name="save_address" value="1" @checked(old('save_address'))>
                                            <label for="save_address">Enregistrer cette adresse dans mon carnet</label>
                                        </div>
                                    </div>
                                @endauth
                            </div>
                        </fieldset>

                        <fieldset class="mb--32">
                            <legend class="h5 mb--16">Paiement</legend>
                            {{-- Pay now online (CinetPay) or on delivery: one card per method, the whole card is clickable. --}}
                            @php
                                $networks = collect(config('storefront.payment_methods'))->filter(fn ($network) => filled($network['logo'] ?? null) && file_exists(public_path($network['logo'])));
                            @endphp
                            <div class="kova-pay-options">
                                @foreach ($paymentMethods as $method)
                                    <label class="kova-pay-option" for="payment-{{ $method->value }}">
                                        <input type="radio" id="payment-{{ $method->value }}" name="payment_method" value="{{ $method->value }}" @checked(old('payment_method', $paymentMethods[0]->value) === $method->value)>
                                        <span class="kova-pay-icon" aria-hidden="true"><i class="fa-solid {{ $method->isOnline() ? 'fa-mobile-screen' : 'fa-truck' }}"></i></span>
                                        <span class="kova-pay-body">
                                            <strong>{{ $method->isOnline() ? 'Payer maintenant, en ligne' : 'Payer à la livraison' }}</strong>
                                            <span>{{ $method->description() }}</span>
                                            @if ($method->isOnline() && $networks->isNotEmpty())
                                                <span class="kova-pay-logos">
                                                    @foreach ($networks as $network)
                                                        <img src="{{ asset($network['logo']) }}" alt="{{ $network['label'] }}" height="26" loading="lazy">
                                                    @endforeach
                                                </span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @if (collect($paymentMethods)->contains(fn ($method) => $method->isOnline()))
                                <p class="b4 mt--12 mb-0"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Paiement en ligne : après « Valider ma commande », vous payez sur la page sécurisée de CinetPay, puis vous revenez ici. Une commande non payée est annulée au bout de {{ app(\App\Services\Payments\OnlinePayments::class)->timeoutMinutes() }} minutes.</p>
                            @endif
                            @error('payment_method')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </fieldset>

                        {{-- F-053: explicit consent, separate optional marketing opt-in. --}}
                        <div class="rbt-check-group mb--8">
                            <input type="checkbox" id="terms" name="terms" value="1" @checked(old('terms')) required>
                            {{-- One span: the theme lays the label out as a flex row, which spread the links apart. --}}
                            <label for="terms"><span>J’accepte les <a href="{{ route('pages.show', 'conditions-generales-de-vente') }}" target="_blank">conditions générales de vente</a> et la <a href="{{ route('pages.show', 'politique-de-confidentialite') }}" target="_blank">politique de confidentialité</a>.<span class="rbt-text-color-danger">*</span></span></label>
                        </div>
                        @error('terms')<span class="d-block mb--8 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        <div class="rbt-check-group">
                            <input type="checkbox" id="marketing_opt_in" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in'))>
                            <label for="marketing_opt_in">Je souhaite recevoir les offres et nouveautés de {{ config('storefront.name') }} (facultatif).</label>
                        </div>
                    </div>

                    <aside class="col-lg-5">
                        <div class="rbt-bg-color-gray-light rbt-radius p-4">
                            <h2 class="h5 mb--16">Votre commande</h2>
                            <ul class="list-unstyled mb--16">
                                @foreach ($summary->lines->filter->available as $line)
                                    <li class="d-flex justify-content-between gap-3 mb--8">
                                        <span>{{ $line->quantity }} × {{ $line->product->name }}@if ($line->variant->attributeValues->isNotEmpty())<span class="b4 d-block">{{ $line->variant->label() }}</span>@endif</span>
                                        <span class="text-nowrap">@money($line->total())</span>
                                    </li>
                                @endforeach
                            </ul>
                            <dl class="mb-0 border-top pt-3">
                                <div class="d-flex justify-content-between mb--8">
                                    <dt class="fw-normal">Sous-total</dt>
                                    <dd class="mb-0">@money($summary->subtotal)</dd>
                                </div>
                                @if ($summary->discount > 0)
                                    <div class="d-flex justify-content-between mb--8">
                                        <dt class="fw-normal">Remise ({{ $summary->coupon->code }})</dt>
                                        <dd class="mb-0">−@money($summary->discount)</dd>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between mb--8">
                                    <dt class="fw-normal">Livraison{{ $summary->hasFreeShippingCoupon() ? " ({$summary->coupon->code})" : '' }}</dt>
                                    <dd class="mb-0" data-shipping>Choisissez votre commune</dd>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                    <dt class="h6 mb-0">Total</dt>
                                    <dd class="h6 mb-0" data-total>@money($summary->subtotal - $summary->discount)</dd>
                                </div>
                            </dl>
                            <x-turnstile class="mt--24" />
                            <button type="submit" class="rbt-btn w-100 mt--24">Valider ma commande</button>
                            <a class="d-block text-center b3 mt--12" href="{{ route('cart.show') }}">Modifier mon panier</a>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Shows the delivery fee and total of the chosen commune; the server computes them again on validation.
        (() => {
            const subtotal = {{ $summary->subtotal }};
            const discount = {{ $summary->discount }};
            const freeShippingCoupon = @json($summary->hasFreeShippingCoupon());
            const threshold = @json($summary->freeShippingThreshold);
            const money = (amount) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount).replace(/\s/g, ' ') + ' FCFA';
            const select = document.querySelector('[data-commune]');

            const render = () => {
                const option = select.selectedOptions[0];
                if (!option || !option.dataset.fee) {
                    document.querySelector('[data-shipping]').textContent = 'Choisissez votre commune';
                    document.querySelector('[data-total]').textContent = money(subtotal - discount);
                    document.querySelector('[data-delay-text]').textContent = '';
                    return;
                }
                const fee = freeShippingCoupon || (threshold !== null && subtotal >= threshold) ? 0 : Number(option.dataset.fee);
                document.querySelector('[data-shipping]').textContent = fee === 0 ? 'Offerte' : money(fee);
                document.querySelector('[data-total]').textContent = money(subtotal - discount + fee);
                document.querySelector('[data-delay-text]').textContent = option.dataset.delay ? 'Délai indicatif : ' + option.dataset.delay : '';
            };

            select.addEventListener('change', render);
            render();

            document.querySelector('[data-saved-address]')?.addEventListener('change', (event) => {
                const option = event.target.selectedOptions[0];
                if (!option.value) {
                    return;
                }
                document.getElementById('customer_name').value = option.dataset.recipient;
                document.getElementById('phone').value = option.dataset.phone;
                document.getElementById('district').value = option.dataset.district;
                document.getElementById('landmark').value = option.dataset.landmark || '';
                select.value = option.dataset.commune;
                render();
            });
        })();
    </script>
@endpush
