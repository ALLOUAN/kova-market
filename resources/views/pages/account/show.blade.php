@extends('layouts.storefront')

@section('title', 'Mon compte')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-account-layout title="Mon compte">
        {{-- Fortify reports successful updates through the "status" flash. --}}
        @if (session('status') === 'profile-information-updated')
            <div class="alert alert-success mb--24" role="status">Vos informations sont enregistrées.</div>
        @elseif (session('status') === 'password-updated')
            <div class="alert alert-success mb--24" role="status">Votre mot de passe a été modifié.</div>
        @endif

        {{-- Guest orders placed with this phone number join the account after a code sent on WhatsApp (F-070). --}}
        @if ($guestOrdersCount > 0 && \App\Services\Security\SmsCode::available())
            <section class="rbt-bg-color-gray-light rbt-radius p-4 mb--40">
                <p class="b2 mb--12"><strong>{{ $guestOrdersCount }} {{ Str::plural('commande', $guestOrdersCount) }}</strong> {{ $guestOrdersCount > 1 ? 'ont été passées' : 'a été passée' }} sans compte avec votre numéro. Confirmez que ce numéro est bien le vôtre pour {{ $guestOrdersCount > 1 ? 'les' : 'la' }} retrouver ici.</p>
                @if (session('claim_code_sent') || $errors->claim->any())
                    <form method="POST" action="{{ route('account.guest-orders.confirm') }}" class="d-flex flex-wrap align-items-start gap-2" novalidate>
                        @csrf
                        <div>
                            <label class="visually-hidden" for="claim_code">Code reçu par {{ \App\Services\Security\SmsCode::channelLabel() }}</label>
                            <input class="rbt-input-field" id="claim_code" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="Code reçu par {{ \App\Services\Security\SmsCode::channelLabel() }}" maxlength="10" required>
                            @error('code', 'claim')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <button type="submit" class="rbt-btn rbt-btn-sm">Confirmer</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('account.guest-orders.claim') }}" class="mt--12">
                    @csrf
                    <button type="submit" @class(['rbt-btn rbt-btn-sm', 'rbt-btn-gray-light' => session('claim_code_sent') || $errors->claim->any()])>
                        {{ session('claim_code_sent') || $errors->claim->any() ? 'Renvoyer un code' : 'Recevoir un code par '.\App\Services\Security\SmsCode::channelLabel() }}
                    </button>
                </form>
            </section>
        @endif

        <section class="mb--40">
            <div class="d-flex justify-content-between align-items-center mb--16">
                <h2 class="h5 mb-0">Mes dernières commandes</h2>
                <a class="b3" href="{{ route('account.orders') }}">Tout voir</a>
            </div>
            @forelse ($recentOrders as $order)
                <a href="{{ route('account.orders.show', $order) }}" class="d-flex flex-wrap justify-content-between gap-2 border rbt-radius p-3 mb--8">
                    <span><strong>{{ $order->number }}</strong> · {{ $order->created_at->format('d/m/Y') }} · {{ $order->items_count }} {{ Str::plural('article', $order->items_count) }}</span>
                    <span>@money($order->total) · <span class="rbt-text-bold">{{ $order->status->getLabel() }}</span></span>
                </a>
            @empty
                <p class="mb-0">Vous n’avez pas encore passé de commande. <a href="{{ route('shop.index') }}">Découvrir la boutique</a></p>
            @endforelse
        </section>

        <section class="mb--40">
            <h2 class="h5 mb--16">Mes informations</h2>
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="row g-3" novalidate>
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="rbt-field-label" for="profile_name">Nom complet<span class="rbt-text-color-danger">*</span></label>
                    <input class="rbt-input-field" id="profile_name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                    @error('name', 'updateProfileInformation')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="rbt-field-label" for="profile_phone">Téléphone<span class="rbt-text-color-danger">*</span></label>
                    <input class="rbt-input-field" id="profile_phone" name="phone" type="tel" value="{{ old('phone', $phone) }}" autocomplete="tel" required>
                    @error('phone', 'updateProfileInformation')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <label class="rbt-field-label" for="profile_email">E-mail <span class="b4">(facultatif)</span></label>
                    <input class="rbt-input-field" id="profile_email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email">
                    @error('email', 'updateProfileInformation')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="rbt-field-label" for="profile_current_password">Mot de passe actuel</label>
                    <input class="rbt-input-field" id="profile_current_password" name="current_password" type="password" autocomplete="current-password">
                    <span class="d-block mt--4 b4">Demandé pour changer de téléphone ou d’e-mail. Un avis est envoyé à vos anciennes coordonnées.</span>
                    @error('current_password', 'updateProfileInformation')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <div class="col-12"><button type="submit" class="rbt-btn rbt-btn-sm">Enregistrer</button></div>
            </form>
        </section>

        <section class="mb--40">
            <h2 class="h5 mb--16">Mot de passe</h2>
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
                <div class="col-12"><button type="submit" class="rbt-btn rbt-btn-sm">Modifier le mot de passe</button></div>
            </form>
        </section>

        <section class="mb--40">
            <h2 class="h5 mb--16">Préférences</h2>
            <form method="POST" action="{{ route('account.preferences') }}">
                @csrf
                <div class="rbt-check-group mb--12">
                    <input type="checkbox" id="marketing_opt_in" name="marketing_opt_in" value="1" @checked($user->marketing_opt_in)>
                    <label for="marketing_opt_in">Je souhaite recevoir les offres et nouveautés de {{ config('storefront.name') }}.</label>
                </div>
                <button type="submit" class="rbt-btn rbt-btn-sm rbt-btn-border">Enregistrer mes préférences</button>
            </form>
        </section>

        {{-- Right of access and erasure (F-075). --}}
        <section>
            <h2 class="h5 mb--16">Mes données personnelles</h2>
            <p class="mb--16">Téléchargez tout ce que nous conservons sur vous : compte, adresses et commandes.</p>
            <a class="rbt-btn rbt-btn-sm rbt-btn-border mb--24" href="{{ route('account.export') }}">Télécharger mes données</a>

            <details @if ($errors->deleteAccount->any()) open @endif>
                <summary class="rbt-text-color-danger">Supprimer mon compte</summary>
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
        </section>
    </x-account-layout>
@endsection
