@extends('layouts.storefront')

@php
    $field = fn (string $name) => old($name, $defaults[$name] ?? null);
    $phoneHref = 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone']);
@endphp

@section('title', 'Nous contacter')
@section('description', 'Contactez '.config('storefront.name').' : téléphone, WhatsApp, e-mail ou formulaire. Nous répondons en général dans la journée.')

@section('content')
    <x-page-header title="Nous contacter" />

    <section class="kova-contact-band">
        <div class="container">
            <p class="kova-contact-intro">Une question sur un produit, une commande ou une livraison ? Choisissez le moyen qui vous arrange : nous répondons en général dans la journée.</p>

            {{-- F-080: the quickest ways to reach us, one card each. --}}
            <ul class="kova-contact-cards">
                <li>
                    <a class="kova-contact-card" href="{{ $phoneHref }}">
                        <span class="kova-contact-card__icon kova-contact-card__icon--navy"><i class="fa-regular fa-phone"></i></span>
                        <span class="kova-contact-card__label">Téléphone</span>
                        <strong class="kova-contact-card__value">{{ $contact['phone'] }}</strong>
                        <span class="kova-contact-card__action">Appeler</span>
                    </a>
                </li>
                @if ($whatsappUrl)
                    <li>
                        <a class="kova-contact-card" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
                            <span class="kova-contact-card__icon kova-contact-card__icon--whatsapp"><i class="fa-brands fa-whatsapp"></i></span>
                            <span class="kova-contact-card__label">WhatsApp</span>
                            <strong class="kova-contact-card__value">Réponse rapide</strong>
                            <span class="kova-contact-card__action">Écrire sur WhatsApp</span>
                        </a>
                    </li>
                @endif
                <li>
                    <a class="kova-contact-card" href="mailto:{{ $contact['email'] }}">
                        <span class="kova-contact-card__icon kova-contact-card__icon--gold"><i class="fa-regular fa-envelope"></i></span>
                        <span class="kova-contact-card__label">E-mail</span>
                        <strong class="kova-contact-card__value">{{ $contact['email'] }}</strong>
                        <span class="kova-contact-card__action">Écrire un e-mail</span>
                    </a>
                </li>
                <li>
                    <div class="kova-contact-card">
                        <span class="kova-contact-card__icon kova-contact-card__icon--green"><i class="fa-regular fa-location-dot"></i></span>
                        <span class="kova-contact-card__label">Adresse</span>
                        <strong class="kova-contact-card__value">{{ $contact['address'] }}</strong>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <section class="kova-contact-main">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="kova-contact-form">
                        <h2 class="h4 mb--8">Envoyer un message</h2>
                        <p class="b3 mb--24">Remplissez le formulaire, nous vous recontactons par téléphone ou par e-mail.</p>

                        <form method="POST" action="{{ route('contact.store') }}" novalidate>
                            @csrf
                            <input type="hidden" name="started_at" value="{{ $startedAt }}">
                            {{-- Left empty by people, filled by robots. --}}
                            <div class="d-none" aria-hidden="true">
                                <label for="website">Site web</label>
                                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="rbt-field-label" for="name">Nom<span class="rbt-text-color-danger">*</span></label>
                                    <input class="rbt-input-field" type="text" id="name" name="name" value="{{ $field('name') }}" autocomplete="name" maxlength="255" required>
                                    @error('name')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="rbt-field-label" for="phone">Téléphone<span class="rbt-text-color-danger">*</span></label>
                                    <input class="rbt-input-field" type="tel" id="phone" name="phone" value="{{ $field('phone') }}" placeholder="07 01 02 03 04" autocomplete="tel" required>
                                    @error('phone')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="rbt-field-label" for="email">E-mail <span class="b4">(facultatif)</span></label>
                                    <input class="rbt-input-field" type="email" id="email" name="email" value="{{ $field('email') }}" autocomplete="email">
                                    @error('email')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="rbt-field-label" for="subject">Sujet<span class="rbt-text-color-danger">*</span></label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Choisir un sujet</option>
                                        @foreach ($subjects as $subject)
                                            <option value="{{ $subject->value }}" @selected(old('subject', request('sujet')) === $subject->value)>{{ $subject->getLabel() }}</option>
                                        @endforeach
                                    </select>
                                    @error('subject')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="rbt-field-label" for="message">Message<span class="rbt-text-color-danger">*</span></label>
                                    <textarea class="rbt-input-field" id="message" name="message" rows="6" maxlength="3000" required placeholder="Pour une commande, indiquez son numéro (KM-…) : nous vous répondrons plus vite.">{{ old('message') }}</textarea>
                                    @error('message')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <x-turnstile />
                                </div>
                            </div>

                            <div class="kova-contact-form__submit">
                                <p class="b4 mb-0"><i class="fa-regular fa-lock mr--4"></i> Vos coordonnées servent uniquement à vous répondre.</p>
                                <button type="submit" class="rbt-btn">Envoyer le message</button>
                            </div>
                        </form>
                    </div>
                </div>

                <aside class="col-lg-4">
                    <div class="kova-contact-side kova-contact-side--navy">
                        <h2 class="h6 mb--8"><i class="fa-regular fa-clock mr--8"></i>Horaires du service client</h2>
                        <p class="mb-0">{{ $contact['opening_hours'] }}</p>
                    </div>

                    <div class="kova-contact-side">
                        <h2 class="h6 mb--12"><i class="fa-regular fa-circle-question mr--8"></i>Avant d’écrire</h2>
                        <ul class="kova-contact-links">
                            <li><a href="{{ route('tracking.show') }}"><i class="fa-regular fa-truck-fast"></i> Suivre ma commande</a></li>
                            <li><a href="{{ route('faq') }}"><i class="fa-regular fa-comments"></i> Questions fréquentes</a></li>
                        </ul>
                    </div>

                    <div class="kova-contact-side kova-contact-side--tip">
                        <p class="b3 mb-0"><strong>Astuce :</strong> votre numéro de commande (KM-…) figure dans le SMS et l’e-mail de confirmation. Donnez-le-nous, nous retrouverons votre commande tout de suite.</p>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
