@extends('layouts.storefront')

@section('title', 'Mon compte')
@section('robots', 'noindex, nofollow')

@php
    $firstName = Str::before($user->name, ' ');
    // The settings tab to open: the one whose form was just sent (saved or refused).
    $tab = match (true) {
        $errors->updatePassword->any() || session('status') === 'password-updated' => 'securite',
        $errors->deleteAccount->any() => 'donnees',
        session('account_tab') === 'preferences' => 'preferences',
        default => 'infos',
    };
    $settingsSent = $errors->updateProfileInformation->any() || $errors->updatePassword->any() || $errors->deleteAccount->any()
        || in_array(session('status'), ['profile-information-updated', 'password-updated'], true) || session('account_tab');
    $tabs = [
        'infos' => ['Mes informations', 'fa-id-card'],
        'securite' => ['Mot de passe', 'fa-lock'],
        'preferences' => ['Préférences', 'fa-bell'],
        'donnees' => ['Mes données', 'fa-shield-check'],
    ];
@endphp

@section('content')
    <x-account-layout title="Mon compte" :heading="false">
        {{-- Fortify reports successful updates through the "status" flash. --}}
        @if (session('status') === 'account-created')
            <div class="alert alert-success mb--24" data-popup role="status">
                <strong>Bienvenue {{ $firstName }}, votre compte est créé !</strong>
                Retrouvez ici vos commandes, vos adresses et vos informations.
            </div>
        @elseif (session('status') === 'profile-information-updated')
            <div class="alert alert-success mb--24" data-popup role="status">Vos informations sont enregistrées.</div>
        @elseif (session('status') === 'password-updated')
            <div class="alert alert-success mb--24" data-popup role="status">Votre mot de passe a été modifié.</div>
        @endif

        {{-- Welcome: stands for the page title. --}}
        <section class="kova-dash-hero">
            <div>
                <p class="kova-dash-hero__eyebrow">Mon compte</p>
                <h1 class="kova-dash-hero__title">Bonjour {{ $firstName }}</h1>
                <p class="kova-dash-hero__text">Client depuis {{ $user->created_at->translatedFormat('F Y') }}. Suivez vos commandes et gérez vos informations en un coup d’œil.</p>
            </div>
            <div class="kova-dash-hero__actions">
                <a class="rbt-btn rbt-btn-sm kova-dash-hero__primary" href="{{ route('shop.index') }}"><i class="fa-regular fa-bag-shopping mr--8"></i>Continuer mes achats</a>
                <a class="kova-dash-hero__secondary" href="{{ route('account.tracking') }}"><i class="fa-regular fa-truck-fast mr--8"></i>Suivre une commande</a>
            </div>
        </section>

        {{-- Figures, each leading to its page. --}}
        <div class="kova-dash-stats">
            <a class="kova-dash-stat" href="{{ route('account.orders') }}">
                <span class="kova-dash-stat__icon"><i class="fa-regular fa-bag-shopping"></i></span>
                <span class="kova-dash-stat__value">{{ $stats['orders'] }}</span>
                <span class="kova-dash-stat__label">{{ Str::plural('Commande', $stats['orders']) }}</span>
            </a>
            <a class="kova-dash-stat" href="{{ route('account.tracking') }}">
                <span class="kova-dash-stat__icon kova-dash-stat__icon--gold"><i class="fa-regular fa-clock"></i></span>
                <span class="kova-dash-stat__value">{{ $stats['open'] }}</span>
                <span class="kova-dash-stat__label">En cours</span>
            </a>
            <a class="kova-dash-stat" href="{{ route('account.addresses.index') }}">
                <span class="kova-dash-stat__icon kova-dash-stat__icon--navy"><i class="fa-regular fa-location-dot"></i></span>
                <span class="kova-dash-stat__value">{{ $stats['addresses'] }}</span>
                <span class="kova-dash-stat__label">{{ Str::plural('Adresse', $stats['addresses']) }}</span>
            </a>
            @if ($stats['wishlist'] !== null)
                <a class="kova-dash-stat" href="{{ route('account.wishlist') }}">
                    <span class="kova-dash-stat__icon kova-dash-stat__icon--rose"><i class="fa-regular fa-heart"></i></span>
                    <span class="kova-dash-stat__value">{{ $stats['wishlist'] }}</span>
                    <span class="kova-dash-stat__label">{{ Str::plural('Favori', $stats['wishlist']) }}</span>
                </a>
            @endif
        </div>

        {{-- Guest orders placed with this phone number join the account after a code sent on WhatsApp (F-070). --}}
        @if ($guestOrdersCount > 0 && \App\Services\Security\SmsCode::available())
            <section class="kova-dash-callout">
                <span class="kova-dash-callout__icon"><i class="fa-regular fa-box-open"></i></span>
                <div class="kova-dash-callout__body">
                    <p class="mb--12"><strong>{{ $guestOrdersCount }} {{ Str::plural('commande', $guestOrdersCount) }}</strong> {{ $guestOrdersCount > 1 ? 'ont été passées' : 'a été passée' }} sans compte avec votre numéro. Confirmez que ce numéro est bien le vôtre pour {{ $guestOrdersCount > 1 ? 'les' : 'la' }} retrouver ici.</p>
                    @if (session('claim_code_sent') || $errors->claim->any())
                        <form method="POST" action="{{ route('account.guest-orders.confirm') }}" class="d-flex flex-wrap align-items-start gap-2 mb--12" novalidate>
                            @csrf
                            <div>
                                <label class="visually-hidden" for="claim_code">Code reçu par {{ \App\Services\Security\SmsCode::channelLabel() }}</label>
                                <input class="rbt-input-field" id="claim_code" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="Code reçu par {{ \App\Services\Security\SmsCode::channelLabel() }}" maxlength="10" required>
                                @error('code', 'claim')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            </div>
                            <button type="submit" class="rbt-btn rbt-btn-sm">Confirmer</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('account.guest-orders.claim') }}">
                        @csrf
                        <button type="submit" @class(['rbt-btn rbt-btn-sm', 'rbt-btn-gray-light' => session('claim_code_sent') || $errors->claim->any()])>
                            {{ session('claim_code_sent') || $errors->claim->any() ? 'Renvoyer un code' : 'Recevoir un code par '.\App\Services\Security\SmsCode::channelLabel() }}
                        </button>
                    </form>
                </div>
            </section>
        @endif

        <div class="row g-4 mb--32">
            <div class="col-xl-8">
                <section class="kova-dash-card">
                    <div class="kova-dash-card__head">
                        <h2 class="kova-dash-card__title">Mes dernières commandes</h2>
                        @if ($recentOrders->isNotEmpty())
                            <a class="kova-dash-card__more" href="{{ route('account.orders') }}">Tout voir <i class="fa-regular fa-arrow-right"></i></a>
                        @endif
                    </div>
                    @forelse ($recentOrders as $order)
                        <a href="{{ route('account.orders.show', $order) }}" class="kova-dash-order">
                            <span class="kova-dash-order__icon"><i class="fa-regular fa-receipt"></i></span>
                            <span class="kova-dash-order__main">
                                <strong>{{ $order->number }}</strong>
                                <span>{{ $order->created_at->translatedFormat('j M Y') }} · {{ $order->items_count }} {{ Str::plural('article', $order->items_count) }}</span>
                            </span>
                            <span class="kova-pill kova-pill--{{ $order->status->getColor() }}">{{ $order->status->getLabel() }}</span>
                            <span class="kova-dash-order__total">@money($order->total)</span>
                            <i class="fa-regular fa-chevron-right kova-dash-order__go" aria-hidden="true"></i>
                        </a>
                    @empty
                        <div class="kova-dash-empty">
                            <span class="kova-dash-empty__icon"><i class="fa-regular fa-basket-shopping"></i></span>
                            <p class="kova-dash-empty__title">Aucune commande pour l’instant</p>
                            <p class="kova-dash-empty__text">Vos commandes et leur suivi apparaîtront ici.</p>
                            <a class="rbt-btn rbt-btn-sm" href="{{ route('shop.index') }}">Découvrir la boutique</a>
                        </div>
                    @endforelse
                </section>
            </div>

            <div class="col-xl-4 d-flex flex-column gap-4">
                <section class="kova-dash-card">
                    <div class="kova-dash-card__head">
                        <h2 class="kova-dash-card__title">Adresse de livraison</h2>
                    </div>
                    @if ($defaultAddress)
                        <p class="kova-dash-address__label"><i class="fa-regular fa-location-dot mr--8"></i>{{ $defaultAddress->label ?: 'Adresse principale' }}</p>
                        <p class="kova-dash-address__line">{{ $defaultAddress->recipient_name }}</p>
                        <p class="kova-dash-address__line">{{ $defaultAddress->summary() }}</p>
                        @if ($defaultAddress->phone)
                            <p class="kova-dash-address__line">{{ \App\Support\PhoneNumber::format($defaultAddress->phone) }}</p>
                        @endif
                        <a class="kova-dash-card__more mt--12 d-inline-block" href="{{ route('account.addresses.index') }}">Gérer mes adresses <i class="fa-regular fa-arrow-right"></i></a>
                    @else
                        <p class="kova-dash-address__line mb--16">Enregistrez une adresse pour commander plus vite : elle sera proposée à chaque commande.</p>
                        <a class="rbt-btn rbt-btn-sm rbt-btn-border" href="{{ route('account.addresses.index') }}"><i class="fa-regular fa-plus mr--8"></i>Ajouter une adresse</a>
                    @endif
                </section>

                <section class="kova-dash-card kova-dash-help">
                    <h2 class="kova-dash-card__title mb--8">Besoin d’aide ?</h2>
                    <p class="kova-dash-address__line mb--16">Une question sur une commande, une livraison ou un paiement ?</p>
                    <a class="kova-dash-help__link" href="{{ route('faq') }}"><i class="fa-regular fa-circle-question"></i>Questions fréquentes</a>
                    <a class="kova-dash-help__link" href="{{ route('contact.show') }}"><i class="fa-regular fa-headset"></i>Nous contacter</a>
                </section>
            </div>
        </div>

        {{-- Account settings in tabs: the tab of the form just sent opens. --}}
        <section class="kova-dash-card" id="parametres">
            <div class="kova-dash-card__head">
                <h2 class="kova-dash-card__title">Paramètres du compte</h2>
            </div>
            <ul class="nav kova-dash-tabs" role="tablist">
                @foreach ($tabs as $key => [$label, $icon])
                    <li class="nav-item" role="presentation">
                        <button @class(['nav-link', 'active' => $tab === $key]) id="tab-{{ $key }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $key }}" type="button" role="tab" aria-controls="pane-{{ $key }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                            <i class="fa-regular {{ $icon }}"></i>{{ $label }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                <div @class(['tab-pane fade', 'show active' => $tab === 'infos']) id="pane-infos" role="tabpanel" aria-labelledby="tab-infos" tabindex="0">
                    <form method="POST" action="{{ route('user-profile-information.update') }}" class="row g-3" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="col-md-6">
                            <label class="rbt-field-label" for="profile_name">Nom complet<span class="rbt-text-color-danger">*</span></label>
                            <input class="rbt-input-field" id="profile_name" name="name" type="text" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                            @error('name', 'updateProfileInformation')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="rbt-field-label" for="profile_phone">Téléphone<span class="rbt-text-color-danger">*</span></label>
                            <input class="rbt-input-field" id="profile_phone" name="phone" type="tel" value="{{ old('phone', $phone) }}" autocomplete="tel" required>
                            @error('phone', 'updateProfileInformation')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="rbt-field-label" for="profile_email">E-mail <span class="b4">(facultatif)</span></label>
                            <input class="rbt-input-field" id="profile_email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email">
                            @error('email', 'updateProfileInformation')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="rbt-field-label" for="profile_current_password">Mot de passe actuel</label>
                            <input class="rbt-input-field" id="profile_current_password" name="current_password" type="password" autocomplete="current-password">
                            @error('current_password', 'updateProfileInformation')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-12">
                            <p class="kova-dash-note"><i class="fa-regular fa-shield-check"></i>Le mot de passe actuel n’est demandé que pour changer de téléphone ou d’e-mail ; un avis est alors envoyé à vos anciennes coordonnées.</p>
                        </div>
                        <div class="col-12"><button type="submit" class="rbt-btn rbt-btn-sm">Enregistrer</button></div>
                    </form>
                </div>

                <div @class(['tab-pane fade', 'show active' => $tab === 'securite']) id="pane-securite" role="tabpanel" aria-labelledby="tab-securite" tabindex="0">
                    <form method="POST" action="{{ route('user-password.update') }}" class="row g-3" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="col-md-4">
                            <label class="rbt-field-label" for="current_password">Mot de passe actuel</label>
                            <input class="rbt-input-field" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                            @error('current_password', 'updatePassword')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="rbt-field-label" for="new_password">Nouveau mot de passe</label>
                            <input class="rbt-input-field" id="new_password" name="password" type="password" autocomplete="new-password" required>
                            @error('password', 'updatePassword')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="rbt-field-label" for="new_password_confirmation">Confirmation</label>
                            <input class="rbt-input-field" id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        </div>
                        <div class="col-12">
                            <p class="kova-dash-note"><i class="fa-regular fa-key"></i>8 caractères minimum. Un avis vous est envoyé après chaque changement.</p>
                        </div>
                        <div class="col-12"><button type="submit" class="rbt-btn rbt-btn-sm">Modifier le mot de passe</button></div>
                    </form>
                </div>

                <div @class(['tab-pane fade', 'show active' => $tab === 'preferences']) id="pane-preferences" role="tabpanel" aria-labelledby="tab-preferences" tabindex="0">
                    <form method="POST" action="{{ route('account.preferences') }}">
                        @csrf
                        <div class="kova-dash-option">
                            <div class="rbt-check-group mb-0">
                                <input type="checkbox" id="marketing_opt_in" name="marketing_opt_in" value="1" @checked($user->marketing_opt_in)>
                                <label for="marketing_opt_in">Je souhaite recevoir les offres et nouveautés de {{ config('storefront.name') }}.</label>
                            </div>
                            <p class="kova-dash-note mb-0 mt--8">Les messages sur vos commandes (confirmation, livraison) vous sont toujours envoyés.</p>
                        </div>
                        <button type="submit" class="rbt-btn rbt-btn-sm rbt-btn-border mt--16">Enregistrer mes préférences</button>
                    </form>
                </div>

                {{-- Right of access and erasure (F-075). --}}
                <div @class(['tab-pane fade', 'show active' => $tab === 'donnees']) id="pane-donnees" role="tabpanel" aria-labelledby="tab-donnees" tabindex="0">
                    <div class="kova-dash-option mb--16">
                        <p class="mb--12"><strong>Télécharger mes données</strong><br><span class="kova-dash-note">Tout ce que nous conservons sur vous : compte, adresses et commandes.</span></p>
                        <a class="rbt-btn rbt-btn-sm rbt-btn-border" href="{{ route('account.export') }}"><i class="fa-regular fa-download mr--8"></i>Télécharger mes données</a>
                    </div>

                    <details class="kova-dash-danger" @if ($errors->deleteAccount->any()) open @endif>
                        <summary>Supprimer mon compte</summary>
                        <form method="POST" action="{{ route('account.destroy') }}" class="mt--16">
                            @csrf
                            @method('DELETE')
                            <p class="b3">Vos informations personnelles et vos adresses seront effacées. Vos commandes sont conservées pour la comptabilité, sans vos coordonnées. Cette action est définitive.</p>
                            <label class="rbt-field-label" for="delete_password">Confirmez avec votre mot de passe</label>
                            <input class="rbt-input-field mb--12" id="delete_password" name="password" type="password" autocomplete="current-password" required>
                            @error('password', 'deleteAccount')<span class="d-block mb--12 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            <button type="submit" class="rbt-btn rbt-btn-sm kova-btn-danger">Supprimer définitivement mon compte</button>
                        </form>
                    </details>
                </div>
            </div>
        </section>

        @if ($settingsSent)
            {{-- Back on the form just sent, not at the top of the page. --}}
            <script>document.getElementById('parametres')?.scrollIntoView({ block: 'start' });</script>
        @endif
    </x-account-layout>
@endsection
