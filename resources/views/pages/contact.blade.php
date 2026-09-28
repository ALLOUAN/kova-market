@extends('layouts.storefront')

@php
    $field = fn (string $name) => old($name, $defaults[$name] ?? null);
@endphp

@section('title', 'Nous contacter')
@section('description', 'Contactez '.config('storefront.name').' : téléphone, WhatsApp, e-mail ou formulaire. Nous répondons en général dans la journée.')

@section('content')
    <x-page-header title="Nous contacter" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row g-5">
                {{-- F-080: store details and the quickest ways to reach us. --}}
                <aside class="col-lg-4">
                    <div class="rbt-bg-color-gray-light rbt-radius p-4">
                        <h2 class="h5 mb--16">Nos coordonnées</h2>
                        <dl class="mb-0">
                            <dt class="b3">Horaires</dt>
                            <dd class="mb--16">{{ $contact['opening_hours'] }}</dd>
                            <dt class="b3">Téléphone</dt>
                            <dd class="mb--16"><a href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a></dd>
                            <dt class="b3">E-mail</dt>
                            <dd class="mb--16"><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></dd>
                            <dt class="b3">Adresse</dt>
                            <dd class="mb-0">{{ $contact['address'] }}</dd>
                        </dl>
                        @if ($whatsappUrl)
                            <a class="rbt-btn w-100 mt--24 text-center" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
                                <i class="fa-brands fa-whatsapp mr--4"></i> Écrire sur WhatsApp
                            </a>
                        @endif
                    </div>
                </aside>

                <div class="col-lg-8">
                    <h2 class="h5 mb--8">Envoyer un message</h2>
                    <p class="b3 mb--24">Pour une commande, indiquez son numéro (KM-…) : nous vous répondrons plus vite.</p>

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
                                        <option value="{{ $subject->value }}" @selected(old('subject') === $subject->value)>{{ $subject->getLabel() }}</option>
                                    @endforeach
                                </select>
                                @error('subject')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-12">
                                <label class="rbt-field-label" for="message">Message<span class="rbt-text-color-danger">*</span></label>
                                <textarea class="rbt-input-field" id="message" name="message" rows="6" maxlength="3000" required>{{ old('message') }}</textarea>
                                @error('message')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-12">
                                <x-turnstile />
                            </div>
                        </div>

                        <button type="submit" class="rbt-btn mt--24">Envoyer</button>
                        <p class="b4 mt--12 mb-0">Vos coordonnées servent uniquement à vous répondre.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
