@extends('courier.layout')

{{-- "Mon compte": who I am for the store (name, phone, transport, zones), my password, and how to reach the store.
     At the first sign-in only the password form is shown, without the tab bar (every page leads back here). --}}
@section('title', $mustChange ? 'Choisir mon mot de passe' : 'Mon compte')
@unless ($mustChange)
    @section('tab', 'account')
@endunless

@section('hero')
    <header class="ch-hero">
        @include('courier.partials.hero-top', [
            'title' => $mustChange ? 'Bienvenue '.\Illuminate\Support\Str::before($courier->name(), ' ') : 'Mon compte',
            'subtitle' => $mustChange ? 'Une dernière étape avant vos livraisons' : 'Mes informations et mon mot de passe',
        ])
    </header>
@endsection

@section('content')
    @if ($mustChange)
        <div class="alert ok">Bienvenue ! Choisissez votre propre mot de passe pour remplacer celui reçu sur WhatsApp.</div>
    @else
        <section class="ca-profile">
            <div class="ca-profile__row">
                <span class="ca-profile__label">@include('courier.partials.icon', ['name' => 'user', 'class' => 'ico--sm']) Nom</span>
                <strong>{{ $courier->name() }}</strong>
            </div>
            <div class="ca-profile__row">
                <span class="ca-profile__label">@include('courier.partials.icon', ['name' => 'phone', 'class' => 'ico--sm']) Téléphone</span>
                <strong>{{ $courier->formattedPhone() }}</strong>
            </div>
            <div class="ca-profile__row">
                <span class="ca-profile__label">@include('courier.partials.icon', ['name' => 'truck', 'class' => 'ico--sm']) Transport</span>
                <strong>{{ $courier->transport?->getLabel() ?? '—' }}</strong>
            </div>
            <div class="ca-profile__row ca-profile__row--zones">
                <span class="ca-profile__label">@include('courier.partials.icon', ['name' => 'pin', 'class' => 'ico--sm']) Mes zones</span>
                <span class="ca-zones">
                    @forelse ($courier->zones as $zone)
                        <span class="ch-pill ch-pill--ready">{{ $zone->name }}</span>
                    @empty
                        <span class="muted">Aucune zone : demandez à la boutique.</span>
                    @endforelse
                </span>
            </div>
            <p class="ca-profile__note">Une information à corriger ? Seule la boutique peut la modifier.</p>
        </section>
    @endif

    <section class="ca-card">
        <h2 class="ca-card__title">@include('courier.partials.icon', ['name' => 'user', 'class' => 'ico--sm']) {{ $mustChange ? 'Choisir mon mot de passe' : 'Changer mon mot de passe' }}</h2>
        <form method="POST" action="{{ route('courier.password.update') }}" data-password-form>
            @csrf
            @method('PUT')
            @unless ($mustChange)
                <label for="current_password">Mot de passe actuel</label>
                <div class="field">
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="with-toggle" required @error('current_password') aria-invalid="true" @enderror>
                    <button type="button" class="toggle" data-toggle-password="current_password" aria-pressed="false">Afficher</button>
                </div>
                @error('current_password')<p class="error">{{ $message }}</p>@enderror
            @endunless

            <label for="password">Nouveau mot de passe</label>
            <div class="field">
                <input id="password" name="password" type="password" autocomplete="new-password" class="with-toggle" required minlength="8" aria-describedby="password-hint" @error('password') aria-invalid="true" @enderror>
                <button type="button" class="toggle" data-toggle-password="password" aria-pressed="false">Afficher</button>
            </div>
            {{-- Live check of the 8 characters asked by the server. --}}
            <p class="hint ca-rule" id="password-hint" data-password-rule>@include('courier.partials.icon', ['name' => 'check', 'class' => 'ico--xs']) 8 caractères minimum</p>
            @error('password')<p class="error">{{ $message }}</p>@enderror

            <label for="password_confirmation">Confirmer le mot de passe</label>
            <div class="field">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="with-toggle" required>
                <button type="button" class="toggle" data-toggle-password="password_confirmation" aria-pressed="false">Afficher</button>
            </div>
            <p class="hint ca-rule" data-password-match hidden>@include('courier.partials.icon', ['name' => 'check', 'class' => 'ico--xs']) Les deux mots de passe sont identiques</p>

            <button type="submit" class="btn ok" style="margin-top: 20px">Enregistrer</button>
        </form>
    </section>

    @unless ($mustChange)
        <section class="ca-card ca-help">
            <h2 class="ca-card__title">Besoin d’aide ?</h2>
            <p class="muted">Un souci avec une livraison, une zone ou vos informations : la boutique vous répond.</p>
            <div class="ca-help__actions">
                @if ($contact['phone'] ?? null)
                    <a class="ch-action" href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}">@include('courier.partials.icon', ['name' => 'phone'])<span>Appeler la boutique</span></a>
                @endif
                @if ($storeWhatsapp)
                    <a class="ch-action" href="{{ $storeWhatsapp }}" target="_blank" rel="noopener">@include('courier.partials.icon', ['name' => 'chat'])<span>WhatsApp</span></a>
                @endif
            </div>
        </section>
    @endunless

    <script>
        (() => {
            // Show / hide each password; live checks of the length and of the confirmation.
            document.querySelectorAll('[data-toggle-password]').forEach((button) => button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.togglePassword);
                const shown = input.type === 'text';
                input.type = shown ? 'password' : 'text';
                button.textContent = shown ? 'Afficher' : 'Masquer';
                button.setAttribute('aria-pressed', shown ? 'false' : 'true');
            }));
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            const rule = document.querySelector('[data-password-rule]');
            const match = document.querySelector('[data-password-match]');
            const check = () => {
                rule.classList.toggle('is-ok', password.value.length >= 8);
                match.hidden = confirmation.value === '' || confirmation.value !== password.value;
                match.classList.toggle('is-ok', !match.hidden);
            };
            password.addEventListener('input', check);
            confirmation.addEventListener('input', check);
        })();
    </script>
@endsection
